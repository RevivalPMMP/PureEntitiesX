<?php

namespace milk\entitymanager\entity\animal;

use milk\entitymanager\entity\WalkingEntity;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class WalkingAnimal extends WalkingEntity implements Animal{

    public function getSpeed() : float{
        return 0.7;
    }

    public function initEntity(){
        parent::initEntity();

        if($this->getDataProperty(self::DATA_AGEABLE_FLAGS) === null){
            $this->setDataProperty(self::DATA_AGEABLE_FLAGS, self::DATA_TYPE_BYTE, 0);
        }
    }

    public function isBaby(){
        return $this->getDataFlag(self::DATA_AGEABLE_FLAGS, self::DATA_FLAG_BABY);
    }

    public function onUpdate($currentTick){
        if(!$this->isAlive()){
            if(++$this->deadTicks >= 23){
                $this->close();
                return false;
            }
            return true;
        }

        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
        $this->entityBaseTick($tickDiff);

        $target = $this->updateMove($tickDiff);
        if($target instanceof Player){
            if($this->distance($target) <= 2){
                $this->pitch = 22;
                $this->x = $this->lastX;
                $this->y = $this->lastY;
                $this->z = $this->lastZ;
            }
        }elseif($target instanceof Vector3){
            if($this->distance($target) <= 1) $this->moveTime = 0;
        }
        return true;
    }

}
