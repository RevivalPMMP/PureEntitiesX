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

namespace revivalpmmp\pureentities\entity\animal\walking;

use pocketmine\entity\Creature;
use pocketmine\nbt\NBT;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\components\BreedingComponent;
use pocketmine\nbt\tag\IntTag;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfCanPanic;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\traits\Feedable;
use revivalpmmp\pureentities\traits\Tameable;


// TODO: Add 'Begging Mode' for untamed ocelots.
// TODO: Fix tamed ocelot response to Owner in combat (should avoid fights).
// TODO: Add trigger to tame() so that a failure to tame will trigger breeding mode.



class Ocelot extends WalkingAnimal implements IntfTameable, IntfCanBreed, IntfCanInteract, IntfCanPanic {
    use Tameable, Feedable;
    const NETWORK_ID = Data::OCELOT;

    private $comfortObjects = array(
        Item::BED,
        Item::LIT_FURNACE,
        Item::BURNING_FURNACE,
        Item::CHEST
    );

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
     * Tamed cats will explore around the player unless commanded to sit. This describes the
     * max distance to the player.
     *
     * @var int
     */
    private $followDistance = 10;

    /**
     * This will be set to true when the ocelot has been given a sit command by its owner.
     *
     * @var bool
     */
    private $commandedToSit = false;

    private $catType = 0; // 0 = Wild Ocelot, 1 = Tuxedo, 2 = Tabby, 3 = Siamese

    public function getSpeed(): float {
        return $this->speed;
    }

    public function getNormalSpeed(): float {
        return 1.2;
    }

    public function getPanicSpeed(): float {
        return 1.4;
    }

    public function getBeggingSpeed(): float {
        return 0.8;
    }

