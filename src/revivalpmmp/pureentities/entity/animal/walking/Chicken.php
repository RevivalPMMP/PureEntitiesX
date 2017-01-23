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

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;
use revivalpmmp\pureentities\data\Data;

class Chicken extends WalkingAnimal{
    const NETWORK_ID = Data::CHICKEN;

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
