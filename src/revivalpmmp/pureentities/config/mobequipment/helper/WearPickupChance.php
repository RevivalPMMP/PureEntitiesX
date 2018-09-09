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
 * Class WearPickupChance
 * @package revivalpmmp\pureentities\config\mobequipment\helper
 */
class WearPickupChance{

	/**
	 * @var array
	 */
	private $canPickupLoot = [0,0,0];

	/**
	 * @var array
	 */
	private $armor = [0,0,0];

	/**
	 * @var array
	 */
	private $weapon = [0,0,0];

	/**
	 * @var Server
	 */
	private $server;

	public function __construct(string $entityName){
		$plugin = PureEntities::getInstance();
		if($plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.can-pickup-loot") !== null){
			$this->canPickupLoot[0] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.can-pickup-loot.easy");
			$this->canPickupLoot[1] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.can-pickup-loot.normal");
			$this->canPickupLoot[2] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.can-pickup-loot.hard");
		}
		if($plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.armor") !== null){
			$this->armor[0] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.armor.easy");
			$this->armor[1] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.armor.normal");
			$this->armor[2] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.armor.hard");
		}
		if($plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.weapon") !== null){
			$this->weapon[0] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.weapon.easy");
			$this->weapon[1] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.weapon.normal");
			$this->weapon[2] = $plugin->getConfig()->getNested("mob-equipment." . strtolower($entityName) . ".wear-pickup-chances.weapon.hard");
		}
		PureEntities::logOutput("WearPickupChance successfully loaded for $entityName");

		$this->server = Server::getInstance();
	}

	public function getCanPickupLootChance(){
		return $this->getChance($this->canPickupLoot);
	}

	public function getArmorChance(){
		return $this->getChance($this->armor);
	}

	public function getWeaponChance(){
		return $this->getChance($this->weapon);
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