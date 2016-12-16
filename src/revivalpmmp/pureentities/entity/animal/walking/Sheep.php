<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\entity\Colorable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Sheep extends WalkingAnimal implements Colorable{
    const NETWORK_ID = 13;

    public $width = 1.45;
    public $height = 1.12;

    public function getName(){
        return "Sheep";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(8);
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if($creature instanceof Player){
            return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::SEEDS && $distance <= 49;
        }
        return false;
    }
    
    public function onUpdate($currentTick) {
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
                if($target->getItemInHand()->getId() === Item::SHEARS) {
                    $this->setDataProperty(self::DATA_INTERACTIVE_FLAG, self::DATA_TYPE_STRING, "Shear");
                }
            }
        }elseif(
            $target instanceof Vector3
            && $this->distance($target) <= 1
        ){
            $this->moveTime = 0;
        }
        return true;
    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::WHITE_WOOL, 1)];
        }
        return [];
    }

}