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
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\walking\Zombie;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class ZombieSpawner
 *
 * Spawn: In the Overworld, zombies spawn in groups of 4 at a light level of 7 or less. In desert biomes, all zombies
 * exposed to the sky will have an 80% chance to be replaced by husks, a zombie variant. Zombies that are not husks
 * have a 5% chance to spawn as a zombie villager while all zombie variants also have a 5% chance to spawn as a baby
 * zombie type. Baby zombies have an additional 5% chance of spawning as a chicken jockey.
 *
 * Light level of 7 or less and 1×2 space anywhere but transparent blocks (half blocks, glass, TNT etc).
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class ZombieSpawner extends BaseSpawner {

    public function spawn(Position $pos, Player $player): bool {
        if ($this->spawnAllowedByProbability()) { // first check if spawn would be allowed, if not the other method calls make no sense at all
            $block = $pos->level->getBlock($pos); // because we get the air block, we need to subtract 1 from the y position
            $biomeId = $pos->level->getBiomeId($pos->x, $pos->z);
            $herdSize = mt_rand(1, 4); // generate between 1 and 4 zombies in a group (normally, 4 are spawned, but in this case ...)

            // TODO: spawn husk when in desert biome (will be done later!) - therefore we need biomeId

            PureEntities::logOutput($this->getClassNameShort() .
                ": isNight: " . !$this->isDay($pos->getLevel()) .
                ", block is solid: " . $block->isSolid() . "[" . $block->getName() . "]" .
                ", spawnAllowedByEntityCount: " . $this->spawnAllowedByEntityCount($pos->getLevel()) .
                ", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos),
                PureEntities::DEBUG);

            if ($biomeId != Biome::HELL and // they don't spawn in nether!
                $this->isSpawnAllowedByBlockLight($player, $pos, 7) and // check block light when enabled
                !$this->isDay($pos->getLevel()) and // only spawn at night ...
                $block->isSolid() and // spawn only on solid blocks
                $this->spawnAllowedByZombieCount($pos->getLevel(), $herdSize) and // respect count in level
                $this->checkPlayerDistance($player, $pos)
            ) { // distance to player has to be at least a configurable amount of blocks (atm 8!)
                for ($i = 0; $i < $herdSize; $i++) {
                    $isBaby = mt_rand(0, 100) <= 5; // a 5 percent chance to spawn a baby zombie
                    $this->spawnEntityToLevel($pos, $this->getEntityNetworkId(), $pos->getLevel(), "Monster", $isBaby);
                    PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos, baby: $isBaby)", PureEntities::NORM);
                }
                return true;
            }
        } else {
            PureEntities::logOutput($this->getClassNameShort() . ": spawn not allowed because probability denies spawn", PureEntities::DEBUG);
        }

        return false;
    }

    protected function getEntityNetworkId(): int {
        return Zombie::NETWORK_ID;
    }

    protected function getEntityName(): string {
        return "Zombie";
    }

    /**
     * Special method because we spawn herds of zombies
     *
     * @param Level $level
     * @param int $herdSize
     * @return bool
     */
    protected function spawnAllowedByZombieCount(Level $level, int $herdSize): bool {
        if ($this->maxSpawn <= 0) {
            return false;
        }
        $count = 0;
        foreach ($level->getEntities() as $entity) { // check all entities in given level
            if ($entity->isAlive() and !$entity->isClosed() and $entity::NETWORK_ID == $this->getEntityNetworkId()) { // count only alive, not closed and desired entities
                $count++;
            }
        }

        PureEntities::logOutput($this->getClassNameShort() . ": got count of  $count entities living for " . $this->getEntityName(), PureEntities::DEBUG);

        if (($count + $herdSize) < $this->maxSpawn) {
            return true;
        }
        return false;
    }


}