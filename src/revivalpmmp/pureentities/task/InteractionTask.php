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

namespace revivalpmmp\pureentities\task;


use pocketmine\scheduler\Task;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

/**
 * Class InteractionTask
 *
 * This is a helper task which is executed each x ticks. It checks if any player is looking at
 * an entity. If so and the player can interact with the entity - the method "showButton" is
 * called at the entity itself.
 *
 * @package revivalpmmp\pureentities\task
 */
class InteractionTask extends Task{

	/**
	 * @var PureEntities
	 */
	private $plugin;

	public function __construct(PureEntities $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * Called when the task is executed
	 *
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick){
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			$entity = InteractionHelper::getEntityPlayerLookingAt($player, PluginConfiguration::getInstance()->getMaxInteractDistance(),
				PluginConfiguration::getInstance()->isInteractiveButtonCorrection());
			PureEntities::logOutput("InteractionTask: $player is looking at $entity", PureEntities::DEBUG);
			if($entity !== null and $entity instanceof IntfCanInteract){ // player is looking at an entity he can interact with
				$entity->showButton($player);
			}else{ // the player isn't looking at an entity
				InteractionHelper::displayButtonText("", $player);
			}
		}
	}


}