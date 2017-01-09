<?php

namespace revivalpmmp\pureentities\entity\animal\flying;

use revivalpmmp\pureentities\entity\animal\FlyingAnimal;
use pocketmine\entity\Creature;

class Bat extends FlyingAnimal{

    const NETWORK_ID = 19;

    public $width = 0.3;
    public $height = 0.3;

    public function getName(){
        return "Bat";
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        return false;
    }

    public function getDrops(){
        return [];
    }

    public function getMaxHealth() {
        return 6;
    }

}
