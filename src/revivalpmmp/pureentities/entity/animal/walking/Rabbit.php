<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use pocketmine\entity\Ageable;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;

class Rabbit extends WalkingAnimal {
    const NETWORK_ID = 18;

    public $width = 0.5;
    public $height = 0.5;

    public function getSpeed() : float{
        return 1.2;
    }
    
    public function getName(){
        return "Rabbit";
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if($creature instanceof Player){
            return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::SEEDS && $distance <= 49;
        }
        return false;
    }

    public function getDrops(){
        return [];
    }

    public function getMaxHealth() {
        return 3;
    }

}
