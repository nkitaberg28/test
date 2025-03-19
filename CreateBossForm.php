<?php

namespace itoozh\bosses\form;

use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\DropdownEntry;
use cosmicpe\form\entries\custom\InputEntry;
use cosmicpe\form\entries\custom\ToggleEntry;
use itoozh\bosses\boss\BossEntity;
use itoozh\bosses\boss\type\WardenBoss;
use itoozh\bosses\boss\type\WitchBoss;
use itoozh\bosses\boss\type\ZombieBoss;
use itoozh\bosses\Main;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class CreateBossForm extends CustomForm
{
    public function __construct(
        private ?string $name = null,
        private ?int $life = null,
        private ?int $damage = null,
        private ?string $entity = null,
        private ?float $scale = null,
        private ?float $speed = null,
        private bool $broadcast = false
    ) {
        parent::__construct(TextFormat::colorize('&fСоздание босса'));
        $bossesNames = Main::stringBosses();
        $nameInput = new InputEntry('Имя босса');
        $lifeInput = new InputEntry('Здоровье');
        $damageInput = new InputEntry('Урон');
        $scaleInput = new InputEntry('Масштаб');
        $speedInput = new InputEntry('Скорость');
        $entityDropdown = new DropdownEntry('Тип существа', $bossesNames);
        $broadcastToggle = new ToggleEntry('Оповещение');

        $this->addEntry($nameInput, function (Player $player, InputEntry $entry, string $value) : void {
            if ($value !== '') {
                $this->name = $value;
            }
        });
        $this->addEntry($lifeInput, function (Player $player, InputEntry $entry, string $value) : void {
            if ($value !== '') {
                $this->life = (int) $value;
            }
        });
        $this->addEntry($damageInput, function (Player $player, InputEntry $entry, string $value) : void {
            if ($value !== '') {
                $this->damage = (int) $value;
            }
        });
        $this->addEntry($scaleInput, function (Player $player, InputEntry $entry, string $value) : void {
            if ($value !== '') {
                $this->scale = (float) $value;
            }
        });
        $this->addEntry($speedInput, function (Player $player, InputEntry $entry, string $value) : void {
            if ($value !== '') {
                $this->speed = (float) $value;
            }
        });

        $this->addEntry($broadcastToggle, function (Player $player, ToggleEntry $entry, bool $value) : void {
            $this->broadcast = $value;
        });

        $this->addEntry($entityDropdown, function (Player $player, DropdownEntry $entry, int $value) use ($bossesNames): void {
            if ($this->life == null || $this->damage == null || $this->name == null || $this->scale == null || $this->speed == null) {
                $player->sendMessage(TextFormat::colorize('&cЗаполните все поля'));
                return;
            }
            if (!is_numeric($this->life) || !is_numeric($this->damage) || !is_numeric($this->scale) || !is_numeric($this->speed)) {
                $player->sendMessage(TextFormat::colorize('&cИспользуйте корректные числа'));
                return;
            }
            $this->entity = $bossesNames[$value];
            $this->openCreateBossDropsMenu($player);
        });
    }

    public function openCreateBossDropsMenu(Player $player): void
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setInventoryCloseListener(function (Player $player, $inventory): void {
            $items = $inventory->getContents();
            $entity = Main::stringToBoss($this->entity, $player->getLocation());
            $entity->setBossName($this->name);
            $entity->setMaxHealth($this->life);
            $entity->setEntityDamage($this->damage);
            $entity->setDrops($items);
            $entity->setSpeed($this->speed);
            $entity->spawnToAll();
            $entity->setScale($this->scale);
            if ($this->broadcast) {
                $position = $player->getPosition();
                foreach (Main::getInstance()->getConfig()->getNested("bossConfig.spawnBroadcast", []) as $msg) {
                    Server::getInstance()->broadcastMessage(TextFormat::colorize(
                    str_replace(
                        ["{bossName}", "{bossDamage}", "{bossScale}", "{bossSpeed}", "{bossLife}", "{bossType}", "{x}", "{y}", "{z}", "{world}"],
                        [$this->name, $this->damage, $this->scale, $this->speed, $this->life, $this->entity, $position->getFloorX(), $position->getFloorY(), $position->getFloorZ(), $position->getWorld()->getDisplayName()],
                        $msg))
                    );
                }
            }
            $player->sendMessage(TextFormat::colorize('&aБосс ' . $this->name . ' создан!'));
        });
        $menu->send($player, TextFormat::YELLOW . 'Добавьте дроп босса');
    }
}