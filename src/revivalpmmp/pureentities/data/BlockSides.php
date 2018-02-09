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

namespace revivalpmmp\pureentities\data;

use pocketmine\block\Block;

class BlockSides{

	private static $initialized = false;

	/**
	 * @var array
	 */
	private static $sides;


	private static function init(){
		self::$sides[] = Block::SIDE_SOUTH;
		self::$sides[] = Block::SIDE_WEST;
		self::$sides[] = Block::SIDE_NORTH;
		self::$sides[] = Block::SIDE_EAST;
		self::$initialized = true;
	}

	/**
	 * Returns sides mapping to direction.
	 *
	 * @return array
	 */
	public static function getSides(){
		if(!self::$initialized){
			self::init();
		}
		return self::$sides;
	}

}