<?php

namespace itoozh\bosses\boss\type;

use itoozh\bosses\boss\BossEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class SpiderBoss extends BossEntity
{
    protected string $bossName = "Spider Boss";

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2, 1, 1.8);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::SPIDER;
    }

    public function getName(): string
    {
        return $this->bossName;
    }
}
