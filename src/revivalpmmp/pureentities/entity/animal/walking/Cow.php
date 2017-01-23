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

class Cow extends WalkingAnimal{
    const NETWORK_ID = Data::COW;

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
