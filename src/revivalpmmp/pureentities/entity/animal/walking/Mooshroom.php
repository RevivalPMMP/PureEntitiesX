<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use revivalpmmp\pureentities\features\BreedingExtension;
use revivalpmmp\pureentities\features\IntfCanBreed;

class Mooshroom extends WalkingAnimal implements IntfCanBreed {
    const NETWORK_ID = 16;

    public $width = 1.45;
    public $height = 1.12;

    private $feedableItems = array (Item::WHEAT);

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
        return "Mooshroom";
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
      $drops = [];
      array_push($drops, Item::get(Item::LEATHER, 0, mt_rand(0, 2)));
      if ($this->isOnFire()) {
        array_push($drops, Item::get(Item::COOKED_BEEF, 0, mt_rand(1, 3)));
      } else {
        array_push($drops, Item::get(Item::RAW_BEEF, 0, mt_rand(1, 3)));
      }
      return $drops;
    }

    public function getMaxHealth() {
        return 10;
    }
}
