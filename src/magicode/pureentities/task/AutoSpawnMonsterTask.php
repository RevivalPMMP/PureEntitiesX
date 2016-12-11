<?php

namespace magicode\pureentities\task;

use pocketmine\scheduler\PluginTask;
use magicode\pureentities\PureEntities;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\level\generator\biome\Biome;

class AutoSpawnMonsterTask extends PluginTask {

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
                    if($player->distance($entity) <= 20) {
                        $valid = true;
                    }
                }
            
                $entities[] = $entity;
        
                if($valid && count($entities) <= 30) {
                    $x = $player->x + mt_rand(-20, 20);
                    $z = $player->z + mt_rand(-20, 20);
                    $pos = new Position(
                        $x,
                        ($y = $level->getHighestBlockAt($x, $z) + 1),
                        $z,
                        $level
                    );
                }
                
                $biome = $level->getBiome($x, $z);
                $probability = mt_rand(1, 100);
                
                /*
                 * Plains | (Birch) Forest | (Small) Mountains Biome Entity Generator
                 * Entities:
                 * - Zombie
                 * - Villager Zombie
                 * - Skeleton
                 * - Spider
                 * - Enderman
                 * - Witch
                 * - Creeper
                 */
                if($biome === Biome::PLAINS || $biome === Biome::FOREST || Biome::BIRCH_FOREST || Biome::MOUNTAINS || Biome::SMALL_MOUNTAIN) {
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
                 * Desert Biome Entity Generator
                 * Entities:
                 * - Husk
                 * - Skeleton
                 * - Spider
                 * - Enderman
                 * - Witch
                 * - Creeper
                 */
                elseif($biome === Biome::DESERT) {
                    if($probability <= 10) {
                        $type = 38; // Enderman
                    } elseif($probability <= 25) {
                        $type = 35; // Spider
                    } elseif($probability <= 55) {
                        //$type = 47; // Husk (Yet to be implemented)
                    } elseif($probability <= 80) {
                        $type = 34; // Skeleton
                    } elseif($probability <= 95) {
                        $type = 33; // Creeper
                    } else {
                        //$type = 45; // Witch (Yet to be implemented)
                    }
                }
                
                /*
                 * Swamp Biome Entity Generator
                 * Entities:
                 * - Zombie
                 * - Villager Zombie
                 * - Skeleton
                 * - Spider
                 * - Enderman
                 * - Witch
                 * - Creeper
                 * - Slime
                 */
                if($biome === Biome::SWAMP) {
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
                        $type = 37; // Slime
                    } else {
                        //$type = 45; // Witch (Yet to be implemented)
                    }
                }
                
                /*
                 * Taiga | Ice Plains Biome Entity Generator
                 * Entities:
                 * - Stray
                 * - Zombie
                 * - Zombie Villager
                 * - Spider
                 * - Enderman
                 * - Witch
                 * - Creeper
                 */
                if($biome === Biome::TAIGA || $biome === Biome::ICE_PLAINS) {
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
                 * Ocean | River Biome Entity Generator
                 * Entities:
                 * - Guardian?
                 */
                if($biome === Biome::RIVER || $biome === Biome::OCEAN) {
                    if($probability <= 5) {
                        //$type = 49; // Guardian (Should it be kept vanilla or should we add some more action?)
                    }
                }
                
                $entity = PureEntities::create($type, $pos);
                $time = $level->getTime() % Level::TIME_FULL;
                
                if(
                    !$player->distance($entity) <= 8 &&
                    $level->getBlockLightAt($x, $y, $z) <= 7 &&
                    ($time >= Level::TIME_SUNSET && $time <= Level::TIME_SUNRISE)
                ) {
                    $entity->spawnToAll();
                }
            }
        }
    }
}
