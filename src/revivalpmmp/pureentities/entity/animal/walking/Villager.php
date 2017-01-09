<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;

class Villager extends WalkingAnimal{
    const NETWORK_ID = 15;

    public $width = 0.72;
    public $height = 1.8;

    public function getSpeed() : float{
        return 1.1;
    }
    
    public function getName(){
        return "Villager";
    }

    public function getDrops(){
        return [];
    }

    public function getMaxHealth() {
        return 10;
    }
}