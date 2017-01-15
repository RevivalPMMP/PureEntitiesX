<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;
use revivalpmmp\pureentities\features\BreedingExtension;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\PureEntities;

class Cow extends WalkingAnimal implements IntfCanBreed {
    const NETWORK_ID = 11;

    public $width = 0.9;
    public $height = 1.3;
    public $eyeHeight = 1.2;

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
     * @param Creature $creature
     * @param float $distance
     * @return bool
     */
    public function targetOption(Creature $creature, float $distance) : bool {
        if($creature instanceof Player) { // is the player a target option?
            if ($creature != null and $creature->getInventory() != null) { // sometimes, we get null on getInventory?! F**k
                if ($creature->getInventory()->getItemInHand()->getId() === Item::WHEAT) {
                    if ($distance <= $this->maxInteractDistance) { // we can feed a cow! and it makes no difference if it's an adult or a baby ...
                        $creature->setDataProperty(self::DATA_INTERACTIVE_TAG, self::DATA_TYPE_STRING, PureEntities::BUTTON_TEXT_FEED);
                    }
                    // check if the cow is able to follow - but only on a distance of 6 blocks
                    $follow = $creature->spawned && $creature->isAlive() && !$creature->closed && $distance <= 6;
                    // cows only follow when <= 5 blocks away. otherwise, forget the player as target!
                    if (!$follow and $this->isFollowingPlayer($creature)) {
                        $this->baseTarget = $this->getBreedingExtension()->getBreedPartner(); // reset base target to breed partner (or NULL, if there's none)
                    }
                    return $follow;
                } else {
                    $creature->setDataProperty(self::DATA_INTERACTIVE_TAG, self::DATA_TYPE_STRING, "");
                    // reset base target when it was player before (follow by holding wheat)
                    if ($this->isFollowingPlayer($creature)) { // we've to reset follow when there's nothing interesting in hand
                        // reset base target!
                        $this->baseTarget = $this->getBreedingExtension()->getBreedPartner(); // reset base target to breed partner (or NULL, if there's none)
                    }
                }
            }
        }
        return false;
    }


    protected function checkTarget(){
        // we should also check for any blocks of interest for the entity
        $this->getBreedingExtension()->checkInLove();

        // tick the breedable class embedded
        $this->getBreedingExtension()->tick();

        // and of course, we should call the parent check target method
        parent::checkTarget();
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
