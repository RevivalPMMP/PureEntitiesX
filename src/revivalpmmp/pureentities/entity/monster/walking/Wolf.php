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
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\jumping\Rabbit;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;
use revivalpmmp\pureentities\entity\monster\Monster;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use revivalpmmp\pureentities\features\BreedingExtension;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\data\Data;

class Wolf extends WalkingMonster implements IntfTameable, IntfCanBreed, IntfCanInteract {
    const NETWORK_ID = Data::WOLF;

    public $width = 0.72;
    public $height = 0.9;

    const RED = 14;

    const NBT_KEY_COLLAR_COLOR = "CollarColor"; // 0 -14 (14 - RED)
    const NBT_KEY_OWNER_UUID = "OwnerUUID"; // string
    const NBT_KEY_SITTING = "Sitting"; // 1 or 0 (true/false)
    const NBT_KEY_ANGRY = "Angry"; // 0 - not angry, > 0 angry

    // this is our own tag - only for server side ...
    const NBT_SERVER_KEY_OWNER_NAME = "OwnerName";

    private $feedableItems = array(
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

    private $tameFoods = array(
        Item::BONE
    );

    /**
     * Is needed for breeding functionality
     *
     * @var BreedingExtension
     */
    private $breedableClass;

    public function getSpeed(): float {
        return 1.2;
    }

    public function initEntity() {
        parent::initEntity();

        $this->fireProof = false;
        $this->setDamage([0, 3, 4, 6]);

        $this->setAngry($this->isAngry());
        $this->setTamed($this->isTamed());
        $this->setSitting($this->isSitting());
        if ($this->isTamed()) {
            $player = $this->getOwner();
            if ($player !== null) {
                $this->setOwner($player);
            } else {
                PureEntities::logOutput("Wolf($this): is tamed but player not online. Cannot set tamed owner. Will be set when player logs in ..", PureEntities::NORM);
            }
        }

        $this->breedableClass = new BreedingExtension($this);
        $this->breedableClass->init();
    }

    /**
     * Returns the breedable class or NULL if not configured
     *
     * @return BreedingExtension
     */
    public function getBreedingExtension() {
        return $this->breedableClass;
    }

    /**
     * Returns the appropiate NetworkID associated with this entity
     * @return int
     */
    public function getNetworkId() {
        return self::NETWORK_ID;
    }

    /**
     * Returns the items that can be fed to the entity
     *
     * @return array
     */
    public function getFeedableItems() {
        return $this->feedableItems;
    }

    /**
     * Returns the items the entity can be tamed with (maybe multiple!)
     *
     * @return array
     */
    public function getTameFoods() {
        return $this->tameFoods;
    }


    public function getName() {
        return "Wolf";
    }

    /**
     * We've to override the method to have better control over the wolf (atm deciding if the
     * wolf is tamed and has to teleport to the owner when more than 12 blocks away)
     *
     * @param int $tickDiff
     * @param int $EnchantL
     * @return bool
     */
    public function entityBaseTick($tickDiff = 1, $EnchantL = 0) {
        $this->checkTeleport();
        return parent::entityBaseTick($tickDiff, $EnchantL);
    }

    /**
     * We need to override this function as the wolf is hunting for skeletons, rabbits and sheeps when not tamed and wild.
     * When tamed and no other target is set (or is following player) the tamed wolf attack only skeletons!
     *
     */
    public function checkTarget(bool $checkSkip = true) {
        if (($checkSkip and $this->isCheckTargetAllowedBySkip()) or !$checkSkip) {
            if (!$this->isTamed() and !$this->getBaseTarget() instanceof Monster) {
                // is there any entity around that is attackable (skeletons, rabbits, sheeps)
                foreach ($this->getLevel()->getNearbyEntities($this->boundingBox->grow(10, 10, 10), $this) as $entity) {
                    if ($entity instanceof Skeleton or $entity instanceof Rabbit or $entity instanceof Sheep and
                        $entity->isAlive()) {
                        $this->setBaseTarget($entity); // set the given entity as target ...
                        return;
                    }
                }
            }
            return parent::checkTarget(false);
        }
    }

    /**
     * We need to override this method. When wolf is sitting, the entity shouldn't move!
     *
     * @param int $tickDiff
     * @return null|\pocketmine\math\Vector3
     */
    public function updateMove($tickDiff) {

        // count down angry property
        if ($this->isAngry()) {
            $this->setAngry($this->getAngryValue() - $tickDiff);
        }

        if ($this->isSitting()) {
            // we need to call checkTarget otherwise the targetOption method is not called :/
            $this->checkTarget(false);
            return null;
        }
        return parent::updateMove($tickDiff);
    }

    /**
     * Returns true if the wolf is angry
     *
     * @return bool
     */
    public function isAngry(): bool {
        if (!isset($this->namedtag->Angry)) {
            $this->namedtag->Angry = new IntTag(self::NBT_KEY_ANGRY, 0); // set not angry
        }
        return $this->namedtag[self::NBT_KEY_ANGRY] > 0;
    }

    /**
     * Returns the value currently set for angry. We need this method to
     * countdown to 0 when the wolf is angry
     *
     * @return int
     */
    private function getAngryValue(): int {
        $value = 0;
        if ($this->isAngry()) {
            $value = $this->namedtag[self::NBT_KEY_ANGRY];
        }
        return $value;
    }

    public function setAngry(int $val) {
        if ($val < 0) {
            $val = 0;
        }
        $this->namedtag->Angry = new IntTag(self::NBT_KEY_ANGRY, $val);
    }

    /**
     * Wolf gets attacked ...
     *
     * @param float $damage
     * @param EntityDamageEvent $source
     * @return mixed
     */
    public function attack($damage, EntityDamageEvent $source) {
        parent::attack($damage, $source);

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

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops() {
        return [];
    }

    public function getMaxHealth() {
        return 8; // but only for wild ones, tamed ones: 20
    }


    // -----------------------------------------------------------------------------------------------
    // TAMING functionality
    // -----------------------------------------------------------------------------------------------
    /**
     * Call this method when a player tries to tame an entity
     *
     * @param Player $player
     * @return bool
     */
    public function tame(Player $player): bool {
        $tameSuccess = mt_rand(0, 2) === 0; // 1/3 chance of taiming succeeds
        $itemInHand = $player->getInventory()->getItemInHand();
        if ($itemInHand != null) {
            $player->getInventory()->getItemInHand()->setCount($itemInHand->getCount() - 1);
        }
        if ($tameSuccess) {
            $pk = new EntityEventPacket();
            $pk->eid = $this->getId();
            $pk->event = EntityEventPacket::TAME_SUCCESS; // this "plays" success animation on entity
            $player->dataPacket($pk);

            // set the properties accordingly
            $this->setTamed(true);
            $this->setOwner($player);
            $this->setSitting(true);

        } else {
            $pk = new EntityEventPacket();
            $pk->eid = $this->getId();
            $pk->event = EntityEventPacket::TAME_FAIL; // this "plays" fail animation on entity
            $player->dataPacket($pk);
        }
        return $tameSuccess;
    }

    /**
     * Sets this entity tamed and belonging to the player
     *
     * @param bool $tamed
     */
    public function setTamed(bool $tamed) {
        if ($tamed) {
            $this->namedtag->CollarColor = new ByteTag(self::NBT_KEY_COLLAR_COLOR, self::RED); // set collar color
            $this->setDataProperty(self::DATA_COLOUR, self::DATA_TYPE_BYTE, self::RED); // collar color RED (because it's tamed!)
            $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_TAMED, true); // set tamed
        }
    }

    /**
     * Only returns true when this entity is tamed and owned by a player (who is not necessary online!)
     *
     * @return bool
     */
    public function isTamed(): bool {
        return isset($this->namedtag->OwnerName);
    }

    /**
     * Returns the owner of this entity. When isTamed is true and this method returns NULL the player is offline!
     *
     * @return null|Player
     */
    public function getOwner() {
        /** @var Player $player */
        $player = null;
        if (isset($this->namedtag->OwnerName)) {
            foreach ($this->getLevel()->getPlayers() as $levelPlayer) {
                if (strcasecmp($levelPlayer->getName(), $this->namedtag->OwnerName) == 0) {
                    $player = $levelPlayer;
                    break;
                }
            }
        }
        return $player;
    }

    /**
     * Sets the owner of the wolf
     *
     * @param Player $player
     */
    public function setOwner(Player $player) {
        $this->namedtag->OwnerUUID = new StringTag(self::NBT_KEY_OWNER_UUID, $player->getUniqueId()->toString()); // set owner UUID
        $this->setDataProperty(self::DATA_OWNER_EID, self::DATA_TYPE_LONG, $player->getId()); // set owner entity id
        $this->namedtag->OwnerName = new StringTag(self::NBT_SERVER_KEY_OWNER_NAME, $player->getName()); // only for our own (server side)
        $this->setBaseTarget($player);
    }

    /**
     * Set the wolf sitting or not
     * @param bool $sit
     */
    public function setSitting(bool $sit) {
        $this->namedtag->Sitting = new IntTag(self::NBT_KEY_SITTING, $sit ? 1 : 0);
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SITTING, $sit);
    }

    /**
     * Returns if the wolf is sitting or not
     *
     * @return bool
     */
    public function isSitting(): bool {
        if (!isset($this->namedtag->Sitting)) {
            $this->namedtag->Sitting = new ByteTag(self::NBT_KEY_SITTING, 0); // set not sitting (by default)
        }
        return $this->namedtag[self::NBT_KEY_SITTING] === 1;
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
     * Checks if the wolf is tamed and not sitting and has a "physically" available owner.
     * If so and the distance to the owner is more than 12 blocks: set position to the position
     * of the owner.
     */
    private function checkTeleport() {
        if ($this->isTamed() && $this->getOwner() !== null && !$this->isSitting()) {
            if ($this->getOwner()->distanceSquared($this) > 12) {
                $this->setAngry(0); // reset angry flag
                $this->setPosition($this->getOwner());
            }
        }
    }

}
