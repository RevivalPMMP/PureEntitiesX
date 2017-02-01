<?php

/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2016 RevivalPMMP

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

        $this->setMaxHealth(10);
        $this->setHealth(10);
    }
    
    public function getDrops(){
        return [];
    }
}
