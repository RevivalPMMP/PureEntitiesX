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
use revivalpmmp\pureentities\PureEntities;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\level\generator\biome\Biome;

class AutoSpawnMonsterTask extends PluginTask {

	private $plugin;

    public function __construct(PureEntities $plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }
    
    public function onRun($currentTick){
        
        $entities = [];
        $valid = false;
        foreach($this->plugin->getServer()->getLevels() as $level) {
            foreach($level->getPlayers() as $player){
                foreach($level->getEntities() as $entity) {
                    if($player->distance($entity) <= 25) {
                        $valid = true;
                        $entities[] = $entity;
                    }
                }
        
                if($valid && count($entities) <= 20 && $this->plugin->getConfig()->get("spawnmonsters") == true) {
                    $x = $player->x + mt_rand(-20, 20);
                    $z = $player->z + mt_rand(-20, 20);
                    $pos = new Position(
                        $x,
                        ($y = $level->getHighestBlockAt($x, $z) + 1),
                        $z,
                        $level
                    );
                } else {
                    return;
                }
                
                $type = 32; // If $type is NOT set, it won't dump errors.
                if($level->getBiomeId($x, $z) === null) {
                    $biome = 1;
                } else {
                    $biome = $level->getBiomeId($x, $z);
                }
                $probability = mt_rand(1, 100);
                
                /*
                 * Plains | (Birch) Forest | (Small) Mountains Biome Monster Generator
                 * Entities:
                 * - Zombie             25%
                 * - Villager Zombie    5%
                 * - Skeleton           25%
                 * - Spider             15%
                 * - Enderman           10%
                 * - Witch              5%
                 * - Creeper            15%
                 */
                if($biome === Biome::PLAINS || $biome === Biome::FOREST || Biome::BIRCH_FOREST || Biome::MOUNTAINS || Biome::SMALL_MOUNTAINS) {
                    if($probability <= 10) {
                        $type = 38; // Enderman
                    } elseif($probability <= 25) {
                        $type = 35; // Spider
                    } elseif($probability <= 30) {
                        $type = 44; // Villager Zombie
                    } elseif($probability <= 55) {
                        $type = 32; // Zombie
                    } elseif($probability <= 80) {
                        $type = 34; // Skeleton
                    } elseif($probability <= 95) {
                        $type = 33; // Creeper
                    } else {
                        //$type = 45; // Witch (Yet to be implemented)
                    }
                }
                
                /*
                 * Desert Biome Monster Generator
                 * Entities:
                 * - Husk               30%
                 * - Skeleton           25%
                 * - Spider             15%
                 * - Enderman           10%
                 * - Witch              5%
                 * - Creeper            15%
                 */
                elseif($biome === Biome::DESERT) {
                    if($probability <= 10) {
                        $type = 38; // Enderman
                    } elseif($probability <= 25) {
                        $type = 35; // Spider
                    } elseif($probability <= 55) {
                        $type = 47; // Husk 
                    } elseif($probability <= 80) {
                        $type = 34; // Skeleton
                    } elseif($probability <= 95) {
                        $type = 33; // Creeper
                    } else {
                        //$type = 45; // Witch (Yet to be implemented)
                    }
                }
                
                /*
                 * Swamp Biome Monster Generator
                 * Entities:
                 * - Zombie             20%
                 * - Villager Zombie    5%
                 * - Skeleton           20%
                 * - Spider             15%
                 * - Enderman           10%
                 * - Witch              5%
                 * - Creeper            15%
                 * - Slime              10%
                 */
                elseif($biome === Biome::SWAMP) {
                    if($probability <= 10) {
                        $type = 38; // Enderman
                    } elseif($probability <= 25) {
                        $type = 35; // Spider
                    } elseif($probability <= 30) {
                        $type = 44; // Villager Zombie
                    } elseif($probability <= 50) {
                        $type = 32; // Zombie
                    } elseif($probability <= 70) {
                        $type = 34; // Skeleton
                    } elseif($probability <= 85) {
                        $type = 33; // Creeper
                    } elseif($probability <= 95) {
                        //$type = 37; // Slime (Improvements needed)
                    } else {
                        //$type = 45; // Witch (Yet to be implemented)
                    }
                }
                
                /*
                 * Taiga | Ice Plains Biome Monster Generator
                 * Entities:
                 * - Stray              25%
                 * - Zombie             25%
                 * - Zombie Villager    5%
                 * - Spider             15%
                 * - Enderman           10%
                 * - Witch              5%
                 * - Creeper            15%
                 */
                elseif($biome === Biome::TAIGA || $biome === Biome::ICE_PLAINS) {
                    if($probability <= 10) {
                        $type = 38; // Enderman
                    } elseif($probability <= 25) {
                        $type = 35; // Spider
                    } elseif($probability <= 30) {
                        $type = 44; // Villager Zombie
                    } elseif($probability <= 55) {
                        $type = 32; // Zombie
                    } elseif($probability <= 80) {
                        $type = 46; // Stray
                    } elseif($probability <= 95) {
                        $type = 33; // Creeper
                    } else {
                        //$type = 45; // Witch (Yet to be implemented)
                    }
                }
                
                /*
                 * Ocean | River Biome Monster Generator
                 * Entities:
                 * - Guardian?          100%
                 */
                elseif($biome === Biome::RIVER || $biome === Biome::OCEAN) {
                    //$type = 49; // Guardian (Should it be kept vanilla or should we add some more action?)
                }
                
                /*
                 * Hell Biome Monster Generator
                 * Entities:
                 * - Zombie Pigman      65%
                 * - Ghast              10%            
                 * - Magma Cube         10%
                 * - Blaze              5%
                 * - Wither Skeleton    10%
                 */
                elseif($biome === Biome::HELL) {
                    if($probability <= 65) {
                        $type = 36; // Zombie Pigman
                    }elseif($probability <= 75) {
                        $type = 48; //Wither Skeleton
                    }elseif($probability <= 85) {
                        $type = 41; // Ghast
                    } elseif($probability <= 90) {
                        $type = 43; // Blaze
                    } else {
                        //$type = 42; // Magma Cube (Has to be improved)
                    }
                }
                $time = $level->getTime() % Level::TIME_FULL;
                
                if(
                    !$player->distance($pos) <= 8 &&
                    $time >= 10900 && $time <= 17800
                ) {
                    $this->plugin->scheduleCreatureSpawn($pos, $type, $level, "Monster");
                }
            }
        }
    }
}