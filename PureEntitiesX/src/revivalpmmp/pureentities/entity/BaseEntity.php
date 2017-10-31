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
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use revivalpmmp\pureentities\components\IdlingComponent;
use revivalpmmp\pureentities\data\Data;
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
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\walking\Wolf;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanPanic;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\utils\TickCounter;

abstract class BaseEntity extends Creature {

    public $stayTime = 0;
    protected $moveTime = 0;

    /** @var float */
    public $eyeHeight = null;

    /** @var Vector3|Entity */
    private $baseTarget = null;

    private $movement = true;
    private $friendly = false;
    private $wallcheck = true;
    protected $fireProof = false;
    private $maxJumpHeight = 1; // default: 1 block jump height - this should be 2 for horses e.g.
    protected $checkTargetSkipTicks = 1; // default: no skip
    public $width = 1.0;
    public $height = 1.0;
    public $speed = 1.0;
    /**
     * @var null|TickCounter
     */
    protected $panicCounter = null;

    /**
     * @var int
     */
    private $checkTargetSkipCounter = 0;

    /**
     * @var IdlingComponent
     */
    protected $idlingComponent;

    protected $panicEnabled = true;
    protected $panicTicks = 100;

    protected $maxAge = 0;

    public function __destruct() {
    }

    public function __construct(Level $level, CompoundTag $nbt) {
        $this->idlingComponent = new IdlingComponent($this);
        $this->checkTargetSkipTicks = PluginConfiguration::getInstance()->getCheckTargetSkipTicks();
        $this->panicEnabled = PluginConfiguration::getInstance()->getEnablePanic();
        $this->panicTicks = PluginConfiguration::getInstance()->getPanicTicks();
        $this->maxAge = PluginConfiguration::getInstance()->getMaxAge();
        $this->maxDeadTicks = 23;
        parent::__construct($level, $nbt);
        if ($this->eyeHeight === null) {
            $this->eyeHeight = $this->height / 2 + 0.1;
        }
        $this->namedtag->generatedByPEX = new ByteTag("generatedByPEX",1);
    }

    public abstract function updateMove($tickDiff);

