<?php
namespace revivalpmmp\pureentities\tile;

use pocketmine\Player;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Spawnable;

use revivalpmmp\pureentities\PureEntities;

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
		if(!isset($nbt->EntityId)){
			$nbt->EntityId = new IntTag("EntityId", 0);

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

   // public function onUpdate(){
		//if($this->closed === true){
			//return false;
			
		//}
	    
		$this->timings->startTiming();
		if(!($this->chunk instanceof FullChunk)){
			return false;
			
		}
	    
		if($this->canUpdate()){
			if($this->getDelay() <= 0){
				$success = 0;
				for($i = 0; $i < $this->getSpawnCount(); $i++){
					$pos = $this->add(mt_rand() / mt_getrandmax() * $this->getSpawnRange(), mt_rand(-1, 1), mt_rand() / mt_getrandmax() * $this->getSpawnRange());
					$target = $this->getLevel()->getBlock($pos);
					$ground = $target->getSide(Vector3::SIDE_DOWN);
					if($target->getId() == Item::AIR && $ground->isTopFacingSurfaceSolid()){
						$success++;
						$this->getLevel()->getServer()->getPluginManager()->callEvent($ev = new EntityGenerateEvent($pos, $this->getEntityId(), EntityGenerateEvent::CAUSE_MOB_SPAWNER));
						if(!$ev->isCancelled()){
							$nbt = new CompoundTag("", [
								"Pos" => new ListTag("Pos", [
									new DoubleTag("", $pos->x),
									new DoubleTag("", $pos->y),
									new DoubleTag("", $pos->z)
								
								/"Motion" => new ListTag("Motion", [
									new DoubleTag("", 0),
									new DoubleTag("", 0),
									new DoubleTag("", 0)
								
								"Rotation" => new ListTag("Rotation", [
									new FloatTag("", mt_rand() / mt_getrandmax() * 360),
									new FloatTag("", 0)
						
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
