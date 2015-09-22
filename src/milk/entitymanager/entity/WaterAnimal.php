<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Ageable;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class WaterAnimal extends FlyEntity implements Ageable{
    //TODO: This isn't implemented yet

    public function initEntity(){
        if($this->getDataProperty(self::DATA_AGEABLE_FLAGS) === null){
            $this->setDataProperty(self::DATA_AGEABLE_FLAGS, self::DATA_TYPE_BYTE, 0);
        }
        parent::initEntity();
    }

    public function isBaby(){
        return $this->getDataFlag(self::DATA_AGEABLE_FLAGS, self::DATA_FLAG_BABY);
    }

    public function updateTick(){
        if(!$this->isAlive()){
            if(++$this->deadTicks >= 23) $this->close();
            return;
        }

        --$this->moveTime;
        $target = $this->updateMove();
        if($target instanceof Vector3){
            if((($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2) <= 1) $this->moveTime = 0;
        }
        $this->entityBaseTick();
    }

    public function targetOption(Player $player, $distance){
        return false;
    }

}