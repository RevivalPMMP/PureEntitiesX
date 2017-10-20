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

use pocketmine\Player;
use revivalpmmp\pureentities\components\BreedingComponent;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfCanPanic;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\data\Data;

class Cow extends WalkingAnimal implements IntfCanBreed, IntfCanInteract, IntfCanPanic {
    const NETWORK_ID = Data::COW;

    public $eyeHeight = 1;

    private $feedableItems = array(Item::WHEAT);

    /**
     * Is needed for breeding functionality
     *
     * @var BreedingComponent
     */
    private $breedableClass;

    public function initEntity() {
        parent::initEntity();
        $this->width = Data::WIDTHS[self::NETWORK_ID];
        $this->height = Data::HEIGHTS[self::NETWORK_ID];

        $this->breedableClass = new BreedingComponent($this);
        $this->breedableClass->init();

    }

    public function saveNBT() {
        parent::saveNBT();
        $this->breedableClass->saveNBT();
    }

    public function getSpeed(): float {
        return $this->speed;
    }

    public function getPanicSpeed(): float {
        return 1.2;
    }

    public function getNormalSpeed(): float {
        return 1.0;
    }

    public function getName(): string {
        return "Cow";
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

    public function getDrops(): array {
        $drops = [];
        if ($this->isLootDropAllowed()) {
            array_push($drops, Item::get(Item::LEATHER, 0, mt_rand(0, 2)));
            if ($this->isOnFire()) {
                array_push($drops, Item::get(Item::COOKED_BEEF, 0, mt_rand(1, 3)));
            } else {
                array_push($drops, Item::get(Item::RAW_BEEF, 0, mt_rand(1, 3)));
            }
        }
        return $drops;
    }

    public function getMaxHealth(): int {
        return 10;
    }

    /**
     * Simple method that milks this cow
     *
     * @param Player $player
     * @return bool true if milking was successful, false if not
     */
    public function milk(Player $player): bool {
        $item = $player->getInventory()->getItemInHand();
        if ($item !== null && $item->getId() === Item::BUCKET) {
            --$item->count;
            $player->getInventory()->setItemInHand($item);
            $bucketWithMilk = Item::get(Item::BUCKET, 0, 1);
            $bucketWithMilk->setDamage(1);
            $player->getInventory()->addItem($bucketWithMilk);
            InteractionHelper::displayButtonText("", $player);
            return true;
        }
        return false;
    }


    /**
     * This method is called when a player is looking at this entity. This
     * method shows an interactive button or not
     *
     * @param Player $player the player to show a button eventually to
     */
    public function showButton(Player $player) {
        if ($player->getInventory() != null) { // sometimes, we get null on getInventory?! F**k
            $itemInHand = $player->getInventory()->getItemInHand();
            if ($itemInHand->getId() === Item::BUCKET && $itemInHand->getDamage() === 0) { // empty bucket
                InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_MILK, $player);
                return;
            }
        }
        parent::showButton($player);
    }

    public function getKillExperience(): int {
        if ($this->getBreedingComponent()->isBaby()) {
            return mt_rand(1, 7);
        }
        return mt_rand(1, 3);
    }

}


