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

use pocketmine\Server;
use revivalpmmp\pureentities\PureEntities;

/**
 * Class WearChances
 * @package revivalpmmp\pureentities\config\mobequipment\helper
 */
class WearChances{

	/**
	 * @var array
	 */
	private $helmet = [];

	/**
	 * @var array
	 */
	private $helmetChestplate = [];

	/**
	 * @var array
	 */
	private $helmetChestplateLeggings = [];

	/**
	 * @var array
	 */
	private $full = [];

	/**
	 * @var Server
	 */
	private $server;

	public function __construct(string $entityName){
		$plugin = PureEntities::getInstance();
		$this->helmet[0] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.helmet.easy");
		$this->helmet[1] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.helmet.normal");
		$this->helmet[2] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.helmet.hard");

		$this->helmetChestplate[0] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.helmet-chestplate.easy");
		$this->helmetChestplate[1] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.helmet-chestplate.normal");
		$this->helmetChestplate[2] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.helmet-chestplate.hard");

		$this->helmetChestplateLeggings[0] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.helmet-chestplate-leggings.easy");
		$this->helmetChestplateLeggings[1] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.helmet-chestplate-leggings.normal");
		$this->helmetChestplateLeggings[2] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.helmet-chestplate-leggings.hard");

		$this->full[0] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.full.easy");
		$this->full[1] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.full.normal");
		$this->full[2] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-chances.full.hard");

		$this->server = Server::getInstance();

		PureEntities::logOutput("WearChances successfully loaded for $entityName", PureEntities::NORM);
	}

	public function getHelmetChance(){
		return $this->getChance($this->helmet);
	}

	public function getHelmetChestplateChance(){
		return $this->getChance($this->helmetChestplate);
	}

	public function getHelmetChestplateLeggingsChance(){
		return $this->getChance($this->helmetChestplateLeggings);
	}

	public function getFullChance(){
		return $this->getChance($this->full);
	}

	/**
	 * Helper method
	 *
	 * @param array $arrayToCheck
	 * @return int|mixed
	 */
	private function getChance(array $arrayToCheck){
		$difficulty = $this->server->getDifficulty(); // 1 -easy, 2 - normal, 3-hard
		$chance = 0;

		if($difficulty > 0){
			return $arrayToCheck[$difficulty - 1];
		}

		return $chance;
	}
}