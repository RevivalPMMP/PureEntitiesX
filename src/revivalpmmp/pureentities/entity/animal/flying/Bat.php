<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\FlyingAnimal;
use pocketmine\entity\Creature;

class Bat extends FlyingAnimal{
    //TODO: This isn't implemented yet
    const NETWORK_ID = Data::BAT;

    public $width = 0.3;
    public $height = 0.3;

    public function getName(){
        return "Bat";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(6);
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        return false;
    }

    public function getDrops(){
        return [];
    }

}
