<?php

/**
 * PureEntitiesX: Mob AI Plugin for PMMP
 * Copyright (C)  2018 RevivalPMMP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace revivalpmmp\pureentities\tile;

use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\tile\Spawnable;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

class MobSpawner extends Spawnable{

	protected $entityId = -1;
	protected $spawnRange = 8;
	protected $maxNearbyEntities = 6;
	protected $requiredPlayerRange = 16;

	protected $delay = 0;

	protected $minSpawnDelay = 200;
	protected $maxSpawnDelay = 800;
	protected $spawnCount = 0;

	public function __construct(Level $level, CompoundTag $nbt){

		parent::__construct($level, $nbt);

		$this->scheduleUpdate();
		PureEntities::logOutput("MobSpawner Created with EntityID of $this->entityId");
	}

	public function onUpdate() : bool{
		if($this->isClosed()){
			return false;
		}
		if($this->entityId === -1){
			PureEntities::logOutput("onUpdate Called with EntityID of -1");
			return false;
		}

		if($this->delay++ >= mt_rand($this->minSpawnDelay, $this->maxSpawnDelay)){
			$this->delay = 0;

			$list = [];
			$isValid = false;
			foreach($this->level->getEntities() as $entity){
				if($entity->distance($this) <= $this->requiredPlayerRange){
					if($entity instanceof Player){
						$isValid = true;
					}
					$list[] = $entity;
					break;
				}
			}

			if($isValid && count($list) <= $this->maxNearbyEntities){
				$y = $this->y;
				$x = $this->x + mt_rand(-$this->spawnRange, $this->spawnRange);
				$z = $this->z + mt_rand(-$this->spawnRange, $this->spawnRange);
				$pos = PureEntities::getSuitableHeightPosition($x, $y, $z, $this->level);
				$pos->y += Data::HEIGHTS[$this->entityId];
				$entity = PureEntities::create($this->entityId, $pos);
				if($entity != null){
					PureEntities::logOutput("MobSpawner: spawn $entity to $pos");
					$entity->spawnToAll();
				}
			}
		}
		$this->scheduleUpdate();
		return true;
	}

	public function setSpawnEntityType(int $entityId){
		PureEntities::logOutput("setSpawnEntityType called with EntityID of $entityId");
		$this->entityId = $entityId;
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			$this->writeSaveData($tag = new CompoundTag());
		}
		$this->onChanged();
		$this->scheduleUpdate();
	}

	public function setMinSpawnDelay(int $minDelay){
		if($minDelay > $this->maxSpawnDelay){
			return;
		}

		$this->minSpawnDelay = $minDelay;
	}

	public function setMaxSpawnDelay(int $maxDelay){
		if($this->minSpawnDelay > $maxDelay or $maxDelay === 0){
			return;
		}

		$this->maxSpawnDelay = $maxDelay;
	}

	public function setSpawnDelay(int $minDelay, int $maxDelay){
		if($minDelay > $maxDelay){
			return;
		}

		$this->minSpawnDelay = $minDelay;
		$this->maxSpawnDelay = $maxDelay;
	}

	public function setRequiredPlayerRange(int $range){
		$this->requiredPlayerRange = $range;
	}

	public function setMaxNearbyEntities(int $count){
		$this->maxNearbyEntities = $count;
	}

	public function addAdditionalSpawnData(CompoundTag $nbt) : void{
		$nbt->setByte(NBTConst::NBT_KEY_SPAWNER_IS_MOVABLE, 1);
		$nbt->setShort(NBTConst::NBT_KEY_SPAWNER_DELAY, 0);
		$nbt->setShort(NBTConst::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES, $this->maxNearbyEntities);
		$nbt->setShort(NBTConst::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY, $this->maxSpawnDelay);
		$nbt->setShort(NBTConst::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY, $this->minSpawnDelay);
		$nbt->setShort(NBTConst::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE, $this->requiredPlayerRange);
		$nbt->setShort(NBTConst::NBT_KEY_SPAWNER_SPAWN_COUNT, $this->spawnCount);
		$nbt->setShort(NBTConst::NBT_KEY_SPAWNER_SPAWN_RANGE, $this->spawnRange);
		$nbt->setInt(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, $this->entityId);
		//$spawnData = new CompoundTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_DATA, [new StringTag("id", $this->entityId)]);
		//$nbt->setTag($spawnData);
		$this->scheduleUpdate();
	}

	public function readSaveData(CompoundTag $nbt) : void{
		if(PluginConfiguration::getInstance()->getEnableNBT()){

			if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID)){
				$this->setSpawnEntityType($nbt->getInt(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, -1, true));
			}

			if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_RANGE)){
				$this->spawnRange = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_SPAWN_RANGE, 8, true);
			}

			if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY)){
				$this->minSpawnDelay = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY, 200, true);
			}

			if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY)){
				$this->maxSpawnDelay = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY, 800, true);
			}

			if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_DELAY)){
				$this->delay = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_DELAY, 0, true);
			}

			if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES)){
				$this->maxNearbyEntities = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES, 6, true);
			}

			if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE)){
				$this->requiredPlayerRange = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE, 16);
			}

			if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_COUNT)){
				$this->spawnCount = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_SPAWN_COUNT, 0, true);
			}

			// TODO: add SpawnData: Contains tags to copy to the next spawned entity(s) after spawning. Any of the entity or
			// mob tags may be used. Note that if a spawner specifies any of these tags, almost all variable data such as mob
			// equipment, villager profession, sheep wool color, etc., will not be automatically generated, and must also be
			// manually specified (note that this does not apply to position data, which will be randomized as normal unless
			// Pos is specified. Similarly, unless Size and Health are specified for a Slime or Magma Cube, these will still
			// be randomized). This, together with EntityId, also determines the appearance of the miniature entity spinning
			// in the spawner cage. Note: this tag is optional: if it does not exist, the next spawned entity will use
			// the default vanilla spawning properties for this mob, including potentially randomized armor (this is true even
			// if SpawnPotentials does exist). Warning: If SpawnPotentials exists, this tag will get overwritten after the
			// next spawning attempt: see above for more details.
			// if(!$nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_DATA)){
			//	 $spawnData = new CompoundTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_DATA, [new IntTag(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, $this->entityId)]);
			//	 $nbt->setTag($spawnData);
			// }

		}
	}

	public function writeSaveData(CompoundTag $nbt) : void{
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			$this->addAdditionalSpawnData($nbt);
		}
	}

	public function getSpawnCount() : int{
		return $this->spawnCount;
	}
}