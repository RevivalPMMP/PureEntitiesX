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

namespace revivalpmmp\pureentities\task\spawners;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\flying\Bat;
use revivalpmmp\pureentities\entity\animal\swimming\Squid;
use revivalpmmp\pureentities\entity\animal\walking\Chicken;
use revivalpmmp\pureentities\entity\animal\walking\Cow;
use revivalpmmp\pureentities\entity\animal\walking\Donkey;
use revivalpmmp\pureentities\entity\animal\walking\Horse;
use revivalpmmp\pureentities\entity\animal\walking\Mooshroom;
use revivalpmmp\pureentities\entity\animal\walking\Mule;
use revivalpmmp\pureentities\entity\animal\walking\Ocelot;
use revivalpmmp\pureentities\entity\animal\walking\Pig;
use revivalpmmp\pureentities\entity\animal\walking\Rabbit;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;
use revivalpmmp\pureentities\entity\animal\walking\Villager;
use revivalpmmp\pureentities\entity\monster\flying\Blaze;
use revivalpmmp\pureentities\entity\monster\flying\Ghast;
use revivalpmmp\pureentities\entity\monster\jumping\MagmaCube;
use revivalpmmp\pureentities\entity\monster\jumping\Slime;
use revivalpmmp\pureentities\entity\monster\walking\CaveSpider;
use revivalpmmp\pureentities\entity\monster\walking\Creeper;
use revivalpmmp\pureentities\entity\monster\walking\Enderman;
use revivalpmmp\pureentities\entity\monster\walking\Husk;
use revivalpmmp\pureentities\entity\monster\walking\IronGolem;
use revivalpmmp\pureentities\entity\monster\walking\PigZombie;
use revivalpmmp\pureentities\entity\monster\walking\Silverfish;
use revivalpmmp\pureentities\entity\monster\walking\Skeleton;
use revivalpmmp\pureentities\entity\monster\walking\SnowGolem;
use revivalpmmp\pureentities\entity\monster\walking\Spider;
use revivalpmmp\pureentities\entity\monster\walking\Stray;
use revivalpmmp\pureentities\entity\monster\walking\WitherSkeleton;
use revivalpmmp\pureentities\entity\monster\walking\Wolf;
use revivalpmmp\pureentities\entity\monster\walking\Zombie;
use revivalpmmp\pureentities\entity\monster\walking\ZombieVillager;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

