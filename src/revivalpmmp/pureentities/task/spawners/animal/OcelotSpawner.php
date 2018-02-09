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


use pocketmine\block\Grass;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\generator\object\Tree;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\walking\Ocelot;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class OcelotSpawner
 *
 * Spawn: In Jungle Biomes, on Grass or leaf blocks that are above sea level, any light level.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class OcelotSpawner extends BaseSpawner{

	public function spawn(Position $pos, Player $player) : bool{

		if($this->spawnAllowedByProbability()){ // first check if spawn would be allowed, if not the other method calls make no sense at all
			$block = $pos->level->getBlock($pos); // because we get the air block, we need to subtract 1 from the y position
			$biomeId = $pos->level->getBiomeId($pos->x, $pos->z);

			PureEntities::logOutput($this->getClassNameShort() . ": aboveSeaLevel: " . $this->isAboveSeaLevel($pos) . ", block is instanceof Grass or Tree" . ($block instanceof Grass or $block instanceof Tree) .
				"[" . $block->getName() . "], spawnAllowedByEntityCount: " . $this->spawnAllowedByEntityCount($pos->getLevel()) .
				", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos) . ", biomeId matches: " . $biomeId == Biome::FOREST,
				PureEntities::DEBUG);

			if($this->isAboveSeaLevel($pos) and // spawns only above sea level
				($block instanceof Grass || $block instanceof Tree) and // and only on grass blocks or leaf block (we don't have leaf blocks atm, so we use tree!)
				$this->spawnAllowedByEntityCount($pos->getLevel()) and // respect count in level
				$this->checkPlayerDistance($player, $pos) and // distance to player has to be at least a configurable amount of blocks (atm 8!)
				$biomeId == Biome::FOREST
			){ // normally, they spawn in jungle. but as jungle biome is not implemented yet, we spawn them in forest
				$this->spawnEntityToLevel($pos, $this->getEntityNetworkId(), $pos->getLevel(), "Animal");
				PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos)", PureEntities::NORM);
				return true;
			}
		}else{
			PureEntities::logOutput($this->getClassNameShort() . ": spawn not allowed because probability denies spawn", PureEntities::DEBUG);
		}

		return false;
	}

	protected function getEntityNetworkId() : int{
		return Ocelot::NETWORK_ID;
	}

	protected function getEntityName() : string{
		return "Ocelot";
	}

	private function isAboveSeaLevel(Position $pos){
		return $pos->y >= 62; // sea level is 62 ...
	}


}