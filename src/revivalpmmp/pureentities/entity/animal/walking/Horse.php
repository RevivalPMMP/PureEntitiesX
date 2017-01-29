<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use pocketmine\entity\Ageable;
use pocketmine\inventory\InventoryHolder;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\entity\Rideable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;
use revivalpmmp\pureentities\features\BreedingExtension;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

class Horse extends WalkingAnimal implements Rideable, Ageable, InventoryHolder, IntfCanBreed {
    const NETWORK_ID = 23;

    public $width = 1.4;
    public $height = 1.6;

	private $feedableItems = array(Item::SUGAR);

	/**
	 * Is needed for breeding functionality
	 *
	 * @var BreedingExtension
	 */
	private $breedableClass;

    public function getName(){
        return "Horse";
    }

    public function getInventory() {
    	//
    }

    public function isBaby() {
	    //
    }

	public function targetOption(Creature $creature, float $distance) : bool{
	    if ($creature instanceof Player) {
		    if ($creature != null and $creature->getInventory() != null) {
			    if ($this->getInventory()) {
				    if ($distance <= PluginConfiguration::getInstance()->getMaxInteractDistance()) {
					    InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_RIDE, $creature, $this);
					    return true;
				    }
			    } else {
				    InteractionHelper::displayButtonText("", $creature, $this);
			    }
		    }
	    }
        return false;
	}

	public function initEntity(){
		parent::initEntity();

		$this->breedableClass = new BreedingExtension($this);
		$this->breedableClass->init();
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

	public function getDrops(){
        return [Item::get(Item::LEATHER, 0, mt_rand(0, 2))];
    }

    public function getMaxHealth() {
        return 20;
    }

}
