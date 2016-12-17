<?php

namespace revivalpmmp\pureentities\tile;

use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\event\CreatureSpawnEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\level\format\FullChunk;
use pocketmine\Player;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;

class Spawner extends Spawnable{
	
    protected $entityId = -1;
    protected $spawnRange;
    protected $maxNearbyEntities;
    protected $requiredPlayerRange;

    protected $delay = 0;

    protected $minSpawnDelay;
    protected $maxSpawnDelay;

	 public function __construct(FullChunk $chunk, CompoundTag $nbt){
        parent::__construct($chunk, $nbt);

        if(isset($this->namedtag->EntityId)){
            $this->entityId = $this->namedtag["EntityId"];
        }

        if(!isset($this->namedtag->SpawnRange)){
            $this->namedtag->SpawnRange = new ShortTag("SpawnRange", 8);
        }

        if(!isset($this->namedtag->MinSpawnDelay)){
            $this->namedtag->MinSpawnDelay = new ShortTag("MinSpawnDelay", 200);
        }

        if(!isset($this->namedtag->MaxSpawnDelay)){
            $this->namedtag->MaxSpawnDelay = new ShortTag("MaxSpawnDelay", 8000);
        }

        if(!isset($this->namedtag->MaxNearbyEntities)){
            $this->namedtag->MaxNearbyEntities = new ShortTag("MaxNearbyEntities", 25);
        }

        if(!isset($this->namedtag->RequiredPlayerRange)){
            $this->namedtag->RequiredPlayerRange = new ShortTag("RequiredPlayerRange", 20);
		
	}
		 
        $this->namedtag->EntityId = new ShortTag("EntityId", $this->entityId);
        $this->namedtag->SpawnRange = new ShortTag("SpawnRange", $this->spawnRange);
        $this->namedtag->MinSpawnDelay = new ShortTag("MinSpawnDelay", $this->minSpawnDelay);
        $this->namedtag->MaxSpawnDelay = new ShortTag("MaxSpawnDelay", $this->maxSpawnDelay);
        $this->namedtag->MaxNearbyEntities = new ShortTag("MaxNearbyEntities", $this->maxNearbyEntities);
        $this->namedtag->RequiredPlayerRange = new ShortTag("RequiredPlayerRange", $this->requiredPlayerRange);
    }
	}

///	public function getEntityId(){
		//return $this->namedtag["EntityId"];
	//}

	public function setEntityId(int $id){
		$this->namedtag->EntityId->setValue($id);
		$this->spawnToAll();
		if($this->chunk instanceof FullChunk){
			$this->chunk->setChanged();
			$this->level->clearChunkCache($this->chunk->getX(), $this->chunk->getZ());
		}
		$this->scheduleUpdate();
	}

	$this->namedtag->EntityId = new ShortTag("EntityId", $this->entityId);
        $this->namedtag->SpawnRange = new ShortTag("SpawnRange", $this->spawnRange);
        $this->namedtag->MinSpawnDelay = new ShortTag("MinSpawnDelay", $this->minSpawnDelay);
        $this->namedtag->MaxSpawnDelay = new ShortTag("MaxSpawnDelay", $this->maxSpawnDelay);
        $this->namedtag->MaxNearbyEntities = new ShortTag("MaxNearbyEntities", $this->maxNearbyEntities);
        $this->namedtag->RequiredPlayerRange = new ShortTag("RequiredPlayerRange", $this->requiredPlayerRange);
    }
	}

	public function getName() : string{
		return "Monster Spawner";
	}

	public function canUpdate() : bool{
		if($this->getEntityId() === 0) return false;
		$hasPlayer = false;
		$count = 0;
		foreach($this->getLevel()->getEntities() as $e){
			if($e instanceof Player){
				if($e->distance($this->getBlock()) <= 15) $hasPlayer = true;
			}
			if($e::NETWORK_ID == $this->getEntityId()){
				$count++;
			}
		}
		if($hasPlayer and $count < 15){ // Spawn limit = 15
			return true;
		}
		return false;
	}
  public function onUpdate(){
        if($this->closed){
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
                }
            }

            if($isValid && count($list) <= $this->maxNearbyEntities){
                $pos = new Position(
                    $this->x + mt_rand(-$this->spawnRange, $this->spawnRange),
                    $this->y,
                    $this->z + mt_rand(-$this->spawnRange, $this->spawnRange),
                    $this->level
                );
                $entity = PureEntities::create($this->entityId, $pos);
                if($entity != null){
                    $entity->spawnToAll();
                }
            }
        }
        return true;
    }

		$this->timings->startTiming();

		if(!($this->chunk instanceof FullChunk)){
			return false;
		}
		public function getSpawnCompound(){
        return new CompoundTag("", [
            new StringTag("id", "MobSpawner"),
            new IntTag("EntityId", $this->entityId)
        ]);
    }

    public function setSpawnEntityType(int $entityId){
        $this->entityId = $entityId;
        $this->spawnToAll();
    }

    public function setMinSpawnDelay(int $minDelay){
        if($minDelay > $this->maxSpawnDelay){
            return;
        }

        $this->minSpawnDelay = $minDelay;
    }

    public function setMaxSpawnDelay(int $maxDelay){
        if($this->minSpawnDelay > $maxDelay){
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

}
