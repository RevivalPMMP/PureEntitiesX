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

use pocketmine\block\Block;
use pocketmine\level\biome\Biome;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use revivalpmmp\pureentities\data\BiomeInfo;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\MobTypeMaps;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\utils\PeTimings;


class AutoSpawnTask extends Task{

	const HOSTILE_CAP_CONSTANT = 70;
	const PASSIVE_WET_CAP_CONSTANT = 10;
	const PASSIVE_DRY_CAP_CONSTANT = 15;
	const AMBIENT_CAP_CONSTANT = 5;

	private $plugin;
	private $spawnerWorlds = [];

	// Friendly Mobs only generate every 400 ticks
	private $hostileMobs = 0;
	private $passiveDryMobs = 0;
	private $passiveWetMobs = 0;
	private $ambientMobs = 0;

	public function __construct(PureEntities $plugin){
		$this->plugin = $plugin;
		$this->spawnerWorlds = PluginConfiguration::getInstance()->getEnabledWorlds();
	}

	public function onRun(int $currentTick){
		PureEntities::logOutput("AutoSpawnTask: onRun ($currentTick)");
		PeTimings::startTiming("AutoSpawnTask");

		foreach($this->plugin->getServer()->getLevels() as $level){
			if(count($this->spawnerWorlds) > 0 and !in_array($level->getName(), $this->spawnerWorlds)){
				continue;
			}
			$this->hostileMobs = 0;
			$this->passiveDryMobs = 0;
			$this->passiveWetMobs = 0;

			// For now, spawning as overworld only.
			foreach($level->getEntities() as $entity){
				if(in_array(array_search($entity::NETWORK_ID, Data::NETWORK_IDS), MobTypeMaps::OVERWORLD_HOSTILE_MOBS)){
					$this->hostileMobs++;
				}elseif(in_array(array_search($entity::NETWORK_ID, Data::NETWORK_IDS), MobTypeMaps::PASSIVE_DRY_MOBS)){
					$this->passiveDryMobs++;
				}elseif(in_array(array_search($entity::NETWORK_ID, Data::NETWORK_IDS), MobTypeMaps::PASSIVE_WET_MOBS)){
					$this->passiveWetMobs++;
				}
			}
			PureEntities::logOutput("AutoSpawnTask: Hostiles = $this->hostileMobs");
			PureEntities::logOutput("AutoSpawnTask: Passives(Dry) = $this->passiveDryMobs");
			PureEntities::logOutput("AutoSpawnTask: Passives(Wet) = $this->passiveWetMobs");

			$playerLocations = [];


			if(count($level->getPlayers()) > 0){
				foreach($level->getPlayers() as $player){
					if($player->spawned){

						/* Intentionally not converting directly to chunks here so
						 * spawn locations can be compared to player locations to meet
						 * distance requirements.
						 */
						array_push($playerLocations, $player->getPosition());
					}
				}

				// List of chunks eligible to spawn new mobs.
				$spawnMap = $this->generateSpawnMap($playerLocations);

				PureEntities::logOutput("AutoSpawnTask: Spawn Map generated.");

				if(($totalChunks = count($spawnMap)) > 0){
					PureEntities::logOutput("AutoSpawnTask: Spawn Map is populated.");
					$hostileCap = self::HOSTILE_CAP_CONSTANT * $totalChunks / 256;
					$passiveDryCap = self::PASSIVE_DRY_CAP_CONSTANT * $totalChunks / 256;
					$passiveWetCap = self::PASSIVE_WET_CAP_CONSTANT * $totalChunks / 256;
					$ambientCap = self::AMBIENT_CAP_CONSTANT * $totalChunks / 256;

					foreach($spawnMap as $chunk){
						// TODO Find source of null chunks
						if($chunk != null){
							if($hostileCap > $this->hostileMobs){
								$this->spawnHostileMob($chunk, $level);
							}
							if($passiveDryCap > $this->passiveDryMobs){
								$this->spawnPassiveMob($chunk, $level);
							}
							if($passiveWetCap > $this->passiveWetMobs){
								// TODO: Implement Passive Wet Spawns
								// $this->spawnPassiveWet($chunk, $level);
							}
							// TODO: Implement Ambient Spawning.
						}
					}
				}

			}
		}
		PeTimings::stopTiming("AutoSpawnTask", true);
	}


