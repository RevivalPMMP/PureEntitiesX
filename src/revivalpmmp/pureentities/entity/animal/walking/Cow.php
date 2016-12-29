<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Cow extends WalkingAnimal{
    const NETWORK_ID = 11;

    public $width = 0.9;
    public $height = 1.3;
    public $eyeHeight = 1.2;

    public function getName(){
        return "Cow";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(10);
        $this->setHealth(10);
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if($creature instanceof Player){
            return $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::WHEAT && $distance <= 49;
        }
        return false;
    }

    public function getDrops(){
        $drops = [];
        array_push($drops, Item::get(Item::LEATHER, 0, mt_rand(0, 2)));
        if ($this->isOnFire()) {
          array_push($drops, Item::get(Item::COOKED_BEEF, 0, mt_rand(1, 3)));
        } else {
          array_push($drops, Item::get(Item::RAW_BEEF, 0, mt_rand(1, 3)));
        }
        return $drops;
    }
}
