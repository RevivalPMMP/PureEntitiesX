<?php

/**
 * PureEntitiesX: Mob AI Plugin for PMMP
 * Copyright (C)  2018 RevivalPMMP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace revivalpmmp\pureentities\entity\animal\flying;

use pocketmine\entity\Creature;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\entity\animal\FlyingAnimal;

class Bat extends FlyingAnimal{
	//TODO implement
	const NETWORK_ID = Data::NETWORK_IDS["bat"];

	public function initEntity() : void{
		parent::initEntity();
	}

	public function getSpeed() : float{
		return $this->speed;
	}

	public function getName() : string{
		return "Bat";
	}

	public function targetOption(Creature $creature, float $distance) : bool{
		return false;
	}

	public function getDrops() : array{
		return [];
	}

	public function getMaxHealth() : int{
		return 6;
	}

}
