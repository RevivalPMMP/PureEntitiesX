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
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\entity\Creature;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\features\IntfCanPanic;

class Ocelot extends WalkingAnimal implements IntfCanPanic {
    const NETWORK_ID = Data::OCELOT;

    public $width = 0.6;
    public $length = 0.8;
    public $height = 0.8;
    public $speed = 1.2;

    public function getSpeed(): float {
        return $this->speed;
    }

    public function getNormalSpeed(): float {
        return 1.2;
    }

    public function getPanicSpeed(): float {
        return 1.4;
    }

    public function getName(): string {
        return "Ocelot";
    }

    public function targetOption(Creature $creature, float $distance): bool {
        if ($creature instanceof Player) {
            return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::RAW_FISH && $distance <= 49;
        }
        return false;
    }

    public function getDrops(): array {
        return [];
    }

    public function getMaxHealth() : int{
        return 10;
    }

    public function getKillExperience(): int {
        return mt_rand(1, 3);
    }

}
