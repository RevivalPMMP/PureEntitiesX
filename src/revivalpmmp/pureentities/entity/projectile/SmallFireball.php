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

namespace revivalpmmp\pureentities\entity\projectile;


use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use revivalpmmp\pureentities\data\Data;

class SmallFireball extends FireBall{
	const NETWORK_ID = Data::NETWORK_IDS["small_fireball"];

	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null, bool $critical = false){
		$this->height = Data::HEIGHTS[self::NETWORK_ID];
		$this->width = Data::WIDTHS[self::NETWORK_ID];
		parent::__construct($level, $nbt, $shootingEntity, $critical);
	}

}