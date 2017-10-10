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

namespace revivalpmmp\pureentities\task\spawners\animal;


use pocketmine\block\Solid;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\walking\Donkey;
use revivalpmmp\pureentities\entity\animal\walking\Horse;
use revivalpmmp\pureentities\entity\animal\walking\Mule;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class HorseSpawner
 *
 * Spawn: Plains and savanna
 *
 * Horses and donkeys only spawn in plains and savannas in herds of 2-6.
 * 10% of herds will be donkeys. For horses, all combinations of color and markings are equally likely.
 * All members of the herd will have the same color, but markings may vary.
 * 20% of the individual horses or donkeys will spawn as babies.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class HorseSpawner extends BaseSpawner {

    public function __construct() {
        parent::__construct();
    }

    public function spawn(Position $pos, Player $player): bool {

        if ($this->spawnAllowedByProbability()) {

            $block = $pos->level->getBlock($pos);
            $biomeId = $pos->level->getBiomeId($pos->x, $pos->z);

            // how many horses to spawn (we spawn herds)
            $herdSize = mt_rand(2, 6);
            // how many of the herd size is a donkey?
            $spawnDonkey = false;
            if ($herdSize > 4) {
                $spawnDonkey = mt_rand(0, 1) == 1 ? true : false;
            }

            PureEntities::logOutput($this->getClassNameShort() . ": isDay: " . $this->isDay($pos->getLevel()) . ", block is instanceof Solid" . $block instanceof Solid .
                "[" . $block->getName() . "], spawnAllowedByEntityCount: " . $this->spawnAllowedByHorseCount($pos->getLevel(), $herdSize) .
                ", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos) . ", herdSize: $herdSize, withDonkey: $spawnDonkey", PureEntities::DEBUG);


            if ($this->isSpawnAllowedByBlockLight($player, $pos, -1, 9) and // check block light when enabled
                $this->isDay($pos->level) and // spawn only at day
                $this->spawnAllowedByHorseCount($pos->level, $herdSize) and // check entity count for horse, donkey and mule
                ($biomeId == Biome::PLAINS or $biomeId == Biome::TAIGA) and // spawn only allowed in PLAINS or SAVANNA (as there's no savanna atm we use taiga)
                $block instanceof Solid and // must be a solid block
                $this->checkPlayerDistance($player, $pos)
            ) { // player distance must be ok
                // spawn the herd ...
                if ($spawnDonkey) {
                    $herdSize--;
                    $this->spawnEntityToLevel($pos, Donkey::NETWORK_ID, $pos->getLevel(), "Animal");
                    PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos) Donkey", PureEntities::NORM);
                }
                for ($i = 0; $i < $herdSize; $i++) {
                    $this->spawnEntityToLevel($pos, Horse::NETWORK_ID, $pos->getLevel(), "Animal");
                    PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos) Horse", PureEntities::NORM);
                }
                return true;
            }

        } else {
            PureEntities::logOutput($this->getClassNameShort() . ": Spawn not allowed because of probability", PureEntities::DEBUG);
        }
        return false;
    }

    protected function getEntityNetworkId(): int {
        return Horse::NETWORK_ID;
    }

    protected function getEntityName(): string {
        return "Horse";
    }


    // ---- horse spawner specific -----

    /**
     * This method is overridden from BaseSpawner because this spawner is a little special ;)
     *
     * @param Level $level
     * @param int $herdSize
     * @return bool
     */
    protected function spawnAllowedByHorseCount(Level $level, int $herdSize): bool {
        if ($this->maxSpawn <= 0) {
            return false;
        }
        $count = 0;
        foreach ($level->getEntities() as $entity) { // check all entities in given level
            if ($entity->isAlive() and !$entity->isClosed() and
                ($entity::NETWORK_ID == Horse::NETWORK_ID or $entity::NETWORK_ID == Donkey::NETWORK_ID or $entity::NETWORK_ID == Mule::NETWORK_ID)
            ) { // count only alive, not closed and desired entities
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