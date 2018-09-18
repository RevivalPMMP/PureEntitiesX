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

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;

class Villager extends WalkingAnimal{
	const NETWORK_ID = Data::NETWORK_IDS["villager"];

	public function initEntity() : void{
		parent::initEntity();
		$this->speed = 1.1;
	}

	public function getName() : string{
		return "Villager";
	}

	public function getDrops() : array{
		return [];
	}

	public function getMaxHealth() : int{
		return 10;
	}

	public function updateXpDropAmount() : void{
		// Villagers don't drop any xp unless "willing" (breeding) or trading.
		//TODO: Update this when Villagers get breeding and trading updates.
	}

}
