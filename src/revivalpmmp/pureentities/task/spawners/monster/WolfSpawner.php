<?php

/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2017 RevivalPMMP

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>. */

namespace revivalpmmp\pureentities\task\spawners\monster;


use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\walking\Wolf;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class WolfSpawner
 *
 * Spawn: Wolves only spawn naturally in forest, taiga, mega taiga, cold taiga, and cold taiga M biomes. In the Pocket
 * Edition they only spawn naturally in taiga biomes and their variants.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class WolfSpawner extends BaseSpawner {

    public function spawn(Position $pos, Player $player): bool {
        if ($this->spawnAllowedByProbability()) { // first check if spawn would be allowed, if not the other method calls make no sense at all
            $biomeId = $pos->level->getBiomeId($pos->x, $pos->z);

            PureEntities::logOutput($this->getClassNameShort() .
                ": isBiomeOK: " . ($biomeId == Biome::TAIGA) .
                ", spawnAllowedByEntityCount: " . $this->spawnAllowedByEntityCount($pos->getLevel()) .
                ", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos),
                PureEntities::DEBUG);

            if ($biomeId == Biome::TAIGA and // biome must be taiga
                $this->spawnAllowedByEntityCount($pos->getLevel()) and // respect count in level
                $this->checkPlayerDistance($player, $pos)
            ) { // distance to player has to be at least a configurable amount of blocks (atm 8!)
                $this->spawnEntityToLevel($pos, $this->getEntityNetworkId(), $pos->getLevel(), "Monster");
                PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos)", PureEntities::NORM);
                return true;
            }
        } else {
            PureEntities::logOutput($this->getClassNameShort() . ": spawn not allowed because probability denies spawn", PureEntities::DEBUG);
        }

        return false;
    }

    protected function getEntityNetworkId(): int {
        return Wolf::NETWORK_ID;
    }

    protected function getEntityName(): string {
        return "Wolf";
    }

}