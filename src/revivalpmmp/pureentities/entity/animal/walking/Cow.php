<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use pocketmine\entity\Creature;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use revivalpmmp\pureentities\features\BreedingExtension;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\PureEntities;

class Cow extends WalkingAnimal implements IntfCanBreed {
    const NETWORK_ID = 11;

    public $width = 0.9;
    public $height = 1.3;
    public $eyeHeight = 1.2;

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
        return "Cow";
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

    /**
     * @param Creature $creature the creature itself, can be any creature (from player to entity)
     * @param float $distance the distance to the creature
     * @return bool true if the entity has interest in the creature, false if not
     */
    public function targetOption(Creature $creature, float $distance) : bool {
        $targetOption = parent::targetOption($creature, $distance);
        if (!$targetOption) {
            if ($creature instanceof Player) { // is the player a target option?
                if ($creature != null and $creature->getInventory() != null) { // sometimes, we get null on getInventory?! F**k
                    $item = $creature->getInventory()->getItemInHand();
                    if ($distance <= $this->maxInteractDistance && $item->getId() === Item::BUCKET && $item->getDamage() === 0) { // empty bucket
                        PureEntities::displayButtonText(PureEntities::BUTTON_TEXT_MILK, $creature);
                    }
                }
            }
        }
        return $targetOption;
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

    /**
     * Simple method that milks this cow
     *
     * @param Player $player
     */
    public function milk (Player $player) : bool{
        PureEntities::logOutput("Cow ($this): milk by $player", PureEntities::DEBUG);
        $item = $player->getInventory()->getItemInHand();
        if ($item !== null && $item->getId() === Item::BUCKET) {
            PureEntities::logOutput("Cow ($this): producing milk in a bucket ...", PureEntities::DEBUG);
            --$item->count;
            $player->getInventory()->setItemInHand($item);
            $bucketWithMilk = Item::get(Item::BUCKET, 0, 1);
            $bucketWithMilk->setDamage(1);
            $player->getInventory()->addItem($bucketWithMilk);
            return true;
        }
        return false;
    }
}
