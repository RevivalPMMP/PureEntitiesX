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

namespace revivalpmmp\pureentities\event;

use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\level\Level;
use pocketmine\level\Position;
use revivalpmmp\pureentities\PureEntities;

class CreatureSpawnEvent extends PluginEvent implements Cancellable{
	public static $handlerList = null;

	private $pos;
	private $entityid;
	private $level;
	private $type;

	/**
	 * @param PureEntities $plugin
	 * @param Position     $pos
	 * @param int          $entityid
	 * @param Level        $level
	 * @param string       $type
	 */
	public function __construct(PureEntities $plugin, Position $pos, int $entityid, Level $level, string $type){
		parent::__construct($plugin);
		PureEntities::logOutput("New Creature Spawn Event! Entity ID = $entityid and Type $type");
		$this->pos = $pos;
		$this->entityid = $entityid;
		$this->level = $level;
		$this->type = $type;
	}

	/**
	 * Returns the position the entity is about to be spawned at.
	 * @return Position
	 */
	public function getPosition(){
		return $this->pos;
	}

	/**
	 * Returns the Network ID from the entity about to be spawned.
	 * @return int
	 */
	public function getEntityId() : int{
		return $this->entityid;
	}

	/**
	 * Returns the level the entity is about to spawn in.
	 * @return Level
	 */
	public function getLevel(){
		return $this->level;
	}

	/**
	 * Returns the type of the entity about to be spawned. (Animal/Monster)
	 * @return string
	 */
	public function getType() : string{
		return $this->type;
	}
}