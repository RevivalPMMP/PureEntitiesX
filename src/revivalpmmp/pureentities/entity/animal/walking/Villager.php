<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use revivalpmmp\pureentities\data\Data;

class Villager extends WalkingAnimal{
    const NETWORK_ID = Data::VILLAGER;

    public $width = 0.72;
    public $height = 1.8;

    public function getSpeed() : float{
        return 1.1;
    }
    
    public function getName(){
        return "Villager";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(20);
    }
    
    public function getDrops(){
        return [];
    }
}