	/**
	 * Converts player locations to a 15x15 set of chunks centered around each player.
	 * This will not duplicate chunks in the list if 2 players are in close proximity
	 * of one another.
	 *
	 * @param array $playerLocations
	 * @return array
	 */

	private function generateSpawnMap(array $playerLocations) : array{
		$convertedChunkList = [];
		$spawnMap = [];

		if(count($playerLocations) > 0){
			// This will take the location of each player, determine what chunk
			// they are in, and store the chunk in $convertedChunkList.

			/**
			 * @var Position $playerPos
			 */
			foreach($playerLocations as $playerPos){

				$chunkHash = Level::chunkHash($playerPos->x >> 4, $playerPos->z >> 4);


				// If the chunk is already in the list, there's no need to add it again.
				if(!isset($convertedChunkList[$chunkHash])){
					$convertedChunkList[$chunkHash] = $playerPos->getLevel()->getChunk($playerPos->x >> 4, $playerPos->z >> 4);
					PureEntities::logOutput("AutoSpawnTask: Chunk added to convertedChunkList.");
				}
			}

			/**
			 * Add a 15x15 group of chunks centered around each player to the spawn map.
			 * This will avoid adding duplicate chunks when players are in close proximity
			 * to one another.
			 *
			 * @var Chunk $chunk
			 */
			foreach($convertedChunkList as $chunk){
				for($x = -7; $x <= 7; $x++){
					for($z = -7; $z <= 7; $z++){
						$trialX = $chunk->getX() + $x;
						$trialZ = $chunk->getZ() + $z;
						PureEntities::logOutput("AutoSpawnTask: Testing Chunk X: $trialX, Z: $trialZ.");
						$trialChunk = Level::chunkHash($trialX, $trialZ);
						if(!isset($spawnMap[$trialChunk])){
							$spawnMap[$trialChunk] = $playerPos->getLevel()->getChunk($trialX, $trialZ);
							PureEntities::logOutput("AutoSpawnTask: Chunk added to Spawn Map.");
						}
					}
				}
			}
		}
		return $spawnMap;
	}


	/**
	 * Returns a random (x,y,z) position inside the provided chunk as a Vector3.
	 *
	 * @param Vector2 $chunk
	 * @return Vector3
	 */
	private function getRandomLocationInChunk(Vector2 $chunk) : Vector3{
		$x = mt_rand($chunk->x * 16, (($chunk->x * 16) + 15));
		$y = mt_rand(0, 255);
		$z = mt_rand($chunk->y * 16, (($chunk->y * 16) + 15));

		return new Vector3($x, $y, $z);
	}

	private function isValidPackCenter(Vector3 $center, Level $level) : bool{
		if($level->getBlockAt($center->x, $center->y, $center->z)->isTransparent()){
			return true;
		}else{
			return false;
		}
	}

	protected function spawnPackToLevel(Vector3 $center, int $entityId, Level $level, string $type, bool $isBaby = false){

		// TODO Update to change $maxPackSize based on Mob
		$maxPackSize = 4;
		$currentPackSize = 0;

		for($attempts = 0; $attempts <= 12 and $currentPackSize < $maxPackSize; $attempts++){
			$x = mt_rand(-20, 20) + $center->x;
			$z = mt_rand(-20, 20) + $center->z;
			$pos = new Position($x, $center->y, $z, $level);

			if($this->isValidDrySpawnLocation($pos) and $this->isSpawnAllowedByBiome($entityId, $level->getBiomeId($x, $z))){
				PureEntities::logOutput("AutoSpawnTask: Spawning Mob (ID = $entityId) to location: $x, $center->y, $z", PureEntities::NORM);
				$success = PureEntities::getInstance()->scheduleCreatureSpawn($pos, $entityId, $level, $type, $isBaby) !== null;
				if($success){
					$currentPackSize++;
				}
			}
			PureEntities::logOutput("AutoSpawnTask: X:$x, Y:$center->y, Z:$z, Not a valid spawn location.");
		}
		return;

	}

