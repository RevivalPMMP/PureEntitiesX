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

namespace revivalpmmp\pureentities\entity;

use pocketmine\block\Block;
use revivalpmmp\pureentities\entity\monster\flying\Blaze;
use revivalpmmp\pureentities\entity\monster\Monster;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\level\Level;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PluginConfiguration;

abstract class BaseEntity extends Creature {

    public $stayTime = 0;
    protected $moveTime = 0;

    /** @var Vector3|Entity */
    private $baseTarget = null;

    private $movement = true;
    private $friendly = false;
    private $wallcheck = true;
    protected $fireProof = false;
    private $maxJumpHeight = 1; // default: 1 block jump height - this should be 2 for horses e.g.

    /**
     * @var int
     */
    private $checkTargetSkipCounter = 0;

    public function __destruct() {
    }

    public abstract function updateMove($tickDiff);

    public function getSaveId() {
        $class = new \ReflectionClass(get_class($this));
        return $class->getShortName();
    }

    public function isMovement(): bool {
        return $this->movement;
    }

    public function isFriendly(): bool {
        return $this->friendly;
    }

    public function isKnockback(): bool {
        return $this->attackTime > 0;
    }

    public function isWallCheck(): bool {
        return $this->wallcheck;
    }

    public function setMovement(bool $value) {
        $this->movement = $value;
    }

    public function setFriendly(bool $bool) {
        $this->friendly = $bool;
    }

    public function setWallCheck(bool $value) {
        $this->wallcheck = $value;
    }

    /**
     * Sets the base target for the entity. If this method is called
     * and the baseTarget is the same, nothing is set
     *
     * @param $baseTarget
     */
    public function setBaseTarget($baseTarget) {
        if ($baseTarget !== $this->baseTarget) {
            $this->baseTarget = $baseTarget;
        }
    }

    /**
     * Returns the base target currently set for this entity
     *
     * @return Entity|Vector3
     */
    public function getBaseTarget() {
        return $this->baseTarget;
    }

    public function getSpeed(): float {
        return 1;
    }

    /**
     * @return int
     */
    public function getMaxJumpHeight(): int {
        return $this->maxJumpHeight;
    }

    public function initEntity() {
        parent::initEntity();

        if (isset($this->namedtag->Movement)) {
            $this->setMovement($this->namedtag["Movement"]);
        }

        if (isset($this->namedtag->WallCheck)) {
            $this->setWallCheck($this->namedtag["WallCheck"]);
        }
        $this->dataProperties[self::DATA_FLAG_NO_AI] = [self::DATA_TYPE_BYTE, 1];
    }

    public function saveNBT() {
        parent::saveNBT();
        $this->namedtag->Movement = new ByteTag("Movement", $this->isMovement());
        $this->namedtag->WallCheck = new ByteTag("WallCheck", $this->isWallCheck());
    }

