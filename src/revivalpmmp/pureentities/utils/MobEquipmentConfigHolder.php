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


namespace revivalpmmp\pureentities\utils;


use revivalpmmp\pureentities\config\mobequipment\EntityConfig;

class MobEquipmentConfigHolder{

	/**
	 * @var array
	 */
	private static $config = [];

	/**
	 * Returns mob equipment configuration for a specific entity name
	 *
	 * @param string $entityName
	 * @return null|EntityConfig
	 */
	public static function getConfig(string $entityName){
		// check if configuration already cached - if not create it and store it
		if(!array_key_exists($entityName, self::$config)){
			self::$config[$entityName] = new EntityConfig($entityName);
		}

		return self::$config[$entityName];
	}


}