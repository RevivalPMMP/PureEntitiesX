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

namespace revivalpmmp\pureentities\task;

use pocketmine\scheduler\PluginTask;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\entity\monster\Monster;
use revivalpmmp\pureentities\entity\animal\Animal;

class AutoDespawnTask extends PluginTask {

    private $plugin;

    const NO_PLAYER_CHECK = 2;
    const IN_RANGE = 1;
    const OUT_OF_RANGE = 3;

    public function __construct(PureEntities $plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) {
        PureEntities::logOutput("AutoDespawnTask: onRun ($currentTick)", PureEntities::DEBUG);
        $despawnable = [];
        foreach ($this->plugin->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
                $isTameable = $entity instanceof IntfTameable;
                /**
                 * @var $entity IntfTameable|BaseEntity
                 */
                if (!$isTameable or ($isTameable and !$entity->isTamed())) { // do NOT despawn tamed entities!
                    $despawnable[$entity->getId()] = self::NO_PLAYER_CHECK;
                    foreach ($level->getPlayers() as $player) {
                        if ($player->distance($entity) <= 32) {
                            $despawnable[$entity->getId()] = self::IN_RANGE;
                        } elseif ($player->distance($entity) >= 128) {
                            $despawnable[$entity->getId()] = self::OUT_OF_RANGE; // 3 means that it's more than 128 blocks away
                        }
                    }
                    if ($despawnable[$entity->getId()] === self::NO_PLAYER_CHECK) { // no player range check for this entity
                        $probability = mt_rand(1, 100);
                        if ($probability === 1) {
                            if ($entity instanceof Monster) {
                                PureEntities::logOutput("AutoDespawnTask: close entity (id: " . $entity->getId() . ", name:" . $entity->getNameTag() . ")", PureEntities::DEBUG);
                                $entity->close();
                            }
                        }
                    } elseif ($despawnable[$entity->getId()] === self::OUT_OF_RANGE && ($entity instanceof Animal || $entity instanceof Monster)) {
                        PureEntities::logOutput("AutoDespawnTask: close entity (id: " . $entity->getId() . ", name:" . $entity->getNameTag() . ")", PureEntities::DEBUG);
                        $entity->close();
                    }
                }

            }
        }
    }
}