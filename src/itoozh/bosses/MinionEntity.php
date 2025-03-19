<?php

namespace itoozh\bosses\boss;

use itoozh\bosses\Main;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Zombie;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class MinionEntity extends Zombie
{
    private bool $isBaby = true;
    private float $speed = 0.3;
    protected float $jumpVelocity = 0.5;

    protected string $bossName = "Boss";

    public function setBossName(string $bossName): void
    {
        $this->bossName = $bossName;
    }
    protected function getInitialSizeInfo() : EntitySizeInfo {
        return new EntitySizeInfo(1, 0.5);
    }


    protected function initEntity(CompoundTag $nbt) : void {
        parent::initEntity($nbt);
        $helmets = [
            VanillaItems::DIAMOND_HELMET(),
            VanillaItems::AIR(),
            VanillaItems::IRON_HELMET(),
            VanillaItems::AIR(),
            VanillaItems::GOLDEN_HELMET(),
            VanillaItems::AIR(),
            VanillaItems::CHAINMAIL_HELMET(),
            VanillaItems::AIR(),
        ];
        $chestplates = [
            VanillaItems::DIAMOND_CHESTPLATE(),
            VanillaItems::AIR(),
            VanillaItems::IRON_CHESTPLATE(),
            VanillaItems::AIR(),
            VanillaItems::GOLDEN_CHESTPLATE(),
            VanillaItems::AIR(),
            VanillaItems::CHAINMAIL_CHESTPLATE(),
            VanillaItems::AIR(),
        ];
        $leggings = [
            VanillaItems::DIAMOND_LEGGINGS(),
            VanillaItems::AIR(),
            VanillaItems::IRON_LEGGINGS(),
            VanillaItems::AIR(),
            VanillaItems::GOLDEN_LEGGINGS(),
            VanillaItems::AIR(),
            VanillaItems::CHAINMAIL_LEGGINGS(),
            VanillaItems::AIR(),
        ];
        $boots = [
            VanillaItems::DIAMOND_BOOTS(),
            VanillaItems::AIR(),
            VanillaItems::IRON_BOOTS(),
            VanillaItems::AIR(),
            VanillaItems::GOLDEN_BOOTS(),
            VanillaItems::AIR(),
            VanillaItems::CHAINMAIL_BOOTS(),
            VanillaItems::AIR(),
        ];

        $this->getArmorInventory()->setHelmet($helmets[array_rand($helmets)]);
        $this->getArmorInventory()->setChestplate($chestplates[array_rand($chestplates)]);
        $this->getArmorInventory()->setLeggings($leggings[array_rand($leggings)]);
        $this->getArmorInventory()->setBoots($boots[array_rand($boots)]);
        $this->setNameTagAlwaysVisible();

        $this->setMaxHealth(Main::getInstance()->getConfig()->getNested("bossConfig.attacks.minions.maxHealth", 20));
        $this->setHealth($this->getMaxHealth());

        $nbt->setByte('isBaby', (int) $this->isBaby);
    }

    public function spawnTo(Player $player): void
    {
        parent::spawnTo($player);
        $this->setScale(0.5);
    }

    protected function syncNetworkData(EntityMetadataCollection $properties) : void {
        parent::syncNetworkData($properties);
        $properties->setGenericFlag(EntityMetadataFlags::BABY, $this->isBaby);
    }

    public function getDrops() : array {
        return [];
    }

    public function getXpDropAmount() : int {
        return 5;
    }

    protected function entityBaseTick(int $tickDiff = 1): bool {
        if($this->closed) return false;

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $this->setNameTag(TextFormat::MINECOIN_GOLD . $this->bossName . "Помощник" . TextFormat::RESET . "\n" . TextFormat::WHITE . (int) $this->getHealth() . TextFormat::RED . " ❤");

        if(!$this->isAlive()) return $hasUpdate;

        $nearest = $this->location->world->getNearestEntity($this->location, 32, Player::class);
        if($nearest === null) return $hasUpdate;

        $this->lookAt($nearest->getEyePos());

        if($this->isCollidedHorizontally) $this->jump();
        if($nearest->location->distance($this->location) > 1 && $this->isCollided){
            $mVec = $this->getDirectionVector()->multiply($this->speed);
            $mVec->y = 0;
            $this->motion = $this->motion->addVector($mVec);
        }

        if($nearest->location->distance($this->location) < 1){
            $nearest->attack(new EntityDamageByEntityEvent($this, $nearest, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 2));
        }

        return $hasUpdate;
    }

    public function lookAt(Vector3 $target) : void{
        $horizontal = sqrt(($target->x - $this->location->x) ** 2 + ($target->z - $this->location->z) ** 2);
        $vertical = $target->y - ($this->location->y + $this->getEyeHeight());
        $this->location->pitch = -atan2($vertical, $horizontal) / M_PI * 180; //negative is up, positive is down

        $xDist = $target->x - $this->location->x;
        $zDist = $target->z - $this->location->z;
        $this->location->yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if($this->location->yaw < 0){
            $this->location->yaw += 360.0;
        }
    }

}