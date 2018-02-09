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

namespace revivalpmmp\pureentities\features;

use revivalpmmp\pureentities\components\MobEquipment;

/**
 * Interface IntfCanEquip
 *
 * To be implemented by the entities that are capable of wearing armor / other stuff
 *
 * @package revivalpmmp\pureentities\features
 */
interface IntfCanEquip{

	/**
	 * Has to return the mob equipment class which should be initialized in initEntity
	 * @return MobEquipment
	 */
	public function getMobEquipment() : MobEquipment;

	/**
	 * Has to return either an empty array when nothing is picked up by the entity or an array with
	 * ItemIds that the entity may pick up
	 * @return array
	 */
	public function getPickupLoot() : array;

}