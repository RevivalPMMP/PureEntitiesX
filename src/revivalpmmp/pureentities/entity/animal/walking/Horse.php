<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\entity\Rideable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;

class Horse extends WalkingAnimal implements Rideable{
    const NETWORK_ID = 23;

    public $width = 1.4;
    public $height = 1.6;

    public function getName(){
        return "Horse";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(20);
        $this->setHealth(20);
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if($creature instanceof Player){
            return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::APPLE && $distance <= 49;
        }
        return false;
}

    public function getDrops(){
        return [Item::get(Item::LEATHER, 0, mt_rand(0, 2))];
    }

}
