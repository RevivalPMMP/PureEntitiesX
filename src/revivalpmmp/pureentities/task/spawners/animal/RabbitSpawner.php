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

namespace revivalpmmp\pureentities\task\spawners\animal;


use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\walking\Rabbit;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class RabbitSpawner
 *
 * Spawn: Rabbits naturally spawn in deserts, flower forests, taiga, mega taiga, cold taiga, ice plains, ice mountains,
 * ice spikes, and the "hills" and "M" variants of these biomes. They spawn in groups of two or three; one adult and
 * one or two babies. They have different skins that depend on the biome.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class RabbitSpawner extends BaseSpawner{

	public function __construct(){
		parent::__construct();
	}

	public function spawn(Position $pos, Player $player) : bool{

		if($this->spawnAllowedByProbability()){
			// how many rabbits to spawn (we spawn herds)
			$herdSize = mt_rand(2, 3);


			$biomeId = $pos->level->getBiomeId($pos->x, $pos->z);

			$spawnAllowedByEntityCount = $this->spawnAllowedByRabbitCount($pos->getLevel(), $herdSize);
			$biomeOk = ($biomeId == Biome::DESERT or $biomeId == Biome::FOREST or $biomeId == Biome::TAIGA or $biomeId == Biome::PLAINS or $biomeId == Biome::BIRCH_FOREST or $biomeId == Biome::ICE_PLAINS);
			$playerDistanceOk = $this->checkPlayerDistance($player, $pos);

			PureEntities::logOutput($this->getClassNameShort() . ": isDay: " . $this->isDay($pos->getLevel()) .
				", spawnAllowedByEntityCount: " . $spawnAllowedByEntityCount .
				", biomeOk: " . $biomeOk .
				", playerDistanceOK: " . $playerDistanceOk .
				", herdSize: $herdSize", PureEntities::DEBUG);


			if($this->isSpawnAllowedByBlockLight($player, $pos, -1, 9) and // check block light when enabled
				$this->isDay($pos->level) and // spawn only at day
				$spawnAllowedByEntityCount and // check entity count for rabbits
				$biomeOk and // respect spawn biomes
				$playerDistanceOk){ // player distance must be ok

				// Temporary debug test
				PureEntities::logOutput("Conditional Check Passed. Calling spawnEntityToLevel with position $pos");

				// spawn 1 adult rabbit and the rest is baby rabbit
				$this->spawnEntityToLevel($pos, $this->getEntityNetworkId(), $pos->getLevel(), "Animal");
				PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos) as adult", PureEntities::NORM);

				// spawn the rest as baby (not implemented yet)
				for($i = 0; $i < ($herdSize - 1); $i++){
					$this->spawnEntityToLevel($pos, $this->getEntityNetworkId(), $pos->getLevel(), "Animal", true);
					PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos) as baby", PureEntities::NORM);
				}
				return true;
			}else{
				PureEntities::logOutput($this->getClassNameShort() . ": Spawn not allowed because of conditional check. (BlockLight, Daytime, Count, Biome, Player Distance)", PureEntities::DEBUG);
			}

		}else{
			PureEntities::logOutput($this->getClassNameShort() . ": Spawn not allowed because of probability", PureEntities::DEBUG);
		}
		return false;
	}

	protected function getEntityNetworkId() : int{
		return Rabbit::NETWORK_ID;
	}

	protected function getEntityName() : string{
		return "Rabbit";
	}


	// ---- rabbit spawner specific -----

	/**
	 * Special method because we spawn herds of rabbits (at least 2 of them)
	 *
	 * @param Level $level
	 * @param int   $herdSize
	 * @return bool
	 */
	protected function spawnAllowedByRabbitCount(Level $level, int $herdSize) : bool{
		if($this->maxSpawn <= 0){
			return false;
		}
		$count = 0;
		foreach($level->getEntities() as $entity){ // check all entities in given level
			if($entity->isAlive() and !$entity->isClosed() and $entity::NETWORK_ID == Rabbit::NETWORK_ID){ // count only alive, not closed and desired entities
				$count++;
			}
		}

		PureEntities::logOutput($this->getClassNameShort() . ": got count of  $count entities living for " . $this->getEntityName(), PureEntities::DEBUG);

		if(($count + $herdSize) < $this->maxSpawn){
			return true;
		}
		return false;
	}

}