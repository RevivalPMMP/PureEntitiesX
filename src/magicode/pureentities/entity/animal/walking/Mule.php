<?php

namespace magicode\pureentities\entity\animal\walking;

use magicode\pureentities\entity\animal\WalkingAnimal;
use pocketmine\entity\Rideable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Mule extends WalkingAnimal implements Rideable{
    const NETWORK_ID = 25;

    public $width = 1.3;
    public $height = 1.4;

    public function getName(){
        return "Mule";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(15);
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if($creature instanceof Player){
            return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::WHEAT && $distance <= 49;
        }
        return false;
}

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::LEATHER, 0, 2)];
        }
        return [];
    }

}
