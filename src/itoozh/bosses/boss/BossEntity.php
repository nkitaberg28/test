<?php

namespace itoozh\bosses\boss;

use itoozh\bosses\Main;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\ExplodeSound;

abstract class BossEntity extends Living
{
    protected string $bossName = "Boss";

    protected bool $isFlying = false;

    public function isFlying(): bool
    {
        return $this->isFlying;
    }
    protected array $drops = [];

    public function setBossName(string $bossName): void
    {
        $this->bossName = $bossName;
    }

    public function setEntityDamage(int $entityDamage): void
    {
        $this->entityDamage = $entityDamage;
    }
    protected float $jumpVelocity = 0.5;
    protected float $speed = 0.3;

    public function setSpeed(float $speed): void
    {
        $this->speed = $speed;
    }
    private int $explosionCooldown = 0;
    private int $effectsCooldown = 0;
    private int $minionCooldown = 0;

    private int $entityDamage = 2;

    public function __construct(Location $location, ?CompoundTag $nbt = null) {
        parent::__construct($location, $nbt);
        $this->setCanSaveWithChunk(false);
    }



    protected function initEntity(CompoundTag $nbt): void {
        $this->setMaxHealth(40);
        $this->setHealth(40);
        $this->setCanSaveWithChunk(false);
        $this->setNameTagAlwaysVisible();
        parent::initEntity($nbt);
    }

    protected function entityBaseTick(int $tickDiff = 1): bool {
        if($this->closed) return false;

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $this->setNameTag(TextFormat::DARK_RED . TextFormat::BOLD . $this->bossName . TextFormat::RESET . "\n" . $this->lifeBar(20));

        if(!$this->isAlive()) return $hasUpdate;

        $nearest = $this->location->world->getNearestEntity($this->location, 32, Player::class);
        if($nearest === null) return $hasUpdate;
        if (!$nearest instanceof Player) return $hasUpdate;

        $this->lookAt($nearest->getEyePos());

        if($this->isCollidedHorizontally) $this->jump();
        if($nearest->location->distance($this->location) > 1 && $this->isCollided){
            $mVec = $this->getDirectionVector()->multiply($this->speed);
            $mVec->y = 0;
            $this->motion = $this->motion->addVector($mVec);
            if ($this->isFlying) {
                $mVec->y = 0.5;
            }
            $this->motion = $this->motion->addVector($mVec);
        }

        if($nearest->location->distance($this->location) < 1){
            $nearest->attack(new EntityDamageByEntityEvent($this, $nearest, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->entityDamage));
        }

        $config = Main::getInstance()->getConfig();
        $radius = Main::getInstance()->getConfig()->getNested("bossConfig.attacks.negativeEffects.radius", 8);
        $effectDuration = Main::getInstance()->getConfig()->getNested("bossConfig.attacks.negativeEffects.duration", 10);
        $effectCooldown = Main::getInstance()->getConfig()->getNested("bossConfig.attacks.negativeEffects.cooldown", 20);

        if ($this->effectsCooldown <= 0) {
            foreach ($this->getWorld()->getNearbyEntities($this->getBoundingBox()->expandedCopy($radius, $radius, $radius)) as $nearby) {
                if ($nearby instanceof Player) {
                    foreach (Main::negativeEffects() as $effect) {
                        $effectInstance = new EffectInstance($effect, 20 * $effectDuration, 1);
                        $nearby->getEffects()->add($effectInstance);
                    }
                }
            }
            $this->effectsCooldown = $effectCooldown * 20;
        } else {
            $this->effectsCooldown--;
        }

        $explosionRadius = Main::getInstance()->getConfig()->getNested("bossConfig.attacks.explosion.radius", 8);
        $explosionCooldown = Main::getInstance()->getConfig()->getNested("bossConfig.attacks.explosion.cooldown", 10);

        if ($this->explosionCooldown <= 0) {
            foreach ($this->getWorld()->getNearbyEntities($this->getBoundingBox()->expandedCopy($explosionRadius, $explosionRadius, $explosionRadius)) as $nearby) {
                if ($nearby instanceof Player) {
                    $playerLocation = $this->getLocation();
                    $targetLocation = $nearby->getLocation();
                    $yOffset = $this->getEyeHeight();

                    $x = $targetLocation->getX() - $playerLocation->getX();
                    $y = ($targetLocation->getY() + $nearby->getEyeHeight()) - ($playerLocation->getY() + $yOffset);
                    $z = $targetLocation->getZ() - $playerLocation->getZ();
                    $vector = new Vector3($x, $y, $z);
                    $vector = $vector->normalize()->multiply(1.0)->withComponents($vector->getX(), 0.4, $vector->getZ());
                    $nearby->setMotion($vector);
                    $nearest->attack(new EntityDamageByEntityEvent($this, $nearest, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $this->entityDamage * 4));
                    $this->getWorld()->addParticle($this->getPosition(), new HugeExplodeSeedParticle(), [$nearby]);
                    $this->getWorld()->addSound($this->getPosition(), new ExplodeSound(), [$nearby]);
                }
            }
            $this->explosionCooldown = $explosionCooldown * 20;
        } else {
            $this->explosionCooldown--;
        }

        $minionCooldown = Main::getInstance()->getConfig()->getNested("bossConfig.attacks.minions.cooldown", 10);
        $minionsSpawnAmount = Main::getInstance()->getConfig()->getNested("bossConfig.attacks.minions.spawnAmount", 3);


        if ($this->minionCooldown <= 0) {
            $this->minionCooldown = $minionCooldown * 20;

            $world = $this->getWorld();
            $location = $this->getLocation();

            $mobs = 0;
            foreach ($world->getEntities() as $entidad) {
                if ($entidad instanceof MinionEntity) {
                    $dist = $location->distance($entidad->getLocation());
                    if ($dist <= 32) {
                        $mobs++;
                    }
                }
            }

            if ($mobs >= $minionsSpawnAmount) {
                return $hasUpdate;
            }

            for ($i = 0; $i < $minionsSpawnAmount; $i++) {
                $x = $location->getX() + mt_rand(-5, 5);
                $y = $location->getY();
                $z = $location->getZ() + mt_rand(-5, 5);

                $minion = new MinionEntity(new Location($x, $y, $z, $world, $location->getPitch(), $location->getYaw()));
                $minion->setBossName($this->bossName);
                $minion->spawnToAll();
            }
        } else {
            $this->minionCooldown--;
        }

        return $hasUpdate;
    }

