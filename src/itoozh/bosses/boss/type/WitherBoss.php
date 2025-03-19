<?php

namespace itoozh\bosses\boss\type;

use itoozh\bosses\boss\BossEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class WitherBoss extends BossEntity
{
    protected float $gravity = 0;
    protected bool $isFlying = true;
    protected string $bossName = "Wither Boss";

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2, 1, 1.8);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::WITHER;
    }

    public function getName(): string
    {
        return $this->bossName;
    }
}
