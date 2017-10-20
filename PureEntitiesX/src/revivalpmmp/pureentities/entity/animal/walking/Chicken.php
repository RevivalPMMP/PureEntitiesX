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

use pocketmine\level\sound\PopSound;
use revivalpmmp\pureentities\components\BreedingComponent;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfCanPanic;

class Chicken extends WalkingAnimal implements IntfCanBreed, IntfCanInteract, IntfCanPanic {
    const NETWORK_ID = Data::CHICKEN;

    public $eyeHeight = 0.6;
    public $gravity = 0.08; // floating chickens

    // egg laying specific configuration (an egg is laid by a chicken each 6000-120000 ticks)
    private $dropEggTimer = 0;
    private $dropEggTime = 0;
    const DROP_EGG_DELAY_MIN = 6000;
    const DROP_EGG_DELAY_MAX = 12000;

    private $feedableItems = array(
        Item::WHEAT_SEEDS,
        Item::PUMPKIN_SEEDS,
        Item::MELON_SEEDS,
        Item::BEETROOT_SEEDS);

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

    public function getSpeed(): float {
        return $this->speed;
    }

    public function getNormalSpeed(): float {
        return 1.0;
    }

    function getPanicSpeed(): float {
        return 1.2;
    }

    public function saveNBT() {
        parent::saveNBT();
        $this->breedableClass->saveNBT();
    }

    public function getName(): string {
        return "Chicken";
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
            // only adult chicken drop something ...
            if ($this->breedableClass != null && $this->breedableClass->isBaby()) {
                array_push($drops, Item::get(Item::FEATHER, 0, mt_rand(0, 2)));
                if ($this->isOnFire()) {
                    array_push($drops, Item::get(Item::COOKED_CHICKEN, 0, 1));
                } else {
                    array_push($drops, Item::get(Item::RAW_CHICKEN, 0, 1));
                }
            }
        }
        return $drops;
    }

    public function getMaxHealth(): int {
        return 4;
    }


    // ----- functionality to lay an eg ... -------------
    public function entityBaseTick(int $tickDiff = 1): bool {
        if ($this->dropEggTime === 0) {
            $this->dropEggTime = mt_rand(self::DROP_EGG_DELAY_MIN, self::DROP_EGG_DELAY_MAX);
        }

        if ($this->dropEggTimer >= $this->dropEggTime) { // drop an egg!
            $this->layEgg();
        } else {
            $this->dropEggTimer += $tickDiff;
        }

        parent::entityBaseTick($tickDiff);
        return true;
    }

    private function layEgg() {
        $item = Item::get(Item::EGG, 0, 1);
        $this->getLevel()->dropItem($this, $item);
        $this->getLevel()->addSound(new PopSound($this), $this->getViewers());

        $this->dropEggTimer = 0;
        $this->dropEggTime = 0;
    }

    public function getKillExperience(): int {
        if ($this->getBreedingComponent()->isBaby()) {
            return mt_rand(1, 7);
        }
        return mt_rand(1, 3);
    }


}
