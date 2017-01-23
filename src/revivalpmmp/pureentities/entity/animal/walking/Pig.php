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
use pocketmine\entity\Rideable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;
use revivalpmmp\pureentities\data\Data;

class Pig extends WalkingAnimal implements Rideable{
    const NETWORK_ID = Data::PIG;

    public $width = 1.45;
    public $height = 1.12;

    public function getName(){
        return "Pig";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(10);
        $this->setHealth(10);
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if($creature instanceof Player){
            return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::CARROT && $distance <= 49;
        }
        return false;
    }

    public function getDrops(){
        if ($this->isOnFire()) {
          return [Item::get(Item::COOKED_PORKCHOP, 0, mt_rand(1, 3))];
        } else {
          return [Item::get(Item::RAW_PORKCHOP, 0, mt_rand(1, 3))];
        }
    }

}
