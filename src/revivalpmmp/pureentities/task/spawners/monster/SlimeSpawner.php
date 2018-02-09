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
use revivalpmmp\pureentities\entity\monster\jumping\Slime;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class SlimeSpawner
 *
 * Spawn: Slimes spawn in the Overworld in specific chunks below layer 40 regardless of light levels. They can also
 * spawn in swamp biomes between layers 50 and 70 in light levels of 7 or less.
 * Slimes will not spawn within 24 blocks (spherical) of any player, and will despawn over time if no player is within
 * 32 blocks and instantly if no player is within 128 blocks.
 *  Slimes require two vertical transparent blocks[Verify] (e.g., air, signs, torches) to spawn in, with an opaque block
 * underneath. The space they spawn in must also be clear of solid obstructions and liquids.[Verify] Big slimes
 * require a 3×2½×3 space to spawn, small slimes require a 3×2×3 space, and tiny slimes require a 1×2×1 space
 * (or 1×1×1 if the upper block is not opaque).[1]
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class SlimeSpawner extends BaseSpawner{

	public function spawn(Position $pos, Player $player) : bool{
		if($this->spawnAllowedByProbability()){ // first check if spawn would be allowed, if not the other method calls make no sense at all
			$block = $pos->level->getBlock($pos); // because we get the air block, we need to subtract 1 from the y position
			$biomeId = $pos->level->getBiomeId($pos->x, $pos->z);

			$y = $pos->y;
			$spawnAllowedByLayer = false;
			$checkLightLevel = false;
			if($biomeId != Biome::SWAMP and $y <= 40){
				$spawnAllowedByLayer = true;
			}else if($biomeId == Biome::SWAMP and $y >= 50 and $y <= 70){
				$spawnAllowedByLayer = true;
				$checkLightLevel = true;
			}

			PureEntities::logOutput($this->getClassNameShort() .
				": spawnAllowedByLayer: $spawnAllowedByLayer" .
				", isNight: " . !$this->isDay($pos->getLevel()) .
				", block is solid: " . $block->isSolid() . "[" . $block->getName() .
				"], spawnAllowedByEntityCount: " . $this->spawnAllowedByEntityCount($pos->getLevel()) .
				", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos),
				PureEntities::DEBUG);

			if($biomeId != Biome::HELL and // they don't spawn in nether
				($checkLightLevel and $this->isSpawnAllowedByBlockLight($player, $pos, 7)) and // check block light when enabled
				$spawnAllowedByLayer and // respect layer for spawning
				!$this->isDay($pos->getLevel()) and // only spawn at night ...
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
		return Slime::NETWORK_ID;
	}

	protected function getEntityName() : string{
		return "Slime";
	}


}