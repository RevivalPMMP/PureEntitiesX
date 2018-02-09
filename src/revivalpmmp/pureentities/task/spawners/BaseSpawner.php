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

namespace revivalpmmp\pureentities\task\spawners;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

/**
 * Class BaseSpawner
 *
 * A base spawner class which all spawner classes extend from
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
abstract class BaseSpawner{

	const MIN_DISTANCE_TO_PLAYER = 8; // in blocks

	/** @var  PureEntities $plugin */
	protected $plugin;

	/** @var int $maxSpawn */
	protected $maxSpawn = -1;

	/** @var int $probability */
	private $probability = 1; // 1 percent chance by default

	/**
	 * BaseSpawner constructor.
	 */
	public function __construct(){
		$this->maxSpawn = PureEntities::getInstance()->getConfig()->getNested("max-spawn." . strtolower($this->getEntityName()), 0);
		$this->probability = PureEntities::getInstance()->getConfig()->getNested("probability." . strtolower($this->getEntityName()), 0);
		PureEntities::logOutput("BaseSpawner: got " . $this->probability . "% spawn probability for " . $this->getEntityName() . " spawns with a maximum number of " . $this->maxSpawn . " living entities per level", PureEntities::DEBUG);
	}


	/**
	 * Checks with the help of given level, if entity spawn is allowed by configuration or if entity spawn
	 * may exhaust max spawn for the entity
	 *
	 * @param Level $level
	 * @return bool
	 */
	protected function spawnAllowedByEntityCount(Level $level) : bool{
		if($this->maxSpawn <= 0){
			return false;
		}
		$count = 0;
		foreach($level->getEntities() as $entity){ // check all entities in given level
			if($entity->isAlive() and !$entity->isClosed() and $entity::NETWORK_ID == $this->getEntityNetworkId()){ // count only alive, not closed and desired entities
				$count++;
			}
		}

		PureEntities::logOutput("BaseSpawner: got count of $count entities living for " . $this->getEntityName(), PureEntities::DEBUG);

		if($count < $this->maxSpawn){
			return true;
		}
		return false;
	}

	/**
	 * Returns true when the spawn probability matches
	 *
	 * @return bool
	 */
	protected function spawnAllowedByProbability() : bool{
		return $this->probability > 0 ? (mt_rand(0, 100) <= $this->probability) : false;
	}

	/**
	 * Checks and returns true if the spawn point distance relative to the player is at least
	 * 8 fields. If not, this method return false. Do not spawn when this function returns false.
	 *
	 * @param Player   $player
	 * @param Position $pos
	 * @return bool
	 */
	protected function checkPlayerDistance(Player $player, Position $pos){
		return $player->distance($pos) > self::MIN_DISTANCE_TO_PLAYER;
	}

	/**
	 * Checks with the help of the time in the level, if it is night or day.
	 *
	 * @param Level $level
	 * @return bool
	 */
	protected function isDay(Level $level){
		$time = $level->getTime() % Level::TIME_FULL;
		return ($time <= Level::TIME_SUNSET || $time >= Level::TIME_SUNRISE);
	}

	/**
	 * @return string
	 */
	protected function getClassNameShort() : string{
		$classNameWithNamespace = get_class($this);
		return substr($classNameWithNamespace, strrpos($classNameWithNamespace, '\\') + 1);
	}

	/**
	 * Use THIS method for spawning mobs! This adds the proper height to the spawn position. Otherwise
	 * the entity may get stuck in the ground or suffers suffocation
	 *
	 * @param Position $pos
	 * @param int      $entityId
	 * @param Level    $level
	 * @param string   $type
	 * @param bool     $isBaby
	 * @return bool
	 */
	protected function spawnEntityToLevel(Position $pos, int $entityId, Level $level, string $type, bool $isBaby = false) : bool{
		$pos->y += Data::HEIGHTS[$entityId];
		return PureEntities::getInstance()->scheduleCreatureSpawn($pos, $entityId, $level, $type, $isBaby) !== null;
	}

	/**
	 * Just a helper method
	 *
	 * @param Player   $player
	 * @param Position $pos
	 * @return int
	 */
	protected function getBlockLightAt(Player $player, Position $pos){
		if($player !== null){
			return $player->getLevel()->getBlockLightAt($pos->x, $pos->y, $pos->z);
		}
		return -1; // unknown
	}

	/**
	 * Just a helper method
	 *
	 * @param Player   $player
	 * @param Position $pos
	 * @return int
	 */
	protected function getSkyLightAt(Player $player, Position $pos){
		if($player !== null){
			return $player->getLevel()->getBlockSkyLightAt($pos->x, $pos->y, $pos->z);
		}
		return -1; // unknown
	}

	/**
	 * Returns true when spawning is allowed by block light at the given position. This method
	 * considers if the block light checking is enabled via configuration
	 *
	 * @param Player   $player
	 * @param Position $pos
	 * @param int      $maxBlockLight
	 * @param int      $minBlockLight
	 * @return bool
	 */
	protected function isSpawnAllowedByBlockLight(Player $player, Position $pos, int $maxBlockLight = -1, int $minBlockLight = -1){
		if($maxBlockLight > -1 and $minBlockLight > -1){
			PureEntities::logOutput("Unable to execute isSpawnAllowedByBlockLight() because both are set: maxBlockLight and minBlockLight. Check your code!", PureEntities::WARN);
			return false;
		}
		if(PluginConfiguration::getInstance()->getUseBlockLightForSpawn()){
			if($maxBlockLight > -1 and $this->getBlockLightAt($player, $pos) <= $maxBlockLight){
				return true;
			}else if($minBlockLight > -1 and $this->getBlockLightAt($player, $pos) >= $minBlockLight){
				return true;
			}
			return false;
		}
		return true;
	}


	// ---- abstract functions declaration ----
	protected abstract function getEntityNetworkId() : int;

	protected abstract function getEntityName() : string;

	public abstract function spawn(Position $pos, Player $player) : bool;

}