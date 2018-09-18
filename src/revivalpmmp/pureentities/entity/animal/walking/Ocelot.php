<?php

/**
 * PureEntitiesX: Mob AI Plugin for PMMP
 * Copyright (C)  2018 RevivalPMMP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace revivalpmmp\pureentities\entity\animal\walking;

use pocketmine\entity\Creature;
use pocketmine\item\Item;
use pocketmine\Player;
use revivalpmmp\pureentities\components\BreedingComponent;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfCanPanic;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\traits\Breedable;
use revivalpmmp\pureentities\traits\CanPanic;
use revivalpmmp\pureentities\traits\Feedable;
use revivalpmmp\pureentities\traits\Tameable;


// TODO: Add 'Begging Mode' for untamed ocelots.
// TODO: Fix tamed ocelot response to Owner in combat (should avoid fights).
// TODO: Add trigger to tame() so that a failure to tame will trigger breeding mode.


class Ocelot extends WalkingAnimal implements IntfTameable, IntfCanBreed, IntfCanInteract, IntfCanPanic{
	use Breedable, CanPanic, Feedable, Tameable;
	const NETWORK_ID = Data::NETWORK_IDS["ocelot"];

	private $comfortObjects = array(
		Item::BED,
		Item::LIT_FURNACE,
		Item::BURNING_FURNACE,
		Item::CHEST
	);

	/**
	 * Teleport distance - when does a tamed wolf start to teleport to it's owner?
	 *
	 * @var int
	 */
	private $teleportDistance = 12;

	/**
	 * Tamed cats will explore around the player unless commanded to sit. This describes the
	 * max distance to the player.
	 *
	 * @var int
	 */
	private $followDistance = 10;

	private $catType = 0; // 0 = Wild Ocelot, 1 = Tuxedo, 2 = Tabby, 3 = Siamese

	public function getBeggingSpeed() : float{
		return 0.8;
	}

	public function initEntity() : void{
		parent::initEntity();
		$this->speed = 1.2;
		$this->setNormalSpeed($this->speed);
		$this->setPanicSpeed(1.4);

		$this->fireProof = false;

		$this->breedableClass = new BreedingComponent($this);

		$this->tameFoods = array(
			Item::RAW_FISH,
			Item::RAW_SALMON
		);

		$this->feedableItems = array(
			Item::RAW_FISH,
			Item::RAW_SALMON
		);

		if($this->isTamed()){
			$this->mapOwner();
			if($this->owner === null){
				PureEntities::logOutput("Ocelot($this): is tamed but player not online. Cannot set tamed owner. Will be set when player logs in ..", PureEntities::NORM);
			}
		}

		$this->breedableClass->init();

		$this->teleportDistance = PluginConfiguration::getInstance()->getTamedTeleportBlocks();
		$this->followDistance = PluginConfiguration::getInstance()->getTamedPlayerMaxDistance();
	}

	/**
	 * Returns the appropriate NetworkID associated with this entity
	 * @return int
	 */
	public function getNetworkId(){
		return self::NETWORK_ID;
	}

	/**
	 * Returns an array of items that tamed cats are attracted too.
	 *
	 * @return array
	 */
	public function getComfortObjects(){
		return $this->comfortObjects;
	}


	public function getName() : string{
		return "Ocelot";
	}

	/**
	 * We have to override the method to have better control over the ocelot (atm deciding if the
	 * ocelot is tamed and needs to teleport closer to the owner)
	 *
	 * @param int $tickDiff
	 * @return bool
	 */
	public function entityBaseTick(int $tickDiff = 1) : bool{
		$this->checkFollowOwner();
		return parent::entityBaseTick($tickDiff);
	}

	/**
	 * We need to override this function as the ocelot can hunt for chickens when not tamed.
	 * When tamed and no other target is set (or is following player) the tamed ocelot should attack nothing.
	 * @param bool $checkSkip
	 */
	public function checkTarget(bool $checkSkip = true){
		if(($checkSkip and $this->isCheckTargetAllowedBySkip()) or !$checkSkip){
			if(!$this->isTamed() and !$this->getBaseTarget() instanceof Chicken){
				// is there any entity around that can be attacked (chickens)
				// Need to reconsider this method and test response when multiple matches are within
				// the bounding box across multiple checks.  Ocelots should be able to 'stalk' a target
				// after choosing one instead of jumping between multiple entities as targets.
				foreach($this->getLevel()->getNearbyEntities($this->boundingBox->expandedCopy(10, 10, 10), $this) as $entity){
					if($entity instanceof Chicken and $entity->isAlive()){
						$this->setBaseTarget($entity); // set the given entity as target ...
						return;
					}
				}
			}
			parent::checkTarget(false);
		}
	}

	/**
	 * Tamed ocelots behave differently from other tamed animals when dealing with sit commands.
	 *
	 * @param int $tickDiff
	 * @return null|\pocketmine\math\Vector3
	 */
	public function updateMove($tickDiff){

		if($this->isSitting()){
			// we need to call checkTarget otherwise the targetOption method is not called :/
			$this->checkTarget(false);
			return null;
		}
		return parent::updateMove($tickDiff);
	}

	/**
	 * Loads data from nbt and stores to local variables
	 */
	public function loadNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::loadNBT();
			if($this->namedtag->hasTag(NBTConst::NBT_KEY_CATTYPE)){
				$catType = $this->namedtag->getByte(NBTConst::NBT_KEY_CATTYPE, 0, true);
				$this->setCatType($catType);
			}
		}
	}

	public function saveNBT() : void{
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::saveNBT();
			$this->namedtag->setByte(NBTConst::NBT_KEY_CATTYPE, $this->catType, true); // sets ocelot skin
			$this->breedableClass->saveNBT();
		}

	}

	public function targetOption(Creature $creature, float $distance) : bool{

		if($creature instanceof Player){
			return $creature->spawned && $creature->isAlive() && !$creature->isClosed() && $creature->getInventory()->getItemInHand()->getId() == Item::RAW_FISH && $distance <= 49;
		}
		return false;
	}

	public function getDrops() : array{
		return [];
	}

	public function getMaxHealth() : int{
		return 10;
	}

	private function onTameSuccess(Player $player){
		$this->setCatType(mt_rand(1, 3)); // Randomly chooses a tamed skin
	}

	private function onTameFail(Player $player){
		$this->getBreedingComponent()->feed($player);
		return;
	}

	/**
	 * Sets the skin type of the ocelot.
	 * 0 = Wild Ocelot, 1 = Tuxedo, 2 = Tabby, 3 = Siamese
	 *
	 * @param int $type
	 */
	public function setCatType(int $type = 0){
		$this->catType = $type;
		$this->getDataPropertyManager()->setPropertyValue(self::DATA_VARIANT, self::DATA_TYPE_INT, $type);
	}

	/**
	 * Returns which skin is set in catType
	 * 0 = Wild Ocelot, 1 = Tuxedo, 2 = Tabby, 3 = Siamese
	 *
	 * @return int
	 */
	public function getCatType() : int{
		return $this->catType;
	}


	/**
	 * Checks if the ocelot is tamed, not sitting and has a "physically" available owner.
	 * If so and the distance to the owner is more than 12 blocks: set position to the position
	 * of the owner.
	 */
	private function checkFollowOwner(){
		if($this->isTamed()){
			if($this->getOwner() !== null && !$this->isSitting()){
				if($this->getOwner()->distanceSquared($this) > $this->teleportDistance){
					$newPosition = $this->getPositionNearOwner($this->getOwner(), $this);
					$this->teleport($newPosition !== null ? $newPosition : $this->getOwner()); // this should be better than teleporting directly onto player
					PureEntities::logOutput("$this: teleport distance exceeded. Teleport myself near to owner.");
				}else if($this->getOwner()->distanceSquared($this) > $this->followDistance){
					if($this->getBaseTarget() !== $this->getOwner()){
						$this->setBaseTarget($this->getOwner());
						PureEntities::logOutput("$this: follow distance exceeded. Set target to owner. Continue to follow.");
					}else{
						PureEntities::logOutput("$this: follow distance exceeded. But target already set to owner. Continue to follow.");
					}
				}else if($this->getBaseTarget() === null or $this->getBaseTarget() === $this->getOwner()){
					// no distance exceeded. if the target is the owner, set a random one instead.
					$this->findRandomLocation();
					PureEntities::logOutput("$this: set random walking location. Continue to idle.");
				}
			}
		}
	}

	public function updateXpDropAmount() : void{
		if($this->getBreedingComponent()->checkInLove()){
			$this->xpDropAmount = mt_rand(1, 7);
		}
		if(!$this->getBreedingComponent()->isBaby()){
			$this->xpDropAmount = mt_rand(1, 3);
		}
	}

}