    public function spawnTo(Player $player) {
        if (
            !isset($this->hasSpawned[$player->getLoaderId()])
            && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])
        ) {
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

    public function updateMovement() {
        if (
            $this->lastX !== $this->x
            || $this->lastY !== $this->y
            || $this->lastZ !== $this->z
            || $this->lastYaw !== $this->yaw
            || $this->lastPitch !== $this->pitch
        ) {
            $this->lastX = $this->x;
            $this->lastY = $this->y;
            $this->lastZ = $this->z;
            $this->lastYaw = $this->yaw;
            $this->lastPitch = $this->pitch;
        }
        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, $this->yaw, $this->pitch);
    }

    public function isInsideOfSolid() {
        $block = $this->level->getBlock($this->temporalVector->setComponents(Math::floorFloat($this->x), Math::floorFloat($this->y + $this->height - 0.18), Math::floorFloat($this->z)));
        $bb = $block->getBoundingBox();
        return $bb !== null and $block->isSolid() and !$block->isTransparent() and $bb->intersectsWith($this->getBoundingBox());
    }

    public function attack($damage, EntityDamageEvent $source) {
        if ($this->isKnockback() > 0) return;

        parent::attack($damage, $source);

        if ($source->isCancelled() || !($source instanceof EntityDamageByEntityEvent)) {
            return;
        }

        $this->stayTime = 0;
        $this->moveTime = 0;

        $damager = $source->getDamager();
        $motion = (new Vector3($this->x - $damager->x, $this->y - $damager->y, $this->z - $damager->z))->normalize();
        $this->motionX = $motion->x * 0.19;
        $this->motionZ = $motion->z * 0.19;
        if (($this instanceof FlyingEntity) && !($this instanceof Blaze)) {
            $this->motionY = $motion->y * 0.19;
        } else {
            $this->motionY = 0.6;
        }

        $this->checkAttackByTamedEntities($source);
    }

    public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4) {

    }

    public function entityBaseTick($tickDiff = 1, $EnchantL = 0) {
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = Entity::entityBaseTick($tickDiff);

        if ($this->isInsideOfSolid()) {
            $hasUpdate = true;
            $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_SUFFOCATION, 1);
            $this->attack($ev->getFinalDamage(), $ev);
        }

        if ($this->moveTime > 0) {
            $this->moveTime -= $tickDiff;
        }

        if ($this->attackTime > 0) {
            $this->attackTime -= $tickDiff;
        }

        Timings::$timerEntityBaseTick->stopTiming();
        return $hasUpdate;
    }

    public function move($dx, $dy, $dz): bool {
        Timings::$entityMoveTimer->startTiming();

        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;

        $list = $this->level->getCollisionCubes($this, $this->level->getTickRate() > 1 ? $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz));
        if ($this->isWallCheck()) {
            foreach ($list as $bb) {
                $dx = $bb->calculateXOffset($this->boundingBox, $dx);
            }
            $this->boundingBox->offset($dx, 0, 0);

            foreach ($list as $bb) {
                $dz = $bb->calculateZOffset($this->boundingBox, $dz);
            }
            $this->boundingBox->offset(0, 0, $dz);
        }
        foreach ($list as $bb) {
            $dy = $bb->calculateYOffset($this->boundingBox, $dy);
        }
        $this->boundingBox->offset(0, $dy, 0);

        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
        $this->checkChunks();

        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);

        Timings::$entityMoveTimer->stopTiming();
        return true;
    }

    public function targetOption(Creature $creature, float $distance): bool {
        return $this instanceof Monster && (!($creature instanceof Player) || ($creature->isSurvival() && $creature->spawned)) && $creature->isAlive() && !$creature->closed && $distance <= 81;
    }

    /**
     * This is called while moving around. This is specially important for entities like sheep etc. pp
     * which eat grass to grow their wool. They should return the block which is of interest to move
     * the entity there.
     *
     * @param array $blocksAround
     * @return bool|Block
     */
    public function isAnyBlockOfInterest(array $blocksAround) {
        return false;
    }

    /**
     * This method is used to determine if the attack comes from a player. When the player has tamed
     * entities - all will attack (when not already attacking another monster).
     *
     * @param EntityDamageEvent $source the event that has been raised
     */
    protected function checkAttackByTamedEntities(EntityDamageEvent $source) {
        // next: if the player has any tamed entities - they will attack this entitiy too - but only when not already
        // having a valid monster target
        if ($source instanceof EntityDamageByEntityEvent) {
            $attackedBy = $source->getDamager();
            if ($attackedBy instanceof Player) {
                // get all tamed entities in the world and search for those belonging to the player
                foreach ($attackedBy->getLevel()->getEntities() as $entity) {
                    if ($entity instanceof IntfTameable and
                        $entity->getOwner() !== null and
                        $entity->isTamed() and
                        strcasecmp($entity->getOwner()->getName(), $attackedBy->getName()) == 0 and
                        $entity instanceof BaseEntity and
                        !$entity->getBaseTarget() instanceof Monster
                    ) {
                        if ($this instanceof IntfTameable and $this->isTamed() and
                            strcasecmp($this->getOwner()->getName(), $attackedBy->getName()) == 0
                        ) {
                            // this entity belongs to the player. other tamed mobs shouldnt attack!
                            continue;
                        }
                        if ($entity->isSitting()) {
                            $entity->setSitting(false);
                        }
                        $entity->setBaseTarget($this);
                    }
                }
            }
        }
    }

    /**
     * Call this function in any entity you want the tamed mobs to attack when player gets attacked.
     * Take a look at Zombie Entity for usage!
     *
     * @param Entity $player
     */
    protected function checkTamedMobsAttack(Entity $player) {
        // check if the player has tamed mobs (wolf e.g.) if so - the wolves need to set
        // target to this one and attack it!
        if ($player instanceof Player) {
            foreach ($this->getTamedMobs($player) as $tamedMob) {
                $tamedMob->setBaseTarget($this);
                $tamedMob->stayTime = 0;
                if ($tamedMob instanceof Wolf and $tamedMob->isSitting()) {
                    $tamedMob->setSitting(false);
                }
            }
        }
    }

    /**
     * Returns all tamed mobs for the given player ...
     * @param Player $player
     * @return array
     */
    private function getTamedMobs(Player $player) {
        $tamedMobs = [];
        foreach ($player->getLevel()->getEntities() as $entity) {
            if ($entity instanceof IntfTameable and
                $entity->isTamed() and
                strcasecmp($entity->getOwner()->getName(), $player->getName()) === 0 and
                $entity->isAlive()
            ) {
                $tamedMobs[] = $entity;
            }
        }
        return $tamedMobs;
    }

    /**
     * Checks if checkTarget can be called. If not, this method returns false
     *
     * @return bool
     */
    protected function isCheckTargetAllowedBySkip(): bool {
        if ($this->checkTargetSkipCounter > PluginConfiguration::getInstance()->getCheckTargetSkipTicks()) {
            $this->checkTargetSkipCounter = 0;
            return true;
        } else {
            $this->checkTargetSkipCounter++;
            return false;
        }
    }


}
