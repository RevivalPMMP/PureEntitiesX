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
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\walking\IronGolem;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class IronGolemSpawner
 *
 * Spawn: Golems will spawn in a 16×6×16 area, centered between the 21 or more valid doors in a village if it has at
 * least 10 villagers. The chance of spawning is 1 in 7000 per game tick, which averages around one every six minutes.
 * Iron golems can spawn provided the blocks it spawns in are transparent and the block it spawns on top of has a
 * solid surface.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class IronGolemSpawner extends BaseSpawner{

	public function spawn(Position $pos, Player $player) : bool{
		if($this->spawnAllowedByProbability()){ // first check if spawn would be allowed, if not the other method calls make no sense at all
			$block = $pos->level->getBlock($pos); // because we get the air block, we need to subtract 1 from the y position
			$biomeId = $pos->level->getBiomeId($pos->x, $pos->z);

			PureEntities::logOutput($this->getClassNameShort() .
				": block is solid: " . $block->isSolid() . "[" . $block->getName() .
				"], spawnAllowedByEntityCount: " . $this->spawnAllowedByEntityCount($pos->getLevel()) .
				", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos),
				PureEntities::DEBUG);

			if($biomeId != Biome::HELL and // they don't spawn in nether!
				$block->isSolid() and // spawn only on solid blocks
				$this->spawnAllowedByEntityCount($pos->getLevel()) and // respect count in level
				$this->checkPlayerDistance($player, $pos)
			){ // distance to player has to be at least a configurable amount of blocks (atm 8!)
				$this->spawnEntityToLevel($pos, $this->getEntityNetworkId(), $pos->getLevel(), "Monster");
				PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos)", PureEntities::NORM);
				return true;
			}
		}else{
			PureEntities::logOutput($this->getClassNameShort() . ": spawn not allowed because probability denies spawn", PureEntities::DEBUG);
		}

		return false;
	}

	protected function getEntityNetworkId() : int{
		return IronGolem::NETWORK_ID;
	}

	protected function getEntityName() : string{
		return "IronGolem";
	}


}