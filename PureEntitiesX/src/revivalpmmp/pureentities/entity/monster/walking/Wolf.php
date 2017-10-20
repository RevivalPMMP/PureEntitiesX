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

namespace revivalpmmp\pureentities\entity\monster\walking;

use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use revivalpmmp\pureentities\components\BreedingComponent;
use revivalpmmp\pureentities\data\Color;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\animal\jumping\Rabbit;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;
use revivalpmmp\pureentities\entity\monster\Monster;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use revivalpmmp\pureentities\traits\Feedable;
use revivalpmmp\pureentities\traits\Tameable;
use revivalpmmp\pureentities\traits\Breedable;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class Wolf extends WalkingMonster implements IntfTameable, IntfCanBreed, IntfCanInteract {
    use Feedable, Breedable, Tameable;
    const NETWORK_ID = Data::WOLF;

    /**
     * Is needed for breeding functionality
     *
     * @var BreedingComponent
     */
    private $breedableClass;

    /**
     * Teleport distance - when does a tamed wolf start to teleport to it's owner?
     *
     * @var int
     */
    private $teleportDistance = 12;

    /**
     * Tamed wolves are walking aimlessly until they get too far away from the player. This describes the
     * max distance to the player
     *
     * @var int
     */
    private $followDistance = 10;

    private $angryValue = 0;

    private $collarColor = Color::RED;

    public function getSpeed(): float {
        return $this->speed;
    }

    public function initEntity() {
        parent::initEntity();
        $this->width = Data::WIDTHS[self::NETWORK_ID];
        $this->height = Data::HEIGHTS[self::NETWORK_ID];
        $this->speed = 1.2;

        $this->fireProof = false;
        $this->setDamage([0, 3, 4, 6]);


        $this->breedableClass = new BreedingComponent($this);

        $this->loadFromNBT();
        $this->tameFoods = array(
          Item::BONE
        );
        $this->feedableItems = array(
            Item::RAW_BEEF,
            Item::RAW_CHICKEN,
            Item::RAW_MUTTON,
            Item::RAW_PORKCHOP,
            Item::RAW_RABBIT,
            Item::COOKED_BEEF,
            Item::COOKED_CHICKEN,
            Item::COOKED_MUTTON,
            Item::COOKED_PORKCHOP,
            Item::COOKED_RABBIT,
        );
        $this->setAngry($this->isAngry() ? $this->angryValue : 0, true);
        $this->setTamed($this->isTamed());
        if ($this->isTamed()) {
            $this->mapOwner();
            $this->setCollarColor($this->collarColor);
            if ($this->owner === null) {
                PureEntities::logOutput("Wolf($this): is tamed but player not online. Cannot set tamed owner. Will be set when player logs in ..", PureEntities::NORM);
            }
        }
        $this->breedableClass->init();

        $this->teleportDistance = PluginConfiguration::getInstance()->getTamedTeleportBlocks();
        $this->followDistance = PluginConfiguration::getInstance()->getTamedPlayerMaxDistance();
    }

    /**
     * Returns the breedable class or NULL if not configured
     *
     * @return BreedingComponent
     */
    public function getBreedingComponent() {
        return $this->breedableClass;
    }

    /**
     * Returns the appropriate NetworkID associated with this entity
     * @return int
     */
    public function getNetworkId() {
        return self::NETWORK_ID;
    }

    public function getName(): string {
        return "Wolf";
    }

    /**
     * We've to override the method to have better control over the wolf (atm deciding if the
     * wolf is tamed and has to teleport to the owner when more than 12 blocks away)
     *
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool {
        if ($this->isClosed() or $this->getLevel() == null) return false;
        $this->checkFollowOwner();
        return parent::entityBaseTick($tickDiff);
    }

    /**
     * We need to override this function as the wolf is hunting for skeletons, rabbits and sheep when not tamed and wild.
     * When tamed and no other target is set (or is following player) the tamed wolf attack only skeletons!
     * @param bool $checkSkip
     */
    public function checkTarget(bool $checkSkip = true) {
        if (($checkSkip and $this->isCheckTargetAllowedBySkip()) or !$checkSkip) {
            if (!$this->isTamed() and !$this->getBaseTarget() instanceof Monster) {
                // is there any entity around that is attackable (skeletons, rabbits, sheep)
                foreach ($this->getLevel()->getNearbyEntities($this->boundingBox->grow(10, 10, 10), $this) as $entity) {
                    if ($entity instanceof Skeleton or $entity instanceof Rabbit or $entity instanceof Sheep and
                        $entity->isAlive()
                    ) {
                        $this->setBaseTarget($entity); // set the given entity as target ...
                        return;
                    }
                }
            }
            parent::checkTarget(false);
        }
    }

    // TODO: Evaluate best way to handle movement updates for tamed entities that are sitting.
    // Tamed entities should 'look at' or face their owner when the owner is close.

    /**
     * We need to override this method. When wolf is sitting, the entity shouldn't move!
     *
     * @param int $tickDiff
     * @return null|\pocketmine\math\Vector3
     */
    public function updateMove($tickDiff) {

        // count down angry property
        if ($this->isAngry()) {
            $this->setAngry($this->angryValue - $tickDiff);
        }

        if ($this->isSitting()) {
            // we need to call checkTarget otherwise the targetOption method is not called :/
            $this->checkTarget(false);
            return null;
        }
        return parent::updateMove($tickDiff);
    }

    /**
     * Loads data from NBT and stores to local variables
     */
    public function loadFromNBT() {
        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            if (isset($this->namedtag->Angry)) {
                $this->setAngry((int)$this->namedtag[NBTConst::NBT_KEY_ANGRY]);
            }
            if (isset($this->namedtag->Color)) {
                $this->setCollarColor($this->namedtag[NBTConst::NBT_KEY_COLLAR_COLOR]);
            }
            if (isset($this->namedtag->Sitting)) {
                $this->setSitting($this->namedtag[NBTConst::NBT_KEY_SITTING] === 1);
            }
            if (isset($this->namedtag->OwnerName)) {
                $this->ownerName = $this->namedtag[NBTConst::NBT_SERVER_KEY_OWNER_NAME];
                $this->tamed = true;
            }
            if ($this->ownerName !== null) {
                foreach ($this->getLevel()->getPlayers() as $levelPlayer) {
                    if (strcasecmp($levelPlayer->getName(), $this->namedtag->OwnerName) == 0) {
                        $this->owner = $levelPlayer;
                        break;
                    }
                }
            }
        }
        $this->breedableClass->saveNBT();
    }

    // TODO: Determine cause of collar color being improperly applied.

    /**
     * Saves important variables to the NBT
     */
    public function saveNBT() {
        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            parent::saveNBT();
            $this->namedtag->Angry = new IntTag(NBTConst::NBT_KEY_ANGRY, $this->angryValue);
            $this->namedtag->Color = new ByteTag(NBTConst::NBT_KEY_COLLAR_COLOR, $this->collarColor); // set collar color
            $this->namedtag->Sitting = new IntTag(NBTConst::NBT_KEY_SITTING, $this->sitting ? 1 : 0);
            if ($this->getOwnerName() !== null) {
                $this->namedtag->OwnerName = new StringTag(NBTConst::NBT_SERVER_KEY_OWNER_NAME, $this->getOwnerName()); // only for our own (server side)
            }
            if ($this->owner !== null) {
                $this->namedtag->OwnerUUID = new StringTag(NBTConst::NBT_KEY_OWNER_UUID, $this->owner->getUniqueId()->toString()); // set owner UUID
            }
        }
        $this->breedableClass->saveNBT();
    }

    /**
     * Returns true if the wolf is angry
     *
     * @return bool
     */
    public function isAngry(): bool {
        return $this->angryValue > 0;
    }

    public function setAngry(int $val, bool $init = false) {
        if ($val < 0) {
            $val = 0;
        }
        $valueBefore = $this->angryValue;
        $this->angryValue = $val;
        // only change the data property when aggression mode changes or in init phase
        if (($valueBefore > 0 and $val <= 0) or ($valueBefore <= 0 and $val > 0) or $init) {
            $this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_ANGRY, $val > 0);
        }
    }

    /**
     * Wolf gets attacked ...
     *
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source) {
        parent::attack($source);

        if (!$source->isCancelled()) {
            // when this is tamed and the owner attacks, the wolf doesn't get angry
            if (!$this->isTamed()) {
                $this->setAngry(1000);
            } else {
                // a tamed entity gets angry when attacked by another player (which is not owner)
                // or by a monster when tamed
                $attackedBy = $source->getEntity();
                if ($attackedBy instanceof Monster or ($attackedBy instanceof Player and
                        strcasecmp($attackedBy->getName(), $this->getOwner()->getName()) !== 0)
                ) {
                    $this->setAngry(1000);
                }
            }
        }
    }

    /**
     * Wolf attacks entity
     *
     * @param Entity $player
     */
    public function attackEntity(Entity $player) {
        if ($this->attackDelay > 10 && $this->distanceSquared($player) < 1.6) {
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK,
                MobDamageCalculator::calculateFinalDamage($player, $this->getDamage()));
            $player->attack($ev);

            $this->checkTamedMobsAttack($player);
        }
    }

    public function getDrops(): array {
        return [];
    }

    public function getMaxHealth(): int {
        return 8; // but only for wild ones, tamed ones: 20
    }

    private function onTameSuccess() {
        $this->setCollarColor(Color::RED);
    }

    /**
     * We've to override this!
     *
     * @return bool
     */
    public function isFriendly(): bool {
        return !$this->isAngry();
    }

    /**
     * Sets the collar color when tamed
     *
     * @param $collarColor
     */
    public function setCollarColor($collarColor) {
        if ($this->isTamed()) {
            $this->collarColor = $collarColor;

            if (!isset($this->namedtag[NBTConst::NBT_KEY_COLLAR_COLOR])) {
                $this->namedtag->Color = new ByteTag(NBTConst::NBT_KEY_COLLAR_COLOR, $collarColor); // set collar color
                $this->setDataProperty(self::DATA_COLOUR, self::DATA_TYPE_BYTE, $collarColor);

            } else {
                $this->namedtag[NBTConst::NBT_KEY_COLLAR_COLOR] = $this->collarColor;
                $this->setDataProperty(self::DATA_COLOUR, self::DATA_TYPE_BYTE, $collarColor);
            }
        }
    }

    /**
     * Returns the collar color of the wolf
     *
     * @return mixed
     */
    public function getCollarColor() {
        return $this->collarColor;
    }

    /**
     * This method has to be called as soon as an owner name is set. It searches online players for the owner name
     * and then sets it as owner here
     */
    public function mapOwner() {
        if ($this->ownerName !== null) {
            foreach ($this->getLevel()->getPlayers() as $player) {
                if (strcasecmp($this->ownerName, $player->getName()) == 0) {
                    $this->owner = $player;
                    PureEntities::logOutput("$this: mapOwner to $player", PureEntities::NORM);
                    break;
                }
            }
        }
    }

    /**
     * Checks if the wolf is tamed and not sitting and has a "physically" available owner.
     * If so and the distance to the owner is more than 12 blocks: set position to the position
     * of the owner.
     */
    private function checkFollowOwner() {
        if ($this->isTamed()) {
            if ($this->getOwner() !== null && !$this->isSitting() && !$this->isTargetMonsterOrAnimal()) {
                if ($this->getOwner()->distanceSquared($this) > $this->teleportDistance) {
                    $this->setAngry(0); // reset angry flag
                    $newPosition = $this->getPositionNearOwner($this->getOwner(), $this);
                    $this->teleport($newPosition !== null ? $newPosition : $this->getOwner()); // this should be better than teleporting directly onto player
                    PureEntities::logOutput("$this: teleport distance exceeded. Teleport myself near to owner.");
                } else if ($this->getOwner()->distanceSquared($this) > $this->followDistance) {
                    if ($this->getBaseTarget() !== $this->getOwner() and !$this->isTargetMonsterOrAnimal()) {
                        $this->setBaseTarget($this->getOwner());
                        PureEntities::logOutput("$this: follow distance exceeded. Set target to owner. Continue to follow.");
                    } else {
                        PureEntities::logOutput("$this: follow distance exceeded. But target already set to owner. Continue to follow.");
                    }
                } else if ($this->getBaseTarget() === null or $this->getBaseTarget() === $this->getOwner()) {
                    // no distance exceeded. if the target is the owner - forget him as target and set a random one
                    $this->findRandomLocation();
                    PureEntities::logOutput("$this: set random walking location. Continue to idle.");
                }
            }
        }
    }

    public function getKillExperience(): int {
        if ($this->getBreedingComponent()->isBaby()) {
            return mt_rand(1, 7);
        }
        return mt_rand(1, 3);
    }


}