	private function isValidSpawnLocation(Position $spawnLocation){
		if(!$spawnLocation->level->getBlockAt($spawnLocation->x, $spawnLocation->y, $spawnLocation->z)->isTransparent()
			and $spawnLocation->level->getBlockAt($spawnLocation->x, $spawnLocation->y + 1, $spawnLocation->z)->isTransparent()
			and $spawnLocation->level->getBlockAt($spawnLocation->x, $spawnLocation->y + 2, $spawnLocation->z)->isTransparent()){
			return true;
		}
		return false;
	}

	private function isValidDrySpawnLocation(Position $spawnLocation){
		if(!$spawnLocation->level->getBlockAt($spawnLocation->x, $spawnLocation->y - 1, $spawnLocation->z)->isTransparent()
			and ($spawnLocation->level->getBlockAt($spawnLocation->x, $spawnLocation->y, $spawnLocation->z)->isTransparent() and
				$spawnLocation->level->getBlockAt($spawnLocation->x, $spawnLocation->y, $spawnLocation->z)->getId() != Block::WATER)
			and ($spawnLocation->level->getBlockAt($spawnLocation->x, $spawnLocation->y + 1, $spawnLocation->z)->isTransparent()
				and $spawnLocation->level->getBlockAt($spawnLocation->x, $spawnLocation->y + 1, $spawnLocation->z)->getId() != Block::WATER)
		){
			return true;
		}
		return false;
	}


	/**
	 * This finds a random location in the chunk to act as a pack spawn center
	 * then attempts to spawn a pack based on passive mob conditions.
	 * @param Chunk $chunk
	 * @param Level $level
	 */
	private function spawnPassiveMob(Chunk $chunk, Level $level){
		PureEntities::logOutput("AutoSpawnTask: Attempting to spawn passive mob.");
		$packCenter = $this->getRandomLocationInChunk(new Vector2($chunk->getX(), $chunk->getZ()));
		$lightLevel = $level->getFullLightAt($packCenter->x, $packCenter->y, $packCenter->z);
		if($this->isValidPackCenter($packCenter, $level) and $lightLevel > 7){
			$mobId = Data::NETWORK_IDS[MobTypeMaps::PASSIVE_DRY_MOBS[array_rand(MobTypeMaps::PASSIVE_DRY_MOBS)]];
			$this->spawnPackToLevel($packCenter, $mobId, $level, "passive");
		}
		PureEntities::logOutput("AutoSpawnTask: Not a valid pack center.");

	}

	private function spawnHostileMob(Chunk $chunk, Level $level){
		PureEntities::logOutput("AutoSpawnTask: Attempting to spawn hostile mob.");
		$packCenter = $this->getRandomLocationInChunk(new Vector2($chunk->getX(), $chunk->getZ()));
		PureEntities::logOutput("AutoSpawnTask: Chosen Pack Center at $packCenter->x, $packCenter->y, $packCenter->z.");
		$lightLevel = $level->getFullLightAt($packCenter->x, $packCenter->y, $packCenter->z);
		PureEntities::logOutput("AutoSpawnTask: light level at trial pack center is $lightLevel");
		if($this->isValidPackCenter($packCenter, $level) and $lightLevel < 7){

			PureEntities::logOutput("AutoSpawnTask: light level at valid pack center is $lightLevel");
			$mobId = Data::NETWORK_IDS[MobTypeMaps::OVERWORLD_HOSTILE_MOBS[array_rand(MobTypeMaps::OVERWORLD_HOSTILE_MOBS)]];
			$this->spawnPackToLevel($packCenter, $mobId, $level, "hostile");
		}
		PureEntities::logOutput("AutoSpawnTask: Not a valid pack center.");
	}

	private function isSpawnAllowedByBiome(int $entityId, int $trialBiome) : bool{
		if(isset(BiomeInfo::ALLOWED_ENTITIES_BY_BIOME[$trialBiome])){
			if(in_array($entityId, BiomeInfo::ALLOWED_ENTITIES_BY_BIOME[$trialBiome])
				or (($trialBiome !== Biome::HELL and $trialBiome !== 9) and in_array($entityId, BiomeInfo::OVERWORLD_BIOME_EXEMPT))){
				return true;
			}
		}
		PureEntities::logOutput("Biome test failed with Entity: $entityId and Biome: $trialBiome", PureEntities::NORM);
		return false;
	}
}