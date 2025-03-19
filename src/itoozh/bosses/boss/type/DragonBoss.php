<?php

namespace itoozh\bosses\boss\type;

use itoozh\bosses\boss\BossEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class DragonBoss extends BossEntity
{
    protected float $gravity = 0;
    protected bool $isFlying = true;
    protected string $bossName = "Dragon Boss";

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2, 1, 1.8);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ENDER_DRAGON;
    }

    public function getName(): string
    {
        return $this->bossName;
    }
}