    public function lookAt(Vector3 $target) : void{
        $horizontal = sqrt(($target->x - $this->location->x) ** 2 + ($target->z - $this->location->z) ** 2);
        $vertical = $target->y - ($this->location->y + $this->getEyeHeight());
        $this->location->pitch = -atan2($vertical, $horizontal) / M_PI * 180;

        $xDist = $target->x - $this->location->x;
        $zDist = $target->z - $this->location->z;
        $this->location->yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if($this->location->yaw < 0){
            $this->location->yaw += 360.0;
        }
    }

    public function getXpDropAmount(): int {
        return 500;
    }

    public function getDrops(): array {
        return $this->drops;
    }

    public function setDrops(array $drops): void {
        $this->drops = $drops;
    }

    public function attack(EntityDamageEvent $source): void {
        if($source instanceof EntityDamageByEntityEvent){
            $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::ANGRY, true);
        }
        parent::attack($source);
    }

    function lifeBar($barLength) {
        $porcentajeVida = ($this->getHealth() / $this->getMaxHealth()) * 100;

        $barrasVerdes = ($porcentajeVida / 100) * $barLength;
        $barrasRojas = $barLength - $barrasVerdes;

        $barraDeVida = str_repeat((TextFormat::GREEN . "|"), (int)$barrasVerdes) . str_repeat((TextFormat::RED . "|"), (int)$barrasRojas);

        return $barraDeVida;
    }

    public function setMaxHealth(int $amount): void
    {
        parent::setMaxHealth($amount); // TODO: Change the autogenerated stub
        $this->setHealth($amount);
    }

}