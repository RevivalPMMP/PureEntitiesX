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


use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\walking\Skeleton;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class SkeletonSpawner
 *
 * Spawn: Skeletons can spawn almost anywhere in the Overworld at a light level of 7 or less. In the Nether, they can
 * also spawn in nether fortresses at a light level of 7 or less in place of a wither skeleton.
 *
 * Spiders have a 1% chance spawn with a skeleton riding them, creating a spider jockey.
 *
 * Skeleton riders spawn when a skeleton trap is triggered by a player. The skeletons ride on skeleton horses and are
 * equipped with enchanted bows and iron helmets, and have damage immunity for 3 seconds after spawning.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class SkeletonSpawner extends BaseSpawner {

    public function spawn(Position $pos, Player $player): bool {
        if ($this->spawnAllowedByProbability()) { // first check if spawn would be allowed, if not the other method calls make no sense at all
            $block = $pos->level->getBlock($pos); // because we get the air block, we need to subtract 1 from the y position

            PureEntities::logOutput($this->getClassNameShort() .
                ": isNight: " . !$this->isDay($pos->level) .
                ", isSolidBlock: " . $block->isSolid() . " [" . $block->getName() . "]" .
                ", spawnAllowedByEntityCount: " . $this->spawnAllowedByEntityCount($pos->getLevel()) .
                ", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos),
                PureEntities::DEBUG);

            if ($this->isSpawnAllowedByBlockLight($player, $pos, 7) and // check block light when enabled
                !$this->isDay($pos->level) and // spawn only at night
                $block->isSolid() and // block must be solid
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
        return Skeleton::NETWORK_ID;
    }

    protected function getEntityName(): string {
        return "Skeleton";
    }

}