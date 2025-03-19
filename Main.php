<?php

namespace itoozh\bosses;

use itoozh\bosses\boss\BossEntity;
use itoozh\bosses\boss\MinionEntity;
use itoozh\bosses\boss\type\BlazeBoss;
use itoozh\bosses\boss\type\DragonBoss;
use itoozh\bosses\boss\type\EndermanBoss;
use itoozh\bosses\boss\type\PiglinBoss;
use itoozh\bosses\boss\type\SpiderBoss;
use itoozh\bosses\boss\type\WardenBoss;
use itoozh\bosses\boss\type\WitchBoss;
use itoozh\bosses\boss\type\WitherBoss;
use itoozh\bosses\boss\type\WitherSkeletonBoss;
use itoozh\bosses\boss\type\ZombieBoss;
use itoozh\bosses\boss\type\ZombiePigmanBoss;
use itoozh\bosses\command\BossCommand;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

class Main extends PluginBase implements Listener {
    use SingletonTrait;

    protected function onEnable(): void {
        $this->saveDefaultConfig();

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->getServer()->getCommandMap()->register("bosses", new BossCommand());

        $this->registerEntities();
    }

    protected function onLoad(): void {
        self::setInstance($this);
    }

    public static function negativeEffects(): array {
        return [
            VanillaEffects::SLOWNESS(),
            VanillaEffects::POISON(),
            VanillaEffects::WITHER(),
            VanillaEffects::HUNGER(),
        ];
    }

    function registerEntities(): void {
        EntityFactory::getInstance()->register(WitchBoss::class, function (World $world, CompoundTag $nbt): WitchBoss {
            return new WitchBoss(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['WitchBoss']);
        EntityFactory::getInstance()->register(WardenBoss::class, function (World $world, CompoundTag $nbt): WardenBoss {
            return new WardenBoss(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['WardenBoss']);
        EntityFactory::getInstance()->register(ZombieBoss::class, function (World $world, CompoundTag $nbt): ZombieBoss {
            return new ZombieBoss(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['ZombieBoss']);
        EntityFactory::getInstance()->register(WitherBoss::class, function (World $world, CompoundTag $nbt): WitherBoss {
            return new WitherBoss(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['WitherBoss']);

        EntityFactory::getInstance()->register(MinionEntity::class, function (World $world, CompoundTag $nbt): MinionEntity {
            return new MinionEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['Minion']);
    }

    public static function stringBosses(): array {
        return [
            'Witch',
            'Warden',
            'Zombie',
            'Wither',
            'Spider',
            'Enderman',
            'Blaze',
            'Piglin',
            'Wither Esqueleto',
            'Zombie Pigman',
            'Dragon'
        ];
    }

    public static function stringToBoss(string $string, Location $location): BossEntity {
        return match ($string) {
            'Wither' => new WitherBoss($location),
            'Zombie' => new ZombieBoss($location),
            'Warden' => new WardenBoss($location),
            'Spider' => new SpiderBoss($location),
            'Blaze' => new BlazeBoss($location),
            'Piglin' => new PiglinBoss($location),
            'Enderman' => new EndermanBoss($location),
            'Wither Esqueleto' => new WitherSkeletonBoss($location),
            'Zombie Pigman' => new ZombiePigmanBoss($location),
            'Dragon' => new DragonBoss($location),
            default => new WitchBoss($location),
        };
    }

    /**
     * @param EntityDamageByEntityEvent $ev
     *
     * @priority HIGHEST
     */
    public function onDamage(EntityDamageByEntityEvent $ev): void {
        $e = $ev->getEntity();

        if (!$e instanceof BossEntity) return;
        $dmg = $ev->getDamager();
        $amt = $ev->getFinalDamage();
        if (!$dmg instanceof Player) return;
        if ($amt > $e->getHealth() && $e->isAlive()) {
            $e->kill();
            foreach ($this->getConfig()->getNested("bossConfig.deathCommands", []) as $command) {
                $this->getServer()->dispatchCommand(new ConsoleCommandSender($server = $this->getServer(), $server->getLanguage()), str_replace(["{damage}", "{last_damager}"], [$amt, $dmg->getName()], $command));
            }
        }
    }

    public function handlerDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if ($event->getCause() == EntityDamageEvent::CAUSE_FALL) {
            if (!$entity instanceof BossEntity) return;
            if ($entity->isFlying()) {
                $event->cancel();
            }
        }
    }
}