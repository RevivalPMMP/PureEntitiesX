<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\entity\Rideable;
use pocketmine\item\Item;
use revivalpmmp\pureentities\features\BreedingExtension;
use revivalpmmp\pureentities\features\IntfCanBreed;

class Pig extends WalkingAnimal implements Rideable, IntfCanBreed {
    const NETWORK_ID = 12;

    public $width = 1.45;
    public $height = 1.12;

    private $feedableItems = array (Item::CARROT, Item::BEETROOT);

    /**
     * Is needed for breeding functionality
     *
     * @var BreedingExtension
     */
    private $breedableClass;

    public function initEntity() {
        parent::initEntity();
        $this->breedableClass = new BreedingExtension($this);
        $this->breedableClass->init();
    }

    public function getName(){
        return "Pig";
    }

    /**
     * Returns the breedable class or NULL if not configured
     *
     * @return BreedingExtension
     */
    public function getBreedingExtension () {
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

    public function getDrops(){
        if ($this->isOnFire()) {
          return [Item::get(Item::COOKED_PORKCHOP, 0, mt_rand(1, 3))];
        } else {
          return [Item::get(Item::RAW_PORKCHOP, 0, mt_rand(1, 3))];
        }
    }

    public function getMaxHealth() {
        return 10;
    }

}
