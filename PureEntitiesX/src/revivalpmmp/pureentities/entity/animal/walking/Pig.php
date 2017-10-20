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

use revivalpmmp\pureentities\components\BreedingComponent;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\entity\Rideable;
use pocketmine\item\Item;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfCanPanic;

class Pig extends WalkingAnimal implements Rideable, IntfCanBreed, IntfCanInteract, IntfCanPanic {
    const NETWORK_ID = Data::PIG;

    private $feedableItems = array(
        Item::CARROT,
        Item::BEETROOT);

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

    public function getName(): string {
        return "Pig";
    }

    public function getSpeed(): float {
        return $this->speed;
    }

    public function getNormalSpeed(): float {
        return 1.0;
    }

    public function getPanicSpeed(): float {
        return 1.2;
    }

    public function saveNBT() {
        parent::saveNBT();
        $this->breedableClass->saveNBT();
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
        if ($this->isLootDropAllowed()) {
            if ($this->isOnFire()) {
                return [Item::get(Item::COOKED_PORKCHOP, 0, mt_rand(1, 3))];
            } else {
                return [Item::get(Item::RAW_PORKCHOP, 0, mt_rand(1, 3))];
            }
        } else {
            return [];
        }
    }

    public function getMaxHealth(): int {
        return 10;
    }

    public function getKillExperience(): int {
        if ($this->getBreedingComponent()->isBaby()) {
            return mt_rand(1, 7);
        }
        return mt_rand(1, 3);
    }

    /**
     * Just for Bluelight ...
     * @return null
     */
    public function getRidePosition() {
        return null;
    }


}
