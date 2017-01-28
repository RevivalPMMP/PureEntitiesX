<?php

/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2017 RevivalPMMP

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>. */

namespace revivalpmmp\pureentities\entity\animal\jumping;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;
use revivalpmmp\pureentities\data\Data;

class Rabbit extends WalkingAnimal{ //TODO create JumpingAnimal class
    const NETWORK_ID = Data::RABBIT;

    public $width = 0.5;
    public $height = 0.5;

    public function getSpeed() : float{
        return 1.2;
    }
    
    public function getName(){
        return "Rabbit";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(3);
        $this->setHealth(3);
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

}
