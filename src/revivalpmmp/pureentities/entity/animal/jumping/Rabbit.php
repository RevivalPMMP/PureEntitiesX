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

namespace revivalpmmp\pureentities\entity\animal\jumping;

use revivalpmmp\pureentities\entity\animal\JumpingAnimal;
use revivalpmmp\pureentities\data\Data;

class Rabbit extends JumpingAnimal{
	const NETWORK_ID = Data::NETWORK_IDS["rabbit"];

	public function getSpeed() : float{
		return $this->speed;
	}

	public function getName() : string{
		return "Rabbit";
	}

	public function initEntity(){
		parent::initEntity();
		$this->width = Data::WIDTHS[self::NETWORK_ID];
		$this->height = Data::HEIGHTS[self::NETWORK_ID];
		$this->speed = 2;
		$this->setMaxHealth(3);
		$this->setHealth(3);
	}

	public function getDrops() : array{
		return [];
	}

	/**
	 * Returns the amount of XP this mob will drop on death.
	 * @return int
	 */
	public function getXpDropAmount() : int{
		// breeding drop 1-4 (not implemented yet)
		return mt_rand(1, 3);
	}

}
