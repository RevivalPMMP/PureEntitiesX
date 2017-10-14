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
use revivalpmmp\pureentities\data\Data;

class Rabbit extends WalkingAnimal { //TODO create JumpingAnimal class
    const NETWORK_ID = Data::RABBIT;

    public function getSpeed(): float {
        return $this->speed;
    }

    public function getName(): string {
        return "Rabbit";
    }

    public function initEntity() {
        parent::initEntity();
        $this->width = 0.5;
        $this->height = 0.5;
        $this->speed = 2;
        $this->setMaxHealth(3);
        $this->setHealth(3);
    }

    public function getDrops(): array {
        return [];
    }

    public function getKillExperience(): int {
        // breeding drop 1-4 (not implemented yet)
        return mt_rand(1, 3);
    }

}
