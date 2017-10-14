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


namespace revivalpmmp\pureentities\traits;

use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\Player;
use pocketmine\math\Vector3;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\entity\BaseEntity;

/**
 * This trait should be used by mobs that can be tamed. It's intention is to reduce
 * the duplication of code between entities.  This concept was developled while
 * updating movement code for getting owner positions.
 */

trait Tameable
{
    // -----------------------------------------------------------------------------------------------
    // Variables
    // -----------------------------------------------------------------------------------------------

    /**
     * @var null|Player
     */
    private $owner = null;

    /**
     * @var null|string
     */
    private $ownerName = null;

    /**
     * @var bool
     */
    private $tamed = false;

    /**
     * @var bool
     */
    private $sitting = false;


    /**
     * List of items that can be used to tame the entity.
     *
     * @var array
     */
    private $tameFoods = [];

    /**
     * This determines how likely it is that the creature will be tamed
     * during an attempt.  To determing the correct value of this number
     * you use use the second number in the ratio.  eg. Ocelots have a
     * 1:3 chance of being tamed so $tameChance = 3.
     *
     * @var int
     */
    private $tameChance = 3;

    // -----------------------------------------------------------------------------------------------
    // Functions
    // -----------------------------------------------------------------------------------------------


    /**
     * Call this method when a player tries to tame an entity
     *
     * @param Player $player
     * @return bool
     */
    public function attemptToTame(Player $player): bool {
        // This shouldn't be necessary but just in case...
        if ($this->isTamed()) {
            return null;
        }
        $tameSuccess = mt_rand(0, $this->tameChance - 1) === 0;
        $itemInHand = $player->getInventory()->getItemInHand();
        if ($itemInHand != null) {
            $player->getInventory()->getItemInHand()->setCount($itemInHand->getCount() - 1);
        }
        if($tameSuccess) {
            $pk = new EntityEventPacket();
            $pk->entityRuntimeId = $this->getId();
            $pk->event = EntityEventPacket::TAME_SUCCESS; // this "plays" success animation on entity
            $player->dataPacket($pk);

            // set the properties accordingly
            $this->setTamed(true);
            $this->setOwner($player);
            $this->setSitting(true);

            // Perform creature specific options related becoming tamed.
            $this->onTameSuccess();
        } else {
            $pk = new EntityEventPacket();
            $pk->entityRuntimeId = $this->getId();
            $pk->event = EntityEventPacket::TAME_FAIL; // this "plays" fail animation on entity
            $player->dataPacket($pk);

            // Perform entitiy specific options for failed attempts to tame
            $this->onTameFail();
        }
        return $tameSuccess;
    }

    /**
     * Returns a position near the player (owner) of this entity
     *
     * @return Vector3|null the position near the owner
     */

    private function getPositionNearOwner(Player $owner, BaseEntity $pet): Vector3
    {
        $x = $owner->x + (mt_rand(2, 3) * (mt_rand(0, 1) == 1 ?: -1));
        $z = $owner->z + (mt_rand(2, 3) * (mt_rand(0, 1) == 1 ?: -1));
        $pos = PureEntities::getInstance()->getSuitableHeightPosition($x, $owner->y, $z, $pet->getLevel());
        if ($pos !== null) {
            return new Vector3($x, $pos->y, $z);
        } else {
            return null;
        }
    }

    /**
     * Returns the items the entity can be tamed with (maybe multiple!)
     *
     * @return array
     */
    public function getTameFoods()
    {
        return $this->tameFoods;
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
     * Returns if the entity is sitting or not
     *
     * @return bool
     */
    public function isSitting(): bool {
        return $this->sitting;
    }

    /**
     * Sets this entity tamed and belonging to the player
     *
     * @param bool $option
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

    /**
     * Returns the name of the owner
     *
     * @return null
     */
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
     * The onTame functions are for handling entity specific circumstances
     * during the process of trying to tame the entity.  These functions
     * will do nothing unless the entity overrides the function with specific
     * instructions.  An example of where this is necessary can be found
     * with ocelots.
     */
    private function onTameSuccess() {
        return;
    }

    private function onTameFail() {
        return;
    }
}