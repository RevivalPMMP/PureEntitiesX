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

namespace revivalpmmp\pureentities\task\delayed;


use pocketmine\Player;
use pocketmine\scheduler\Task;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use revivalpmmp\pureentities\features\IntfCanEquip;
use revivalpmmp\pureentities\PureEntities;

/**
 * Class ShowMobEquipmentTask
 *
 * This task is executed to send equipment packet to newly logged in players - this is needed because
 * atm there's no other possibility (as we're missing some data properties). We've to work with mob equipment
 * packets. As PlayerJoin event is sent by server but it's not possible to immediately send packet, because
 * chunk is not fully loaded when player joins.
 *
 * @package revivalpmmp\pureentities\task\delayed
 */
class ShowMobEquipmentTask extends Task{

	/**
	 * @var PureEntities
	 */
	private $plugin;

	private $playerJoined;

	/**
	 * ShowMobEquipmentTask constructor.
	 * @param PureEntities $plugin
	 * @param Player       $playerJoined
	 */
	public function __construct(PureEntities $plugin, Player $playerJoined){
		$this->plugin = $plugin;
		$this->playerJoined = $playerJoined;
	}

	/**
	 * Executed when delayed task's time is over.
	 *
	 * This method sends the equipment of all equipped entities to the joined player.
	 *
	 * @param $currentTick
	 */
	public function onRun(int $currentTick){
		foreach($this->playerJoined->getLevel()->getEntities() as $entity){
			if($entity->isAlive() and !$entity->isClosed() and $entity instanceof IntfCanEquip and $entity instanceof WalkingMonster){
				$entity->getMobEquipment()->sendEquipmentUpdate($this->playerJoined);
			}
		}
	}

}