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

namespace revivalpmmp\pureentities\config\mobequipment\helper;

use revivalpmmp\pureentities\PureEntities;

/**
 * Class ArmorTypeChances
 * @package revivalpmmp\pureentities\config\mobequipment\helper
 */
class ArmorTypeChances{

	private $leather = 0;

	private $gold = 0;

	private $chain = 0;

	private $iron = 0;

	private $diamond = 0;

	public function __construct(string $entityName){
		$plugin = PureEntities::getInstance();
		if($plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".armor-types.leather") !== null){
			$this->leather = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".armor-types.leather");
		}
		if($plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".armor-types.gold") !== null){
			$this->gold = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".armor-types.gold");
		}
		if($plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".armor-types.chain") !== null){
			$this->chain = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".armor-types.chain");
		}
		if($plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".armor-types.iron") !== null){
			$this->iron = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".armor-types.iron");
		}
		if($plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".armor-types.") !== null){
			$this->diamond = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".armor-types.diamond");
		}

		PureEntities::logOutput("ArmorTypeChances successfully loaded for $entityName", PureEntities::NORM);
	}

	/**
	 * @return int|mixed
	 */
	public function getLeather(){
		return $this->leather;
	}

	/**
	 * @return int|mixed
	 */
	public function getGold(){
		return $this->gold;
	}

	/**
	 * @return int|mixed
	 */
	public function getChain(){
		return $this->chain;
	}

	/**
	 * @return int|mixed
	 */
	public function getIron(){
		return $this->iron;
	}

	/**
	 * @return int|mixed
	 */
	public function getDiamond(){
		return $this->diamond;
	}


}