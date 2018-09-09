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

namespace revivalpmmp\pureentities\config\mobequipment;

use revivalpmmp\pureentities\config\mobequipment\helper\ArmorTypeChances;
use revivalpmmp\pureentities\config\mobequipment\helper\WearChances;
use revivalpmmp\pureentities\config\mobequipment\helper\WearPickupChance;
use revivalpmmp\pureentities\PureEntities;

/**
 * Class EntityConfig
 * @package revivalpmmp\pureentities\config\mobequipment
 */
class EntityConfig{

	/**
	 * @var string
	 */
	private $entityName;

	/**
	 * @var PureEntities
	 */
	private $plugin;

	/**
	 * @var WearPickupChance
	 */
	private $wearPickupChance;

	/**
	 * @var WearChances
	 */
	private $wearChances;

	/**
	 * @var ArmorTypeChances
	 */
	private $armorTypeChances;

	public function __construct(string $entityName){
		$this->entityName = $entityName;
		$this->plugin = PureEntities::getInstance();
		$this->init();
	}

	private function init(){
		$this->wearPickupChance = new WearPickupChance($this->entityName);
		$this->wearChances = new WearChances($this->entityName);
		$this->armorTypeChances = new ArmorTypeChances($this->entityName);
	}

	/**
	 * @return string
	 */
	public function getEntityName() : string{
		return $this->entityName;
	}

	/**
	 * @return WearPickupChance
	 */
	public function getWearPickupChance() : WearPickupChance{
		return $this->wearPickupChance;
	}

	/**
	 * @return WearChances
	 */
	public function getWearChances() : WearChances{
		return $this->wearChances;
	}

	/**
	 * @return ArmorTypeChances
	 */
	public function getArmorTypeChances() : ArmorTypeChances{
		return $this->armorTypeChances;
	}


}