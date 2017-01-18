<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use pocketmine\level\sound\PopSound;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use revivalpmmp\pureentities\features\BreedingExtension;
use revivalpmmp\pureentities\features\IntfCanBreed;

class Chicken extends WalkingAnimal implements IntfCanBreed {
    const NETWORK_ID = 10;

    public $width = 0.4;
    public $height = 0.7;
    public $eyeHeight = 0.7;

    // egg laying specific configuration (an egg is layed by a chicken each 6000-120000 ticks)
    private $dropEggTimer = 0;
    private $dropEggTime = 0;
    const DROP_EGG_DELAY_MIN = 6000;
    const DROP_EGG_DELAY_MAX = 12000;

    private $feedableItems = array (
        Item::WHEAT_SEEDS,
        Item::PUMPKIN_SEEDS,
        Item::MELON_SEEDS,
        Item::BEETROOT_SEEDS);

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
        return "Chicken";
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

        // only adult chicken drop something ...
        if (!$this->breedableClass->isBaby()) {
            array_push($drops, Item::get(Item::FEATHER, 0, mt_rand(0, 2)));
            if ($this->isOnFire()) {
                array_push($drops, Item::get(Item::COOKED_CHICKEN, 0, 1));
            } else {
                array_push($drops, Item::get(Item::RAW_CHICKEN, 0, 1));
            }
        }
        return $drops;
    }

    public function getMaxHealth() {
        return 4;
    }


    // ----- functionality to lay an eg ... -------------
    public function entityBaseTick($tickDiff = 1) {
        if ($this->dropEggTime === 0) {
            $this->dropEggTime = mt_rand(self::DROP_EGG_DELAY_MIN, self::DROP_EGG_DELAY_MAX);
        }

        if ($this->dropEggTimer >= $this->dropEggTime) { // drop an egg!
            $this->layEgg ();
        } else {
            $this->dropEggTimer += $tickDiff;
        }

        parent::entityBaseTick($tickDiff);
    }

    private function layEgg () {
        $item = Item::get(Item::EGG, 0, 1);
        $this->getLevel()->dropItem($this, $item);
        $this->getLevel()->addSound(new PopSound($this), $this->getViewers());

        $this->dropEggTimer = 0;
        $this->dropEggTime = 0;
    }

}
