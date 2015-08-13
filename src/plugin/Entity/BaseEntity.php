<?php

namespace plugin\Entity;

use pocketmine\block\Block;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Byte;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;
use pocketmine\Server;

abstract class BaseEntity extends Creature{

    private $movement = true;
    private $wallcheck = true;

    protected $speed = 1;

    protected $stayTime = 0;
    protected $moveTime = 0;

    protected $created = false;

    protected $baseTarget = null;
    protected $mainTarget = null;

    protected $attacker = null;
    protected $attackTime = 0;

    public function __destruct(){}

    public function onUpdate($currentTick){
        return false;
    }

    public abstract function updateTick();

    /**
     * @param Player $player
     * @param float $distance
     *
     * @return bool
     */
    public abstract function targetOption(Player $player, $distance);

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

    public function getSpeed(){
        return $this->speed;
    }

    public function initEntity(){
        if(isset($this->namedtag->Movement)){
            $this->setMovement($this->namedtag["Movement"]);
        }
        $this->setDataProperty(self::DATA_NO_AI, self::DATA_TYPE_BYTE, 1);
        Entity::initEntity();
    }

    public function saveNBT(){
        $this->namedtag->Movement = new Byte("Movement", $this->isMovement());
        parent::saveNBT();
    }

    public function spawnTo(Player $player){
        if(isset($this->hasSpawned[$player->getLoaderId()]) or !isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) return;

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

    public function updateMovement(){
        if($this->lastX == $this->x && $this->lastY == $this->y && $this->lastZ == $this->z && $this->lastYaw == $this->yaw && $this->lastPitch == $this->pitch) return;
        $this->lastX = $this->x;
        $this->lastY = $this->y;
        $this->lastZ = $this->z;
        $this->lastYaw = $this->yaw;
        $this->lastPitch = $this->pitch;

        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, $this->yaw, $this->pitch);
    }

