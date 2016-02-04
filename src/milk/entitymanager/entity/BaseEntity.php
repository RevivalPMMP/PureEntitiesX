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

    protected $stayTime = 0;
    protected $moveTime = 0;

    /** @var Vector3|Entity */
    protected $baseTarget = null;
    /** @var Vector3|Entity */
    protected $mainTarget = null;

    private $movement = true;
    private $friendly = false;
    private $wallcheck = true;

    public function __destruct(){}

    public abstract function updateMove(int $tickDiff) : Vector3;

    public abstract function targetOption(Creature $creature, float $distance) : bool;

    public function getSaveId(){
        $class = new \ReflectionClass(static::class);
        return $class->getShortName();
    }

    public function isMovement() : bool{
        return $this->movement;
    }

    public function isFriendly() : bool{
        return $this->friendly;
    }

    public function isKnockback() : bool{
        return $this->attackTime > 0;
    }

    public function isWallCheck() : bool{
        return $this->wallcheck;
    }

    public function setMovement(bool $value){
        $this->movement = $value;
    }

    public function setFriendly(bool $bool){
        $this->friendly = $bool;
    }

    public function setWallCheck(bool $value){
        $this->wallcheck = $value;
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
        if(
            !isset($this->hasSpawned[$player->getLoaderId()])
            && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])
        ){
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
        if($this->isKnockback() > 0) return;

        parent::attack($damage, $source);

        if($source->isCancelled() || !($source instanceof EntityDamageByEntityEvent)){
            return;
        }

        $this->stayTime = 0;
        $this->moveTime = 0;
        $this->baseTarget = null;

        $damager = $source->getDamager();
        $motion = (new Vector3($this->x - $damager->x, $this->y - $damager->y, $this->z - $damager->z))->normalize();
        $this->motionX = $motion->x * 0.19;
        $this->motionZ = $motion->z * 0.19;
        if($this instanceof FlyingEntity){
            $this->motionY = $motion->y * 0.19;
        }else{
            $this->motionY = 0.5;
        }
    }

    public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4){

    }

    public function entityBaseTick($tickDiff = 1){
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if($this->isInsideOfSolid()){
            $hasUpdate = true;
            $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_SUFFOCATION, 1);
            $this->attack($ev->getFinalDamage(), $ev);
        }

        if($this->moveTime > 0){
            $this->moveTime -= $tickDiff;
        }

        if($this->attackTime > 0){
            $this->attackTime -= $tickDiff;
        }

        Timings::$timerEntityBaseTick->startTiming();
        return $hasUpdate;
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

        if($this->isWallCheck()){
            foreach($list as $bb){
                $dx = $bb->calculateXOffset($this->boundingBox, $dx);
            }
            $this->boundingBox->offset($dx, 0, 0);

            foreach($list as $bb){
                $dz = $bb->calculateZOffset($this->boundingBox, $dz);
            }
            $this->boundingBox->offset(0, 0, $dz);
        }

        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
        $this->checkChunks();

        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);

        Timings::$entityMoveTimer->stopTiming();
        return true;
    }

}