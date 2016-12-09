<?php

namespace magicode\pureentities\entity\animal\walking;

use magicode\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Villager extends WalkingAnimal{
    const NETWORK_ID = 15;

public $width = 0.72;
    public $height = 1.8;

    public function getSpeed() : float{
        return 1.5;
    }
    public function getName(){
        return "Villager";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(10);
    }
    public function getDrops(){
        return [];
    }
}