    public function initEntity() {
        parent::initEntity();
        $this->width = Data::WIDTHS[self::NETWORK_ID];
        $this->height = Data::HEIGHTS[self::NETWORK_ID];
        $this->speed = 1.2;

        $this->fireProof = false;

        $this->breedableClass = new BreedingComponent($this);

        $this->tameFoods = array(
            Item::RAW_FISH,
            Item::RAW_SALMON
        );

        $this->feedableItems = array(
            Item::RAW_FISH,
            Item::RAW_SALMON
        );

        $this->loadFromNBT();

        if ($this->isTamed()) {
            $this->mapOwner();
            if ($this->owner === null) {
                PureEntities::logOutput("Ocelot($this): is tamed but player not online. Cannot set tamed owner. Will be set when player logs in ..", PureEntities::NORM);
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

    /**
     * Returns the items that can be fed to the entity
     *
     * @return array
     */
    public function getFeedableItems() {
        return $this->feedableItems;
    }

    /**
     * Returns an array of items that tamed cats are attracted too.
     *
     * @return array
     */
    public function getComfortObjects() {
        return $this->comfortObjects;
    }


    public function getName(): string {
        return "Ocelot";
    }

    /**
     * We have to override the method to have better control over the ocelot (atm deciding if the
     * ocelot is tamed and needs to teleport closer to the owner)
     *
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool {
        $this->checkFollowOwner();
        return parent::entityBaseTick($tickDiff);
    }

    /**
     * We need to override this function as the ocelot can hunt for chickens when not tamed.
     * When tamed and no other target is set (or is following player) the tamed ocelot should attack nothing.
     * @param bool $checkSkip
     */
    public function checkTarget(bool $checkSkip = true) {
        if (($checkSkip and $this->isCheckTargetAllowedBySkip()) or !$checkSkip) {
            if (!$this->isTamed() and !$this->getBaseTarget() instanceof Chicken) {
                // is there any entity around that can be attacked (chickens)
                // Need to reconsider this method and test response when multiple matches are within
                // the bounding box across multiple checks.  Ocelots should be able to 'stalk' a target
                // after choosing one instead of jumping between multiple entities as targets.
                foreach ($this->getLevel()->getNearbyEntities($this->boundingBox->grow(10, 10, 10), $this) as $entity) {
                    if ($entity instanceof Chicken and $entity->isAlive()) {
                        $this->setBaseTarget($entity); // set the given entity as target ...
                        return;
                    }
                }
            }
            parent::checkTarget(false);
        }
    }

    /**
     * We need to override this method. When a tameable entity is sitting, the entity shouldn't move
     * except to face its owner when the owner is close.
     *
     * @param int $tickDiff
     * @return null|\pocketmine\math\Vector3
     */
    public function updateMove($tickDiff) {

        if ($this->isSitting()) {
            // we need to call checkTarget otherwise the targetOption method is not called :/
            $this->checkTarget(false);
            return null;
        }
        return parent::updateMove($tickDiff);
    }

    /**
     * Loads data from nbt and stores to local variables
     */
    public function loadFromNBT() {
        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            if (isset($this->namedtag->Variant)) {
                $this->setCatType($this->namedtag[NBTConst::NBT_KEY_CATTYPE]);
            }
            if (isset($this->namedtag->Sitting)) {
                $this->setSitting($this->namedtag[NBTConst::NBT_KEY_SITTING] === 1);

                // Until an appropriate NBT key can be attached to this, if the entity is sitting when loaded,
                // commandedToSit will be set to true so that it doesn't teleport to it's owner by accident.
                $this->setCommandedToSit($this->isSitting());
            }
            if (isset($this->namedtag->OwnerName)) {
                $this->ownerName = $this->namedtag[NBTConst::NBT_SERVER_KEY_OWNER_NAME];
                $this->setTamed(true);
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

     public function saveNBT() {
         if (PluginConfiguration::getInstance()->getEnableNBT()) {
             parent::saveNBT();
             $this->namedtag->Variant = new ByteTag(NBTConst::NBT_KEY_CATTYPE, $this->catType); // sets ocelot skin
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

    public function targetOption(Creature $creature, float $distance): bool {

        if ($creature instanceof Player) {
            return $creature->spawned && $creature->isAlive() && !$creature->isClosed() && $creature->getInventory()->getItemInHand()->getId() == Item::RAW_FISH && $distance <= 49;
        }
        return false;
    }

    public function getDrops(): array {
        return [];
    }

    public function getMaxHealth(): int {
        return 10;
    }

    private function onTameSuccess() {
        $this->setCatType(mt_rand(1,3)); // Randomly chooses a tamed skin
    }

    private function onTameFail() {
        // Need to make it so that the ocelot will enter breeding mode on tame fail.
        return;
    }

    /**
     * This function is used to set the commandedToSit flag.
     * This should only be called when the owner of a tame
     * ocelot commands it to sit or gives it a command to stand
     * when it did not seat itself.
     *
     * @param bool $command
     */

    public function setCommandedToSit(bool $command = true)
    {
     $this->commandedToSit = $command;
    }

    /**
     * Sets the skin type of the ocelot.
     * 0 = Wild Ocelot, 1 = Tuxedo, 2 = Tabby, 3 = Siamese
     *
     * @param int $type
     */
    public function setCatType(int $type=0) {
        $this->catType = $type;
        $this->setDataProperty(self::DATA_VARIANT,self::DATA_TYPE_INT, $type);
    }

    /**
     * Returns which skin is set in catType
     * 0 = Wild Ocelot, 1 = Tuxedo, 2 = Tabby, 3 = Siamese
     *
     * @return int
     */
    public function getCatType(): int {
        return $this->catType;
    }



    /**
     * Checks if the ocelot is tamed, not sitting and has a "physically" available owner.
     * If so and the distance to the owner is more than 12 blocks: set position to the position
     * of the owner.
     */
    private function checkFollowOwner() {
        if ($this->isTamed()) {
            if ($this->getOwner() !== null && !$this->isSitting()) {
                if ($this->getOwner()->distanceSquared($this) > $this->teleportDistance) {
                    $newPosition = $this->getPositionNearOwner($this->getOwner(), $this);
                    $this->teleport($newPosition !== null ? $newPosition : $this->getOwner()); // this should be better than teleporting directly onto player
                    PureEntities::logOutput("$this: teleport distance exceeded. Teleport myself near to owner.");
                } else if ($this->getOwner()->distanceSquared($this) > $this->followDistance) {
                    if ($this->getBaseTarget() !== $this->getOwner()) {
                        $this->setBaseTarget($this->getOwner());
                        PureEntities::logOutput("$this: follow distance exceeded. Set target to owner. Continue to follow.");
                    } else {
                        PureEntities::logOutput("$this: follow distance exceeded. But target already set to owner. Continue to follow.");
                    }
                } else if ($this->getBaseTarget() === null or $this->getBaseTarget() === $this->getOwner()) {
                    // no distance exceeded. if the target is the owner, set a random one instead.
                    $this->findRandomLocation();
                    PureEntities::logOutput("$this: set random walking location. Continue to idle.");
                }
            }
        }
    }


    /**
     * Generates and returns a random value from 1 to 3.
     * This is used to determine number of XP Orbs dropped
     * after killing the entity.
     * @return int
     */

    // This function needs to be moved to a parent class
    // that can be used by all mobs that drop experience
    // when killed.
    //
    // When moved, method needs to be changed to
    // getKillExperience(int $minXp, int $maxXp) {
    //     return mt_rand($minXp, $maxXp);
    // }

    public function getKillExperience(): int {
        return mt_rand(1, 3);
    }

}