    /**
     * Should return the experience dropped by the entity when killed
     * @return int
     */
    public function getKillExperience(): int {
        return 0; // default no experience drops
    }

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
            PureEntities::logOutput("$this: setBaseTarget to $baseTarget", PureEntities::DEBUG);
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
        return $this->speed;
    }

    /**
     * @return int
     */
    public function getMaxJumpHeight(): int {
        return $this->maxJumpHeight;
    }

    public function initEntity() {
        parent::initEntity();

        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            if (isset($this->namedtag->Movement)) {
                $this->setMovement($this->namedtag["Movement"]);
            }

            if (isset($this->namedtag->WallCheck)) {
                $this->setWallCheck($this->namedtag["WallCheck"]);
            }

            if (isset($this->namedtag->AgeInTicks)) {
                $this->age = $this->namedtag["AgeInTicks"];
            }
        }
        $this->dataProperties[self::DATA_FLAG_NO_AI] = [self::DATA_TYPE_BYTE, 1];


        $this->idlingComponent->loadFromNBT();
    }

    public function saveNBT() {
        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            parent::saveNBT();
            $this->namedtag->Movement = new ByteTag("Movement", $this->isMovement());
            $this->namedtag->WallCheck = new ByteTag("WallCheck", $this->isWallCheck());
            $this->namedtag->AgeInTicks = new IntTag("AgeInTicks", $this->age);
        }
        $this->idlingComponent->saveNBT();
    }

    public function spawnTo(Player $player) {
        if (
            !isset($this->hasSpawned[$player->getLoaderId()])
            && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])
        ) {
            $pk = new AddEntityPacket();
            $pk->entityRuntimeId = $this->getID();
            $pk->type = static::NETWORK_ID;
            $pk->position = $this->asVector3();
            $pk->motion = $this->getMotion();
            $pk->yaw = $this->yaw;
            $pk->pitch = $this->pitch;
            $pk->metadata = $this->dataProperties;
            $player->dataPacket($pk);

            $this->hasSpawned[$player->getLoaderId()] = $player;
        }
    }

    public function updateMovement() {
        if (!$this->isClosed() && $this->getLevel() !== null) {
            parent::updateMovement();
        }
    }

    public function isInsideOfSolid(): bool {
        $block = $this->level->getBlock($this->temporalVector->setComponents(Math::floorFloat($this->x), Math::floorFloat($this->y + $this->height - 0.18), Math::floorFloat($this->z)));
        $bb = $block->getBoundingBox();
        return $bb !== null and $block->isSolid() and !$block->isTransparent() and $bb->intersectsWith($this->getBoundingBox());
    }

    /**
     * Entity gets attacked by another entity / explosion or something similar
     *
     * @param EntityDamageEvent $source the damage event
     */
    public function attack(EntityDamageEvent $source) {

        if ($this->isClosed() || $source->isCancelled() || !($source instanceof EntityDamageByEntityEvent)) {
            return;
        }

        if ($this->isKnockback() > 0) return;

        // "wake up" entity - it gets attacked!
        $this->idlingComponent->stopIdling(1, true);

        parent::attack($source);

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

        // panic mode - here we check if the entity can enter panic mode and so on
        if ($this instanceof IntfCanPanic and $damager instanceof Player and !$this->isInPanic() and $this->panicEnabled) {
            $this->setBaseTarget(new Vector3($this->x - ($damager->x * 10), $this->y - $damager->y, ($this->z - $damager->z * 10)));
            $this->setInPanic(); // this should prevent to search for other targets and increase run speed
        }

        $this->checkAttackByTamedEntities($source);
    }

    public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4) {

    }

    public function entityBaseTick(int $tickDiff = 1): bool {
        Timings::$timerEntityBaseTick->startTiming();
        // check if it needs to despawn

        $hasUpdate = Entity::entityBaseTick($tickDiff);

        // Checking this first because there's no reason to keep going if we know
        // we're going to despawn the entity.
        if ($this->checkDespawn()) {
            Timings::$timerEntityBaseTick->stopTiming();
            return false;
        }

        if ($this->isInsideOfSolid()) {
            $hasUpdate = true;
            $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_SUFFOCATION, 1);
            $this->attack($ev);
        }

        if ($this->moveTime > 0) {
            $this->moveTime -= $tickDiff;
        }

        if ($this->attackTime > 0) {
            $this->attackTime -= $tickDiff;
        }

        // check panic tick
        $this->panicTick($tickDiff);

        Timings::$timerEntityBaseTick->stopTiming();
        return $hasUpdate;
    }

    /**
     * This method checks if an entity should despawn - if so, the entity is closed
     * @return bool
     */
    private function checkDespawn(): bool {
        // when entity is at least x ticks old and it's not tamed, we should remove it
        if ($this->age > $this->maxAge and
            (!$this instanceof IntfTameable or ($this instanceof IntfTameable and !$this->isTamed()))
        ) {
            PureEntities::logOutput("Despawn entity " . $this->getName(), PureEntities::NORM);
            $this->close();
            return true;
        }
        return false;
    }

    public function move(float $dx, float $dy, float $dz): bool {
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
        return $this instanceof Monster && (!($creature instanceof Player) || ($creature->isSurvival() && $creature->spawned)) && $creature->isAlive() && !$creature->isClosed() && $distance <= 81;
    }

    /**
     * This is called while moving around. This is specially important for entities like sheep etc. pp
     * which eat grass to grow their wool. This method should check at which block the entity is currently
     * staying / moving. If it is suitable - it should eat grass or something similar
     *
     * @return bool|Block
     */
    public function isCurrentBlockOfInterest() {
        return false;
    }

    /**
     * This method is used to determine if the attack comes from a player. When the player has tamed
     * entities - all will attack (when not already attacking another monster).
     *
     * @param EntityDamageEvent $source the event that has been raised
     */
    protected function checkAttackByTamedEntities(EntityDamageEvent $source) {
        // next: if the player has any tamed entities - they will attack this entity too - but only when not already
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
                            // this entity belongs to the player. other tamed mobs shouldn't attack!
                            continue;
                        }
                        /**
                         * @var $entity IntfTameable
                         */
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
        if ($this->checkTargetSkipCounter > $this->checkTargetSkipTicks) {
            $this->checkTargetSkipCounter = 0;
            return true;
        } else {
            $this->checkTargetSkipCounter++;
            return false;
        }
    }

    /**
     * Checks if dropping loot is allowed.
     * @return bool true when allowed, false when not
     */
    protected function isLootDropAllowed(): bool {
        $lastDamageEvent = $this->getLastDamageCause();
        if ($lastDamageEvent !== null and $lastDamageEvent instanceof EntityDamageByEntityEvent) {
            return $lastDamageEvent->getDamager() instanceof Player;
        }
        return false;
    }

    /**
     * This has to be called by onUpdate / entityBaseTick
     *
     * @param int $tickDiff
     * @return bool true if the entity is still in panic
     */
    public function panicTick(int $tickDiff = 1): bool {
        if ($this->isInPanic()) {
            PureEntities::logOutput("$this: is in panic. Checking if expired.");
            if ($this->panicCounter->isTicksExpired($tickDiff)) {
                PureEntities::logOutput("$this: panic expired. Resetting entity status.");
                $this->unsetInPanic();
                return false; // not in panic anymore
            }
            PureEntities::logOutput("$this: still in panic.");
            return true; // still in panic
        }
        PureEntities::logOutput("$this: not in panic");
        return false; // no in panic
    }

    /**
     * Checks if this entity is in panic mode (flee mode)
     *
     * @return bool
     */
    public function isInPanic() {
        return $this->panicCounter !== null;
    }

    /**
     * Sets an entity in panic mode.
     */
    public function setInPanic() {
        $this->panicCounter = new TickCounter($this->panicTicks); // x ticks in panic
        /**
         * @var $this IntfCanPanic
         */
        if (!$this instanceof IntfCanBreed or ($this instanceof IntfCanBreed && !$this->getBreedingComponent()->isBaby())) {
            $this->speed = $this->getPanicSpeed();
        }
        $this->moveTime = $this->panicTicks; // move for x ticks
        PureEntities::logOutput("$this: in panic now [speed:" . $this->speed . "] [duration:" . $this->panicTicks . "]");
    }

    /**
     * Unsets panic for an entity
     */
    public function unsetInPanic() {
        $this->panicCounter = null;
        /**
         * @var $this IntfCanPanic
         */
        $this->speed = $this->getNormalSpeed();
        $this->setBaseTarget(null);
        PureEntities::logOutput("$this: unset panic now [speed:" . $this->speed . "]");
    }


}
