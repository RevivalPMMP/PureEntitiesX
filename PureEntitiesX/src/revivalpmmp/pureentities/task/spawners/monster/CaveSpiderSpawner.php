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


use pocketmine\block\Stone;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\walking\CaveSpider;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class CaveSpiderSpawner
 *
 * Spawn:C ave spiders spawn from monster spawners in abandoned mineshaft.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class CaveSpiderSpawner extends BaseSpawner {

    public function spawn(Position $pos, Player $player): bool {
        if ($this->spawnAllowedByProbability()) { // first check if spawn would be allowed, if not the other method calls make no sense at all
            $block = $pos->level->getBlock($pos); // because we get the air block, we need to subtract 1 from the y position
            $biomeId = $pos->level->getBiomeId($pos->x, $pos->z);

            PureEntities::logOutput($this->getClassNameShort() .
                ": block is stone: " . ($block instanceof Stone) .
                "[" . $block->getName() . "], spawnAllowedByEntityCount: " . $this->spawnAllowedByEntityCount($pos->getLevel()) .
                ", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos),
                PureEntities::DEBUG);

            if ($biomeId != Biome::HELL and // they don't spawn in nether
                $block instanceof Stone and // spawn only on stones ...
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
        return CaveSpider::NETWORK_ID;
    }

    protected function getEntityName(): string {
        return "CaveSpider";
    }


}