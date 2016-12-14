<?php

namespace magicode\pureentities\task;

use magicode\pureentities\event\CreatureSpawnEvent;
use pocketmine\scheduler\PluginTask;
use magicode\pureentities\PureEntities;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\level\generator\biome\Biome;
use pocketmine\block\Grass;
use pocketmine\math\Vector3;

class AutoSpawnAnimalTask extends PluginTask {

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
        
                if($valid && count($entities) <= 10) {
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
                
                $type = 11; // If $type is NOT set, it won't dump errors.
                if($level->getBiomeId($x, $z) === null) {
                    $biome = 1;
                } else {
                    $biome = $level->getBiomeId($x, $z);
                }
                $probability = mt_rand(1, 100);
                $block = $level->getBlock(new Vector3($x, $y - 1, $z));
                
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
                if($biome === Biome::PLAINS || $biome) {
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
                }
                
                $time = $level->getTime() % Level::TIME_FULL;
                
                if(
                    !$player->distance($pos) <= 8 &&
                    ($time <= Level::TIME_SUNSET || $time >= Level::TIME_SUNRISE) &&
                    $block instanceof Grass
                ) {
                    $this->plugin->scheduleCreatureSpawn($pos, $type, $level, "Animal");
                }
            }
        }
    }
}