/**
 * Class BaseSpawner
 *
 * A base spawner class which all spawner classes extend from
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
abstract class BaseSpawner {

    // stores all heights of mobs for spawning reasons
    const HEIGHTS = array(
        Bat::NETWORK_ID => 0.3,
        Squid::NETWORK_ID => 0.95,
        Chicken::NETWORK_ID => 0.7,
        Cow::NETWORK_ID => 1.3,
        Donkey::NETWORK_ID => 1.6,
        Horse::NETWORK_ID => 1.6,
        Mooshroom::NETWORK_ID => 1.12,
        Mule::NETWORK_ID => 1.4,
        Ocelot::NETWORK_ID => 0.9,
        Pig::NETWORK_ID => 1.12,
        Rabbit::NETWORK_ID => 0.5,
        Sheep::NETWORK_ID => 1.8,
        Villager::NETWORK_ID => 1.8,
        Blaze::NETWORK_ID => 1.8,
        Ghast::NETWORK_ID => 4,
        MagmaCube::NETWORK_ID => 1.2,
        Slime::NETWORK_ID => 1.2,
        CaveSpider::NETWORK_ID => 0.8,
        Creeper::NETWORK_ID => 1.8,
        Enderman::NETWORK_ID => 2.8,
        Husk::NETWORK_ID => 2,
        IronGolem::NETWORK_ID => 2.1,
        PigZombie::NETWORK_ID => 1.8,
        Silverfish::NETWORK_ID => 0.2,
        Skeleton::NETWORK_ID => 1.8,
        SnowGolem::NETWORK_ID => 1.8,
        Spider::NETWORK_ID => 1.12,
        Stray::NETWORK_ID => 2,
        WitherSkeleton::NETWORK_ID => 1.8,
        Wolf::NETWORK_ID => 0.9,
        Zombie::NETWORK_ID => 1.8,
        ZombieVillager::NETWORK_ID => 1.8
    );

    const MIN_DISTANCE_TO_PLAYER = 8; // in blocks

    /** @var  PureEntities $plugin */
    protected $plugin;

    /** @var int $maxSpawn */
    protected $maxSpawn = -1;

    /** @var int $probability */
    private $probability = 1; // 1 percent chance by default

    /**
     * BaseSpawner constructor.
     */
    public function __construct() {
        $this->maxSpawn = PureEntities::getInstance()->getConfig()->getNested("max-spawn." . strtolower($this->getEntityName()), 0);
        $this->probability = PureEntities::getInstance()->getConfig()->getNested("probability." . strtolower($this->getEntityName()), 0);
        PureEntities::logOutput("BaseSpawner: got " . $this->probability . "% spawn probability for " . $this->getEntityName() . " spawns with a maximum number of " . $this->maxSpawn . " living entities per level", PureEntities::DEBUG);
    }


    /**
     * Checks with the help of given level, if entity spawn is allowed by configuration or if entity spawn
     * may exhaust max spawn for the entity
     *
     * @param Level $level
     * @return bool
     */
    protected function spawnAllowedByEntityCount(Level $level): bool {
        if ($this->maxSpawn <= 0) {
            return false;
        }
        $count = 0;
        foreach ($level->getEntities() as $entity) { // check all entities in given level
            if ($entity->isAlive() and !$entity->isClosed() and $entity::NETWORK_ID == $this->getEntityNetworkId()) { // count only alive, not closed and desired entities
                $count++;
            }
        }

        PureEntities::logOutput("BaseSpawner: got count of $count entities living for " . $this->getEntityName(), PureEntities::DEBUG);

        if ($count < $this->maxSpawn) {
            return true;
        }
        return false;
    }

    /**
     * Returns true when the spawn probability matches
     *
     * @return bool
     */
    protected function spawnAllowedByProbability(): bool {
        return $this->probability > 0 ? (mt_rand(0, 100) <= $this->probability) : false;
    }

    /**
     * Checks and returns true if the spawn point distance relative to the player is at least
     * 8 fields. If not, this method return false. Do not spawn when this function returns false.
     *
     * @param Player $player
     * @param Position $pos
     * @return bool
     */
    protected function checkPlayerDistance(Player $player, Position $pos) {
        return $player->distance($pos) > self::MIN_DISTANCE_TO_PLAYER;
    }

    /**
     * Checks with the help of the time in the level, if it is night or day.
     *
     * @param Level $level
     * @return bool
     */
    protected function isDay(Level $level) {
        $time = $level->getTime() % Level::TIME_FULL;
        return ($time <= Level::TIME_SUNSET || $time >= Level::TIME_SUNRISE);
    }

    /**
     * @return string
     */
    protected function getClassNameShort(): string {
        $classNameWithNamespace = get_class($this);
        return substr($classNameWithNamespace, strrpos($classNameWithNamespace, '\\') + 1);
    }

    /**
     * Use THIS method for spawning mobs! This adds the proper height to the spawn position. Otherwise
     * the entity may get stuck in the ground or suffers suffocation
     *
     * @param Position $pos
     * @param int $entityId
     * @param Level $level
     * @param string $type
     * @param bool $isBaby
     * @return bool
     */
    protected function spawnEntityToLevel(Position $pos, int $entityId, Level $level, string $type, bool $isBaby = false): bool {
        $pos->y += self::HEIGHTS[$entityId];
        return PureEntities::getInstance()->scheduleCreatureSpawn($pos, $entityId, $level, $type, $isBaby) !== null;
    }

    /**
     * Just a helper method
     *
     * @param Player $player
     * @param Position $pos
     * @return int
     */
    protected function getBlockLightAt(Player $player, Position $pos) {
        if ($player !== null) {
            return $player->getLevel()->getBlockLightAt($pos->x, $pos->y, $pos->z);
        }
        return -1; // unknown
    }

    /**
     * Just a helper method
     *
     * @param Player $player
     * @param Position $pos
     * @return int
     */
    protected function getSkyLightAt(Player $player, Position $pos) {
        if ($player !== null) {
            return $player->getLevel()->getBlockSkyLightAt($pos->x, $pos->y, $pos->z);
        }
        return -1; // unknown
    }

    /**
     * Returns true when spawning is allowed by block light at the given position. This method
     * considers if the block light checking is enabled via configuration
     *
     * @param Player $player
     * @param Position $pos
     * @param int $maxBlockLight
     * @param int $minBlockLight
     * @return bool
     */
    protected function isSpawnAllowedByBlockLight(Player $player, Position $pos, int $maxBlockLight = -1, int $minBlockLight = -1) {
        if ($maxBlockLight > -1 and $minBlockLight > -1) {
            PureEntities::logOutput("Unable to execute isSpawnAllowedByBlockLight() because both are set: maxBlockLight and minBlockLight. Check your code!", PureEntities::WARN);
            return false;
        }
        if (PluginConfiguration::getInstance()->getUseBlockLightForSpawn()) {
            if ($maxBlockLight > -1 and $this->getBlockLightAt($player, $pos) <= $maxBlockLight) {
                return true;
            } else if ($minBlockLight > -1 and $this->getBlockLightAt($player, $pos) >= $minBlockLight) {
                return true;
            }
            return false;
        }
        return true;
    }


    // ---- abstract functions declaration ----
    protected abstract function getEntityNetworkId(): int;

    protected abstract function getEntityName(): string;

    public abstract function spawn(Position $pos, Player $player): bool;

}