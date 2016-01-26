<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

abstract class BaseEntity extends Creature{

    private $movement = true;
    private $wallcheck = true;

    protected $stayTime = 0;
    protected $moveTime = 0;

    protected $created = false;

    /** @var Vector3|Entity */
    protected $baseTarget = null;
    /** @var Vector3|Entity */
    protected $mainTarget = null;

    protected $attacker = null;
    protected $atkTime = 0;

    protected $isFriendly = false;
    
    public function isFriendly(){
        return $this->isFriendly;
    }
    public function setFriendly($bool){
        $this->isFriendly = $bool;
    }

    public function __destruct(){}

    public function onUpdate($currentTick){
        return false;
    }

    public abstract function updateTick();

    public abstract function updateMove();

    public abstract function targetOption(Creature $creature, $distance);

    public function getSaveId(){
        $class = new \ReflectionClass(static::class);
        return $class->getShortName();
    }

    public function isCreated(){
        return $this->created;
    }

    public function isMovement(){
        return $this->movement;
    }

    public function setMovement($value){
        $this->movement = (bool) $value;
    }

    public function isWallCheck(){
        return $this->wallcheck;
    }

    public function setWallCheck($value){
        $this->wallcheck = (bool) $value;
    }

    public function getSpeed() : float{
        return 1;
    }

    public function initEntity(){
        parent::initEntity();

        if(isset($this->namedtag->Movement)){
            $this->setMovement($this->namedtag["Movement"]);
        }
        $this->dataProperties[self::DATA_NO_AI] = [self::DATA_TYPE_BYTE, 1];
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->Movement = new ByteTag("Movement", $this->isMovement());
    }

    public function spawnTo(Player $player){
        if(!isset($this->hasSpawned[$player->getLoaderId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])){
            $pk = new AddEntityPacket();
            $pk->eid = $this->getID();
            $pk->type = static::NETWORK_ID;
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->speedX = 0;
            $pk->speedY = 0;
            $pk->speedZ = 0;
            $pk->yaw = $this->yaw;
            $pk->pitch = $this->pitch;
            $pk->metadata = $this->dataProperties;
            $player->dataPacket($pk);

            $this->hasSpawned[$player->getLoaderId()] = $player;
        }
    }

    public function updateMovement(){
        if(
            $this->lastX !== $this->x
            || $this->lastY !== $this->y
            || $this->lastZ !== $this->z
            || $this->lastYaw !== $this->yaw
            || $this->lastPitch !== $this->pitch
        ){
            $this->lastX = $this->x;
            $this->lastY = $this->y;
            $this->lastZ = $this->z;
            $this->lastYaw = $this->yaw;
            $this->lastPitch = $this->pitch;

            $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, $this->yaw, $this->pitch);
        }
    }

    public function attack($damage, EntityDamageEvent $source){
        if($this->atkTime > 0) return;

        parent::attack($damage, $source);

        if($source->isCancelled() || !($source instanceof EntityDamageByEntityEvent)){
            return;
        }

        $this->atkTime = 15;
        $this->stayTime = 0;

        $this->attacker = $source->getDamager();

        $x = $this->attacker->x - $this->x;
        $z = $this->attacker->z - $this->z;
        $diff = abs($x) + abs($z);
        $this->motionX = -0.4 * ($diff == 0 ? 0 : $x / $diff);
        $this->motionZ = -0.4 * ($diff == 0 ? 0 : $z / $diff);
        $this->move($this->motionX, 0.6, $this->motionZ);

        if($this instanceof PigZombie){
            $this->setAngry(1000);
        }elseif($this instanceof Wolf){
            $this->setAngry(1000);
        }elseif($this instanceof Ocelot){
            $this->setAngry(1000);
        }
    }

    public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4){

    }

    public function move($dx, $dy, $dz) : bool{
        Timings::$entityMoveTimer->startTiming();

        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;
        $list = $this->level->getCollisionCubes($this, $this->level->getTickRate() > 1 ? $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz));

        foreach($list as $bb){
            $dy = $bb->calculateYOffset($this->boundingBox, $dy);
        }
        $this->boundingBox->offset(0, $dy, 0);

        foreach($list as $bb){
            if(
                $this->isWallCheck()
                and $this->boundingBox->maxY > $bb->minY
                and $this->boundingBox->minY < $bb->maxY
                and $this->boundingBox->maxZ > $bb->minZ
                and $this->boundingBox->minZ < $bb->maxZ
            ){
                if($this->boundingBox->maxX + $dx >= $bb->minX and $this->boundingBox->maxX <= $bb->minX){
                    if(($x1 = $bb->minX - ($this->boundingBox->maxX + $dx)) < 0) $dx += $x1;
                }
                if($this->boundingBox->minX + $dx <= $bb->maxX and $this->boundingBox->minX >= $bb->maxX){
                    if(($x1 = $bb->maxX - ($this->boundingBox->minX + $dx)) > 0) $dx += $x1;
                }
            }
        }
        $this->boundingBox->offset($dx, 0, 0);

        foreach($list as $bb){
            if(
                $this->isWallCheck()
                and $this->boundingBox->maxY > $bb->minY
                and $this->boundingBox->minY < $bb->maxY
                and $this->boundingBox->maxX > $bb->minX
                and $this->boundingBox->minX < $bb->maxX
            ){
                if($this->boundingBox->maxZ + $dz >= $bb->minZ and $this->boundingBox->maxZ <= $bb->minZ){
                    if(($z1 = $bb->minZ - ($this->boundingBox->maxZ + $dz)) < 0) $dz += $z1;
                }
                if($this->boundingBox->minZ + $dz <= $bb->maxZ and $this->boundingBox->minZ >= $bb->maxZ){
                    if(($z1 = $bb->maxZ - ($this->boundingBox->minZ + $dz)) > 0) $dz += $z1;
                }
            }
        }
        $this->boundingBox->offset(0, 0, $dz);

        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
        $this->checkChunks();

        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);

        Timings::$entityMoveTimer->stopTiming();
        return true;
    }

    public function close(){
        $this->created = false;
        parent::close();
    }

}