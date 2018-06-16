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


use pocketmine\scheduler\Task;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PureEntities;

/**
 * Class SetTamedOwnerTask
 *
 * This task is used to set the owner of a tamed entity after logging in. This is needed because
 * sometimes the spawn order spawns the tamed entity first and then the player. So we need this
 * to set the owner for tamed entities.
 *
 * @package revivalpmmp\pureentities\task\delayed
 */
class SetTamedOwnerTask extends Task{

	/**
	 * @var PureEntities
	 */
	private $plugin;

	/**
	 * @var IntfTameable
	 */
	private $tameableEntity;

	/**
	 * ShowMobEquipmentTask constructor.
	 * @param PureEntities $plugin
	 * @param IntfTameable $tameableEntity
	 */
	public function __construct(PureEntities $plugin, IntfTameable $tameableEntity){
		$this->plugin = $plugin;
		$this->tameableEntity = $tameableEntity;
	}

	/**
	 * Executed when delayed task's time is over.
	 *
	 * This method sends the equipment of all equipped entities to the joined player.
	 *
	 * @param $currentTick
	 */
	public function onRun(int $currentTick){
		$this->tameableEntity->mapOwner();
	}

}