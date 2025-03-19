<?php

namespace itoozh\bosses\command;
use itoozh\bosses\boss\type\WitchBoss;
use itoozh\bosses\form\CreateBossForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class BossCommand extends Command
{
    public function __construct()
    {
        parent::__construct("boss", "Создать босса", "Используйте: /boss", ["bosses"]);
        $this->setPermission("boss.command.use");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) return;
        $sender->sendForm(new CreateBossForm());
    }
}
