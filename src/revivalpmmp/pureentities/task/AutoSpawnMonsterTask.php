<?php

namespace revivalpmmp\pureentities\task;

use pocketmine\scheduler\PluginTask;
use revivalpmmp\pureentities\PureEntities;
use pocketmine\level\Level;
use pocketmine\level\generator\biome\Biome;

class AutoSpawnMonsterTask extends PluginTask {

	private $plugin;

    public function __construct(PureEntities $plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }
    
    public function onRun($currentTick){
        PureEntities::logOutput("AutoSpawnMonsterTask: onRun ($currentTick)",PureEntities::DEBUG);

        $entities = [];
        $valid = false;
        $entityTypeAsString = "none";
        foreach($this->plugin->getServer()->getLevels() as $level) {
            foreach($level->getPlayers() as $player){
                foreach($level->getEntities() as $entity) {
                    if($player->distance($entity) <= 25) {
                        $valid = true;
                        $entities[] = $entity;
                    }
                }

                if($valid) {
                    $x = $player->x + mt_rand(-20, 20);
                    $z = $player->z + mt_rand(-20, 20);
                    $y = $level->getHighestBlockAt($x, $z);
                } else {
                    PureEntities::logOutput("AutoSpawnMonsterTask: invalid",PureEntities::DEBUG);
                    return;
                }

                $correctedPosition = PureEntities::getFirstAirAbovePosition($x, $y, $z, $level); // returns the AIR block found upwards (it seems, highest block is not working :()

                $type = 32; // If $type is NOT set, it won't dump errors.
	            $biome = $level->getBiomeId($x, $z);
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
                        $entityTypeAsString = "Enderman";
                    } elseif($probability <= 25) {
                        $type = 35; // Spider
                        $entityTypeAsString = "Spider";
                    } elseif($probability <= 30) {
                        $type = 44; // Villager Zombie
                        $entityTypeAsString = "Zombie Villager";
                    } elseif($probability <= 55) {
                        $type = 32; // Zombie
                        $entityTypeAsString = "Zombie";
                    } elseif($probability <= 80) {
                        $type = 34; // Skeleton
                        $entityTypeAsString = "Skeleton";
                    } elseif($probability <= 95) {
                        $type = 33; // Creeper
                        $entityTypeAsString = "Creeper";
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
                        $entityTypeAsString = "Enderman";
                    } elseif($probability <= 25) {
                        $type = 35; // Spider
                        $entityTypeAsString = "Spider";
                    } elseif($probability <= 55) {
                        $type = 47; // Husk
                        $entityTypeAsString = "Husk";
                    } elseif($probability <= 80) {
                        $type = 34; // Skeleton
                        $entityTypeAsString = "Skeleton";
                    } elseif($probability <= 95) {
                        $type = 33; // Creeper
                        $entityTypeAsString = "Creeper";
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
                        $entityTypeAsString = "Enderman";
                    } elseif($probability <= 25) {
                        $type = 35; // Spider
                        $entityTypeAsString = "Spider";
                    } elseif($probability <= 30) {
                        $type = 44; // Villager Zombie
                        $entityTypeAsString = "Zombie Villager";
                    } elseif($probability <= 50) {
                        $type = 32; // Zombie
                        $entityTypeAsString = "Zombie";
                    } elseif($probability <= 70) {
                        $type = 34; // Skeleton
                        $entityTypeAsString = "Skeleton";
                    } elseif($probability <= 85) {
                        $type = 33; // Creeper
                        $entityTypeAsString = "Creeper";
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
                        $entityTypeAsString = "Enderman";
                    } elseif($probability <= 25) {
                        $type = 35; // Spider
                        $entityTypeAsString = "Spider";
                    } elseif($probability <= 30) {
                        $type = 44; // Villager Zombie
                        $entityTypeAsString = "Zombie Villager";
                    } elseif($probability <= 55) {
                        $type = 32; // Zombie
                        $entityTypeAsString = "Zombie";
                    } elseif($probability <= 80) {
                        $type = 46; // Stray
                        $entityTypeAsString = "Stray";
                    } elseif($probability <= 95) {
                        $type = 33; // Creeper
                        $entityTypeAsString = "Creeper";
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
                        $entityTypeAsString = "Zombie Pigman";
                    }elseif($probability <= 75) {
                        $type = 48; //Wither Skeleton
                        $entityTypeAsString = "Wither Skeleton";
                    }elseif($probability <= 85) {
                        $type = 41; // Ghast
                        $entityTypeAsString = "Ghast";
                    } elseif($probability <= 90) {
                        $type = 43; // Blaze
                        $entityTypeAsString = "Blaze";
                    } else {
                        //$type = 42; // Magma Cube (Has to be improved)
                    }
                }
                $time = $level->getTime() % Level::TIME_FULL; //why not subtract current from total?
                
                if(
                    !$player->distance($correctedPosition) <= 8 &&
                    $time >= 10900 && $time <= 17800
                ) {
                	if($this->plugin->checkEntityCount("Monster")) {
                        PureEntities::logOutput("AutoSpawnMonsterTask: scheduleCreatureSpawn (pos: $correctedPosition, type: $type, name: $entityTypeAsString)", PureEntities::NORM);
		                $this->plugin->scheduleCreatureSpawn($correctedPosition, $type, $level, "Monster");
	                }else{
                		$this->plugin->getLogger()->debug("The monster mob cap has been reached!");
	                }
                }
            }
        }
    }
}
