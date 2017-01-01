<?php

namespace revivalpmmp\pureentities\task;

use pocketmine\scheduler\PluginTask;
use revivalpmmp\pureentities\PureEntities;
use pocketmine\level\Level;
use pocketmine\level\generator\biome\Biome;
use pocketmine\block\Grass;
use pocketmine\math\Vector3;

class AutoSpawnAnimalTask extends PluginTask {

	private $plugin;

    public function __construct(PureEntities $plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }
    
    public function onRun($currentTick){
        PureEntities::logOutput("AutoSpawnAnimalTask: onRun ($currentTick)",PureEntities::DEBUG);

        $entities = [];
        $valid = false;
        $water = false;
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
                    PureEntities::logOutput("AutoSpawnAnimalTask: invalid",PureEntities::DEBUG);
                    return;
                }
                
                $type = null;
                if($level->getBiomeId($x, $z) === null) {
                    $biome = Biome::PLAINS;
                } else {
                    $biome = $level->getBiomeId($x, $z);
                }
                $probability = mt_rand(1, 100);
                
                $correctedPosition = PureEntities::getFirstAirAbovePosition($x, $y, $z, $level); // returns the AIR block found upwards (it seems, highest block is not working :()
                $block = $level->getBlock(new Vector3($correctedPosition->x, ($correctedPosition->y - 1), $correctedPosition->z));

                PureEntities::logOutput("AutoSpawnAnimalTask: [block:" . $block->getName() . "] [pos:" . $correctedPosition->x . "," . $correctedPosition->y . "," . $correctedPosition->z . "]",PureEntities::DEBUG);
                
                /*
                 * Plains Biome Animal Generator
                 * Entities:
                 * - Chicken            20%
                 * - Cow                20%
                 * - Horse              5%
                 * - Pig                20%
                 * - Sheep              20%
                 * - Rabbit             15%
                 */
                if($biome === Biome::PLAINS) {
                    if($probability <= 20) {
                        $type = 10; // Chicken
                    } elseif($probability <= 40) {
                        $type = 11; // Cow
                    } elseif($probability <= 45) {
                        $type = 23; // Horse
                    } elseif($probability <= 65) {
                        $type = 12; // Pig
                    } elseif($probability <= 85) {
                        $type = 13; // Sheep
                    } else {
                        $type = 18; // Rabbit
                    }
                }
                
                /*
                 * (Birch) Forest | Swamp | (Small) Mountains Biome Animal Generator
                 * Entities:
                 * - Chicken            20%
                 * - Cow                20%
                 * - Pig                20%
                 * - Sheep              20%
                 * - Rabbit             20%
                 */
                elseif($biome === Biome::FOREST || $biome === Biome::SWAMP || $biome === Biome::BIRCH_FOREST || $biome === Biome::SMALL_MOUNTAINS || $biome === Biome::MOUNTAINS) {
                    if($probability <= 20) {
                        $type = 10; // Chicken
                    } elseif($probability <= 40) {
                        $type = 11; // Cow
                    } elseif($probability <= 60) {
                        $type = 12; // Pig
                    } elseif($probability <= 80) {
                        $type = 13; // Sheep
                    } else {
                        $type = 18; // Rabbit
                    }
                }
                
                /*
                 * Desert | Swamp Biome Animal Generator
                 * Entities:
                 * - Rabbit             100%
                 */
                elseif($biome === Biome::DESERT) {
                    $type = 18; // Rabbit
                }
                
                /*
                 * Taiga | Ice Plains Biome Animal Generator
                 * Entities:
                 * - Wolf               20%
                 * - Cow                30%
                 * - Pig                25%
                 * - Chicken            25%
                 */
                elseif($biome === Biome::TAIGA || $biome === Biome::ICE_PLAINS) {
                    if($probability <= 20) {
                        $type = 14; // Wolf
                    } elseif($probability <= 50) {
                        $type = 11; // Cow
                    } elseif($probability <= 75) {
                        $type = 12; // Pig
                    } else {
                        $type = 10; // Zombie
                    } 
                }
                
                /*
                 * Ocean | River Biome Animal Generator
                 * Entities:
                 * - Squid              100%
                 */
                elseif($biome === Biome::RIVER || $biome === Biome::OCEAN) {
                    //$type = 17; // Squid (Yet to be implemented)
	                $water = true;
                }
                
                $time = $level->getTime() % Level::TIME_FULL; //why not subtract current from total?
                
                if(
                    !$player->distance($correctedPosition) <= 8 &&
                    ($time <= Level::TIME_SUNSET || $time >= Level::TIME_SUNRISE) &&
                    $block instanceof Grass && $type !== null  // If $type is NOT set, it won't dump errors.
                ) {
                	if($this->plugin->checkEntityCount("Animal",$water)) {
                        PureEntities::logOutput("AutoSpawnAnimalTask: scheduleCreatureSpawn (pos: $correctedPosition, type: $type)",PureEntities::DEBUG);
		                $this->plugin->scheduleCreatureSpawn($correctedPosition, $type, $level, "Animal");
	                }else{
                		$this->plugin->getLogger()->debug("The animals mob cap has been reached!");
	                }
                } else {
                    PureEntities::logOutput("AutoSpawnAnimalTask: spawns nothing [player.distance.to.entity:" . $player->distance($correctedPosition) . "], [spawnTime:" . ($time <= Level::TIME_SUNSET || $time >= Level::TIME_SUNRISE) . "] [block/backupBlock.instance-of-grass:" . ($block instanceof Grass) . "]",PureEntities::DEBUG);
                }
            }
        }
    }

}