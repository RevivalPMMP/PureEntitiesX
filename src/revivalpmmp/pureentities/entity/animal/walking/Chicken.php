<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Chicken extends WalkingAnimal{
    const NETWORK_ID = 10;

    public $width = 0.4;
    public $height = 0.7;
    public $eyeHeight = 0.7;

    public function getName(){
        return "Chicken";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(4);
        $this->setHealth(4);
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if($creature instanceof Player){
            return $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::SEEDS && $distance <= 49;
        }
        return false;
    }

    public function getDrops(){
        $drops = [];
        array_push($drops, Item::get(Item::FEATHER, 0, mt_rand(0, 2)));
        if ($this->isOnFire()) {
          array_push($drops, Item::get(Item::COOKED_CHICKEN, 0, 1));
        } else {
          array_push($drops, Item::get(Item::RAW_CHICKEN, 0, 1));
        }
        return $drops;
    }

}
