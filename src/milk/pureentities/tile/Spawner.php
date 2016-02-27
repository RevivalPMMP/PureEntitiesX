<?php

namespace milk\pureentities\tile;

use milk\pureentities\PureEntities;
use milk\randomjoin\Player;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Spawnable;

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

        $this->spawnRange = $this->namedtag["SpawnRange"];
        $this->minSpawnDelay = $this->namedtag["MinSpawnDelay"];
        $this->maxSpawnDelay = $this->namedtag["MaxSpawnDelay"];
        $this->maxNearbyEntities = $this->namedtag["MaxNearbyEntities"];
        $this->requiredPlayerRange = $this->namedtag["RequiredPlayerRange"];

        $this->scheduleUpdate();
    }

    public function onUpdate(){
        if($this->closed){
            return false;
        }

        if($this->delay++ >= mt_rand($this->minSpawnDelay, $this->maxSpawnDelay)){
            $this->delay = 0;

            $list = [];
            $isVaild = false;
            foreach($this->level->getEntities() as $entity){
                if($entity->distance($this) <= $this->requiredPlayerRange){
                    if($entity instanceof Player){
                        $isVaild = true;
                    }
                    $list[] = $entity;
                    break;
                }
            }

            if($isVaild && count($list) <= $this->maxNearbyEntities){
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

    public function saveNBT(){
        parent::saveNBT();

        $this->namedtag->EntityId = new ShortTag("EntityId", $this->entityId);
        $this->namedtag->SpawnRange = new ShortTag("SpawnRange", $this->spawnRange);
        $this->namedtag->MinSpawnDelay = new ShortTag("MinSpawnDelay", $this->minSpawnDelay);
        $this->namedtag->MaxSpawnDelay = new ShortTag("MaxSpawnDelay", $this->maxSpawnDelay);
        $this->namedtag->MaxNearbyEntities = new ShortTag("MaxNearbyEntities", $this->maxNearbyEntities);
        $this->namedtag->RequiredPlayerRange = new ShortTag("RequiredPlayerRange", $this->requiredPlayerRange);
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