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
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\components\BreedingComponent;
use pocketmine\nbt\tag\IntTag;
use revivalpmmp\pureentities\entity\monster\Monster;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfCanPanic;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\data\Data;


// TODO: Ocelot skin setting does not persist on server restart..
// TODO: Add 'Begging Mode' for untamed ocelots.
// TODO: Fix tamed ocelot response to Owner in combat (should avoid fights).
// TODO: Consider changing $feedableItems, $tameItems, and $comfortObjects to constants.
// TODO: Add trigger to tame() so that a failure to tame will trigger breeding mode.



class Ocelot extends WalkingAnimal implements IntfTameable, IntfCanBreed, IntfCanInteract, IntfCanPanic {
    const NETWORK_ID = Data::OCELOT;

    public $width = 0.8;
    public $height = 0.8;
    public $speed = 1.2;

    const NBT_KEY_OWNER_UUID = "OwnerUUID"; // string
    const NBT_KEY_SITTING = "Sitting"; // 1 or 0 (true/false)
    const NBT_KEY_CATTYPE = "Variant"; // 0 = Wild Ocelot, 1 = Tuxedo, 2 = Tabby, 3 = Siamese

    // this is our own tag - only for server side ...
    const NBT_SERVER_KEY_OWNER_NAME = "OwnerName";


    /**
     * List of items that can eaten by ocelots
     *
     * @var array
     */
    private $feedableItems = array(
        Item::RAW_FISH,
        Item::RAW_SALMON
    );

    /**
     * List of items that can be used to tame ocelots.
     *
     * @var array
     */
    private $tameFoods = array(
        Item::RAW_FISH,
        Item::RAW_SALMON
    );


    /**
     * List of items that will attract tamed ocelots.
     * You can consider these Minecraft Catnip.
     *
     * @var array
     */
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
     * This will be set to true when the ocelot has been given a sit command by it's owner.
     *
     * @var bool
     */
    private $commandedToSit = false;

    // --------------------------------------------------
    // nbt variables
    // --------------------------------------------------

    private $tamed = false;

    /**
     * @var null|Player
     */
    private $owner = null;

    private $catType = 0; // 0 = Wild Ocelot, 1 = Tuxedo, 2 = Tabby, 3 = Siamese

    private $sitting = false;

    private $ownerName = null;

    // End of NBT Variables


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

        $this->fireProof = false;

        $this->breedableClass = new BreedingComponent($this);

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
     * Returns the items the entity can be tamed with (maybe multiple!)
     *
     * @return array
     */
    public function getTameFoods() {
        return $this->tameFoods;
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
            if (!$this->isTamed() and !$this->getBaseTarget() instanceof Monster) {
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
     * We need to override this method. When ocelot is sitting, the entity shouldn't move!
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
                $this->setCatType($this->namedtag[self::NBT_KEY_CATTYPE]);
            }
            if (isset($this->namedtag->Sitting)) {
                $this->setSitting($this->namedtag[self::NBT_KEY_SITTING] === 1);
            }
            if (isset($this->namedtag->OwnerName)) {
                $this->ownerName = $this->namedtag[self::NBT_SERVER_KEY_OWNER_NAME];
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
             $this->namedtag->Variant = new ByteTag(self::NBT_KEY_CATTYPE, $this->catType); // sets ocelot skin
             $this->namedtag->Sitting = new IntTag(self::NBT_KEY_SITTING, $this->sitting ? 1 : 0);
             if ($this->getOwnerName() !== null) {
                 $this->namedtag->OwnerName = new StringTag(self::NBT_SERVER_KEY_OWNER_NAME, $this->getOwnerName()); // only for our own (server side)
             }
             if ($this->owner !== null) {
                 $this->namedtag->OwnerUUID = new StringTag(self::NBT_KEY_OWNER_UUID, $this->owner->getUniqueId()->toString()); // set owner UUID
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
        // This shouldn't be necessary but just in case...
        if ($this->isTamed()) {
            return null;
        }

        $tameSuccess = mt_rand(0, 2) === 0; // 1/3 chance of taming succeeds
        $itemInHand = $player->getInventory()->getItemInHand();
        if ($itemInHand != null) {
            $player->getInventory()->getItemInHand()->setCount($itemInHand->getCount() - 1);
        }
        if ($tameSuccess) {
            $pk = new EntityEventPacket();
            $pk->entityRuntimeId = $this->getId();
            $pk->event = EntityEventPacket::TAME_SUCCESS; // this "plays" success animation on entity
            $player->dataPacket($pk);

            // set the properties accordingly
            $this->setTamed(true);
            $this->setOwner($player);
            $this->setSitting(true);
            $this->setCatType(mt_rand(1,3)); // Randomly chooses a tamed skin

        } else {
            $pk = new EntityEventPacket();
            $pk->entityRuntimeId = $this->getId();
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
    public function setTamed(bool $option) {
        if ($option) {
            $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_TAMED, true); // set tamed
        } else {
            $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_TAMED, false); // set not tamed
        }
        $this->tamed = $option;
    }

    /**
     * Only returns true when this entity is tamed and owned by a player (who is not necessary online!)
     *
     * @return bool
     */
    public function isTamed(): bool {
        return $this->tamed;
    }

    /**
     * Returns the owner of this entity. When isTamed is true and this method returns NULL the player is offline!
     *
     * @return null|Player
     */
    public function getOwner() {
        return $this->owner;
    }

    public function getOwnerName() {
        return $this->ownerName;
    }

    /**
     * Sets the owner of the wolf
     *
     * @param Player $player
     */
    public function setOwner(Player $player) {
        $this->owner = $player;
        $this->ownerName = $player->getName();
        $this->setDataProperty(self::DATA_OWNER_EID, self::DATA_TYPE_LONG, $player->getId());
        $this->setBaseTarget($player);
    }

    /**
     * Sets entity sitting or not.
     *
     * @param bool $sit
     */
    public function setSitting(bool $sit=true) {
        $this->sitting = $sit;
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SITTING, $sit);
    }

    /**
     * Returns if the wolf is sitting or not
     *
     * @return bool
     */
    public function isSitting(): bool {
        return $this->sitting;
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
     * This method has to be called as soon as a owner name is set. It searches online players for the owner name
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
     * Checks if the ocelot is tamed, not sitting and has a "physically" available owner.
     * If so and the distance to the owner is more than 12 blocks: set position to the position
     * of the owner.
     */
    private function checkFollowOwner() {
        if ($this->isTamed()) {
            if ($this->getOwner() !== null && !$this->isSitting()) {
                if ($this->getOwner()->distanceSquared($this) > $this->teleportDistance) {
                    $newPosition = $this->getPositionNearOwner();
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
     * Returns a position near the player (owner) of this entity
     *
     * @return Vector3|null the position near the owner
     */

    //This function needs to be in a parent class for tamed animals.
    private function getPositionNearOwner(): Vector3 {
        $x = $this->getOwner()->x + (mt_rand(0, 1) == 0 ? -1 : 1);
        $z = $this->getOwner()->z + (mt_rand(0, 1) == 0 ? -1 : 1);
        $pos = PureEntities::getInstance()->getSuitableHeightPosition($x, $this->getOwner()->y, $z, $this->getLevel());
        if ($pos !== null) {
            return new Vector3($x, $pos->y, $z);
        } else {
            return null;
        }
    }

    /**
     * Generates and returns a random value from 1 to 3.
     * This is used to determine number of XP Orbs dropped
     * after killing the wolf.
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
