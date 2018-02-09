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

namespace revivalpmmp\pureentities\task\spawners\monster;


use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\walking\PigZombie;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class PigZombieSpawner
 *
 * Spawn:
 * 1. Zombie pigmen spawn in groups of 4 in the Nether at any light level.
 * 2. When a Nether portal block in the Overworld receives a block tick, there is a small chance (1/2000 on Easy,
 *    2/2000 on Normal, and 3/2000 on Hard) it will spawn a zombie pigman on the portal frame beneath it. If a zombie
 *    pigman spawned in this way does not leave the portal it spawns on, it will never be teleported to the Nether.
 * 3. A zombie pigman will spawn when lightning strikes within 4 blocks of a pig. If the player is riding the pig when
 *    lightning hits it, a zombie pigman will appear on top of the player. Even if the pig is a baby, the zombie pigman
 *    will still be full sized.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class PigZombieSpawner extends BaseSpawner{

	public function spawn(Position $pos, Player $player) : bool{
		if($this->spawnAllowedByProbability()){ // first check if spawn would be allowed, if not the other method calls make no sense at all
			$block = $pos->level->getBlock($pos); // because we get the air block, we need to subtract 1 from the y position
			$biomeId = $pos->level->getBiomeId($pos->x, $pos->z);

			PureEntities::logOutput($this->getClassNameShort() .
				": block is solid: " . $block->isSolid() . "[" . $block->getName() . "]" .
				", biome is hell: " . ($biomeId == Biome::HELL) .
				", spawnAllowedByEntityCount: " . $this->spawnAllowedByPigZombieCount($pos->getLevel(), 4) .
				", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos),
				PureEntities::DEBUG);

			if($block->isSolid() and // spawn only on solid blocks
				$biomeId == Biome::HELL and // only spawn in nether (hell?)
				$this->spawnAllowedByPigZombieCount($pos->getLevel(), 4) and // respect count in level
				$this->checkPlayerDistance($player, $pos)
			){ // distance to player has to be at least a configurable amount of blocks (atm 8!)
				for($i = 0; $i < 4; $i++){
					$this->spawnEntityToLevel($pos, $this->getEntityNetworkId(), $pos->getLevel(), "Monster");
					PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos)", PureEntities::NORM);
				}
				return true;
			}
		}else{
			PureEntities::logOutput($this->getClassNameShort() . ": spawn not allowed because probability denies spawn", PureEntities::DEBUG);
		}

		return false;
	}

	protected function getEntityNetworkId() : int{
		return PigZombie::NETWORK_ID;
	}

	protected function getEntityName() : string{
		return "PigZombie";
	}

	// ---- pigzombie spawner specific -----

	/**
	 * Special method because we spawn herds of rabbits (at least 2 of them)
	 *
	 * @param Level $level
	 * @param int   $herdSize
	 * @return bool
	 */
	protected function spawnAllowedByPigZombieCount(Level $level, int $herdSize) : bool{
		if($this->maxSpawn <= 0){
			return false;
		}
		$count = 0;
		foreach($level->getEntities() as $entity){ // check all entities in given level
			if($entity->isAlive() and !$entity->isClosed() and $entity::NETWORK_ID == $this->getEntityNetworkId()){ // count only alive, not closed and desired entities
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