    public function updateTarget(){
        $nearDistance = PHP_INT_MAX;
        foreach($this->getViewers() as $player){
            if(($distance = $this->distanceSquared($player)) > $nearDistance || !$this->targetOption($player, $distance)) continue;
            $nearDistance = $distance;
            $this->baseTarget = $player;
        }

        if($this->baseTarget instanceof Player){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;
            if($x ** 2 + $z ** 2 < 0.8){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                $diff = abs($x) + abs($z);
                $this->motionX = $this->speed * 0.14 * ($x / $diff);
                $this->motionZ = $this->speed * 0.14 * ($z / $diff);
            }
            $this->yaw = rad2deg(atan2($z, $x) - M_PI_2);
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
            return;
        }
        if($this->stayTime > 0){
            if(mt_rand(1, 125) > 4) return;
            $x = mt_rand(25, 80);
            $z = mt_rand(25, 80);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, mt_rand(-20, 20) / 10, mt_rand(0, 1) ? $z : -$z);

            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;
            $this->motionX = 0;
            $this->motionZ = 0;
            $this->yaw = rad2deg(atan2($z, $x) - M_PI_2);
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }elseif(mt_rand(1, 420) == 1){
            $this->stayTime = mt_rand(95, 420);
            $x = mt_rand(25, 80);
            $z = mt_rand(25, 80);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, mt_rand(-20, 20) / 10, mt_rand(0, 1) ? $z : -$z);

            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;
            $this->motionX = 0;
            $this->motionZ = 0;
            $this->yaw = rad2deg(atan2($z, $x) - M_PI_2);
            $this->pitch = rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }elseif($this->moveTime <= 0 or !$this->baseTarget instanceof Vector3){
            $this->moveTime = mt_rand(100, 1000);
            $x = mt_rand(25, 80);
            $z = mt_rand(25, 80);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);

            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;

            $diff = abs($x) + abs($z);
            $this->motionX = $this->speed * 0.14 * ($x / $diff);
            $this->motionZ = $this->speed * 0.14 * ($z / $diff);
            $this->yaw = rad2deg(atan2($z, $x) - M_PI_2);
            $this->pitch = rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }
    }

    public function updateMove(){
        if(!$this->isMovement()) return null;
        /** @var Vector3 $target */
        $target = $this->mainTarget != null ? $this->mainTarget : $this->baseTarget;
        if($this->stayTime > 0){
            --$this->stayTime;
        }else{
            $isJump = false;
            $dx = $this->motionX;
            $dy = $this->motionY;
            $dz = $this->motionZ;

            $bb = $this->boundingBox;
            $maxX = Math::ceilFloat($bb->maxX + $dx);
            $maxY = Math::ceilFloat($bb->maxY + $dy);
            $maxZ = Math::ceilFloat($bb->maxZ + $dz);
            $minX = Math::floorFloat($bb->minX + $dx);
            $minY = Math::floorFloat($bb->minY + $dy);
            $minZ = Math::floorFloat($bb->minZ + $dz);

            $v = new Position(0, 0, 0, $this->level);
            for($v->z = $minZ; $v->z <= $maxZ; ++$v->z){
                for($v->x = $minX; $v->x <= $maxX; ++$v->x){
                    for($v->y = $minY; $v->y <= $maxY; ++$v->y){
                        $chunk = $this->level->getChunk($v->x >> 4, $v->z >> 4, true);
                        if(!$chunk->isLoaded()) $chunk->load();
                        if(!$chunk->isGenerated()) $chunk->setGenerated();
                        if(!$chunk->isPopulated()) $chunk->setPopulated();
                        $t = Block::get($chunk->getBlockId($v->x & 0x0f, $v->y, $v->z & 0x0f), $chunk->getBlockData($v->x & 0x0f, $v->y, $v->z & 0x0f), $v);
                        if(!$t->canPassThrough() & $t->getBoundingBox() != null){
                            if(
                                $dy == 0
                                && $this->boundingBox->minY < $bb->maxY
                                && $this->boundingBox->minY >= $bb->minY
                                && (($this->x - $t->x) ** 2 + ($this->z - $t->z) ** 2) <= 2
                            ){
                                $up = $t->getSide(Vector3::SIDE_UP)->getBoundingBox();
                                if($up == null && ($height = $bb->maxY - $this->boundingBox->minY) > 0){
                                    if($height <= 0.5){
                                        $dy = 0.5;
                                        $isJump = true;
                                    }elseif($height <= 1){
                                        $dy = 1;
                                        $isJump = true;
                                    }
                                }elseif($up->maxY - $this->boundingBox->minY <= 1){
                                    $dy = 1;
                                    $isJump = true;
                                }
                            }
                        }
                    }
                }
            }
            $this->move($dx, $dy, $dz);
            if($this->onGround){
                $this->motionY = 0;
            }elseif(!$isJump){
                $this->motionY = -0.32;
            }
        }
        $this->updateMovement();
        return $target;
    }

    public function attack($damage, EntityDamageEvent $source){
        if($this->attacker instanceof Entity) return;
        if($this->attackTime > 0 or $this->noDamageTicks > 0){
            $lastCause = $this->getLastDamageCause();
            if($lastCause !== null and $lastCause->getDamage() >= $damage){
                $source->setCancelled();
            }
        }

        Entity::attack($damage, $source);

        if($source->isCancelled()) return;

        if($source instanceof EntityDamageByEntityEvent){
            $this->stayTime = 0;
            $this->attackTime = 5;
            $this->attacker = $source->getDamager();
            if($this instanceof PigZombie) $this->setAngry(1000);
        }
        $pk = new EntityEventPacket();
        $pk->eid = $this->getId();
        $pk->event = $this->isAlive() ? 2 : 3;
        Server::broadcastPacket($this->hasSpawned, $pk->setChannel(Network::CHANNEL_WORLD_EVENTS));
    }

    public function move($dx, $dy, $dz){
        Timings::$entityMoveTimer->startTiming();
        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;
        $list = $this->level->getCollisionCubes($this, $this->level->getTickRate() > 1 ? $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz));
        foreach($list as $bb){
            if(
                $this->boundingBox->maxX > $bb->minX
                and $this->boundingBox->minX < $bb->maxX
                and $this->boundingBox->maxZ > $bb->minZ
                and $this->boundingBox->minZ < $bb->maxZ
            ){
                if($this->boundingBox->maxY + $dy >= $bb->minY and $this->boundingBox->maxY <= $bb->minY){
                    if(($y1 = $bb->minY - ($this->boundingBox->maxY + $dy)) < 0) $dy += $y1;
                }
                if($this->boundingBox->minY + $dy <= $bb->maxY and $this->boundingBox->minY >= $bb->maxY){
                    if(($y1 = $bb->maxY - ($this->boundingBox->minY + $dy)) > 0) $dy += $y1;
                }
            }
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

        $this->updateFallState($dy, $this->onGround = ($movY != $dy and $movY < 0));

        $this->isCollidedVertically = $movY != $dy;
        $this->isCollidedHorizontally = ($movX != $dx or $movZ != $dz);
        $this->isCollided = ($this->isCollidedHorizontally or $this->isCollidedVertically);

        $this->checkChunks();
        Timings::$entityMoveTimer->stopTiming();
    }

    public function knockBackCheck(){
        if(!$this->attacker instanceof Entity) return false;
        if($this->attackTime == 5){
            $target = $this->attacker;
            $x = $target->x - $this->x;
            $z = $target->z - $this->z;
            $diff = abs($x) + abs($z);
            $this->motionX = 0.5 * ($x / $diff);
            $this->motionZ = 0.5 * ($z / $diff);
        }
        $y = [
            4 => 0.32,
            5 => 0.95,
        ];
        $motionY = isset($y[$this->attackTime]) ?  $y[$this->attackTime] : -0.32;
        $this->move(-$this->motionX, $motionY, -$this->motionZ);
        if(--$this->attackTime <= 0) $this->attacker = null;
        return true;
    }

    public function close(){
        $this->created = false;
        parent::close();
    }

}