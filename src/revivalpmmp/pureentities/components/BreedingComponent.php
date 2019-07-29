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

namespace revivalpmmp\pureentities\components;


use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

/**
 * Class BreedingComponent
 *
 * This class contains functionality for ageable entities as well as for entities that can breed.
 *
 * @package revivalpmmp\pureentities\components
 */
class BreedingComponent{

	// ----------------------------------------
	// some useful constants
	// ----------------------------------------
	const DEFAULT_IN_LOVE_TICKS = 5000; // how long is the entity in LOVE mode by default
	const FEED_INCREASE_AGE = 600; // when an entity gets fed - how many ticks are reduced from the grow-up cycle (atm 10%)
	const SEARCH_FOR_PARTNER_DELAY = 100; // do a search for a partner every 300 ticks
	const IN_LOVE_EMIT_DELAY = 100; // emit every 100 ticks the in love animation when in love
	const AGE_TICK_DELAY = 100; // how often is the age updated ...
	const BREED_NOT_POSSIBLE_TICKS = 6000; // 5 minutes - Amount of time that must pass before another breeding attempt can be made after a successful attempt.

	/**
	 * This is the entity that owns this Breedable class (a reference to the Entity)
	 * @var Entity|BaseEntity|IntfCanBreed
	 */
	private $entity = null;

	/**
	 * The partner search timer is used to not search each tick for a partner when in love
	 * @var int
	 */
	private $partnerSearchTimer = 0;
	/**
	 * The inLoveTimer is used for displaying that "in love" (workaround) animation
	 * @var int
	 */
	private $inLoveTimer = 0;
	/**
	 * The ageTickTimer is used for reducing / increasing age each x ticks (not each tick!)
	 * @var int
	 */
	private $ageTickTimer = 0;
	/**
	 * The breed partner is set, when the entity found a partner
	 * @var Entity
	 */
	private $breedPartner = null;

	/**
	 * Defines if the entity currently is breeding
	 * @var bool
	 */
	private $breeding = false;

	/**
	 * Only for babies - the parent of the baby
	 * @var Entity
	 */
	private $parent = null;

	/**
	 * Is initialized from entity, when it's constructed
	 *
	 * @var int
	 */
	private $adultHeight = -1;

	/**
	 * Is initialized from entity, when it's constructed
	 * @var int
	 */
	private $adultWidth = -1;

	/**
	 * Is initialized from entity, when it's constructed
	 *
	 * @var float
	 */
	private $adultSpeed = 0.0;

	/**
	 * @var bool
	 */
	private $emitLoveParticles = false;

	private $age = 0;

	private $inLove = 0;

	public function __construct(Entity $belongsTo){
		/**
		 * @var $belongsTo Entity|BaseEntity
		 */
		$this->entity = $belongsTo;
		$this->adultHeight = $belongsTo->height;
		$this->adultWidth = $belongsTo->width;
		$this->adultSpeed = $belongsTo->getSpeed();
		$this->emitLoveParticles = PluginConfiguration::getInstance()->getEmitLoveParticlesConstantly();
	}

	public function loadFromNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){

			if($this->entity->namedtag->hasTag(NBTConst::NBT_KEY_AGE)){
				$this->age = $this->entity->namedtag->getInt(NBTConst::NBT_KEY_AGE, 0, true);
			}
			if($this->entity->namedtag->hasTag(NBTConst::NBT_KEY_IN_LOVE)){
				$this->inLove = $this->entity->namedtag->getInt(NBTConst::NBT_KEY_IN_LOVE, 0, true);
			}
		}
	}

	public function saveNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			$this->entity->namedtag->setInt(NBTConst::NBT_KEY_AGE, $this->age, true);
			$this->entity->namedtag->setInt(NBTConst::NBT_KEY_IN_LOVE, $this->inLove, true);
		}
	}

	/**
	 * call this method each time, the entity's init method is called
	 */
	public function init(){
		$this->loadFromNBT();
		$this->setAge($this->age);
		$this->setInLove($this->inLove);
	}

	/**
	 * Returns the breed partner as an entity instance or NULL if no breed partner set
	 * TODO: add to NBT
	 * @return Entity|BaseEntity|IntfCanBreed|null
	 */
	public function getBreedPartner(){
		return $this->breedPartner;
	}

	/**
	 * Sets the breed partner for the entity linked with this class
	 * TODO: add to NBT
	 * @param Entity $breedPartner
	 */
	public function setBreedPartner($breedPartner){
		$this->breedPartner = $breedPartner;
		$this->entity->setBaseTarget($breedPartner);
	}

	/**
	 * Sets the entity currently breeding
	 * TODO: add to NBT
	 * @param bool $breeding
	 */
	public function setBreeding(bool $breeding){
		$this->breeding = $breeding;
	}

	/**
	 * Check if the entity is currently breeding
	 * TODO: add to NBT
	 * @return bool
	 */
	public function isBreeding(){
		return $this->breeding;
	}

	/**
	 * Sets the parent when the entity is a baby
	 * TODO: add to NBT
	 * @param Entity $parent
	 */
	public function setParent($parent){
		$this->parent = $parent;
	}

	public function getParent(){
		return $this->parent;
	}


	/**
	 * Returns the age of the entity
	 *
	 * @return int
	 */
	public function getAge() : int{
		return $this->age;
	}

	/**
	 * Sets the age of the entity. Setting this to a number lesser 0 means it's a baby and
	 * it also defines how many ticks it takes to grow up to an adult (e.g.: -1000 means it takes 1000 ticks until the
	 * entity is grown up - can be sped up with wheat)
	 *
	 * @param int $age
	 */
	public function setAge(int $age){
		$this->age = $age; // all under zero is baby
		if($age < 0){
			// we don't want to set the data property each time (because it's transmitted each time to the client)
			if($this->entity->getScale() > 0.5){
				$this->entity->setScale(0.5); // scale entity (and bounding box) to 0.5
			}
			if(!$this->entity->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_BABY)){
				$this->entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_BABY, true); // set baby
				// we also need to adjust the height and width of the entity
				$this->entity->height = $this->adultHeight / 2; // because we scale 0.5
				$this->entity->width = $this->adultWidth / 2; // because we scale 0.5
				$this->entity->speed = $this->adultSpeed * 1.5; // because baby entities are faster
			}
		}else{
			if($this->entity->getScale() < 1.0){
				$this->entity->setScale(1.0); // scale entity (and bounding box) to 1.0 - now it's grown up
			}
			if($this->entity->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_BABY)){
				$this->entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_BABY, false); // set adult
				// reset entity sizes
				$this->entity->height = $this->adultHeight;
				$this->entity->width = $this->adultWidth;
				$this->entity->speed = $this->adultSpeed;
			}
			// forget the parent and reset baseTarget immediately
			$this->setParent(null);
			$this->entity->setBaseTarget(null);
		}
	}

	/**
	 * Returns true if the entity is a baby (age lesser 0)
	 *
	 * @return bool
	 */
	public function isBaby() : bool{
		return $this->getAge() < 0; // this is a baby when the age is lesser 0 (0 is adult,
	}

	/**
	 * completely resets the breed status for this entity
	 */
	public function resetBreedStatus(){
		$this->entity->stayTime = 300; // wait 300 ticks until moving forward
		$this->setBreeding(false); // reset breeding status
		$this->setBreedPartner(null); // reset breed partner
		$this->setInLove(0); // reset in love ticker
		$this->entity->setBaseTarget(null); // search for a new target
		$this->setAge(self::BREED_NOT_POSSIBLE_TICKS); // 20 ticks / second (should be) - the entity cannot breed for 5 minutes
	}


	/**
	 * This method is called when the entity has been fed. This makes the entity fall in love
	 * for the given ticks. When an entity is in love, it searches for another partner of the same
	 * species that is also in love to breed new baby entity.
	 *
	 * @param int $inLoveTicks
	 */
	public function setInLove(int $inLoveTicks){
		$this->inLove = $inLoveTicks;
		if($this->getInLove() > 0){
			$this->entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INLOVE, true); // set client "in love"
		}else{
			$this->entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INLOVE, false); // send client not "in love" anymore
		}
	}

	/**
	 * Returns the amount of ticks the entity is in love. <= 0 means it's not in love and
	 * not actively searching for a partner.
	 *
	 * @return int
	 */
	public function getInLove() : int{
		return $this->inLove;
	}

	/**
	 * This has to be called by checkTarget() or any other tick related method.
	 *
	 * @return bool
	 */
	public function checkInLove() : bool{
		if($this->getInLove() > 0){

			// check if we are near our breeding partner. if so, set breed!
			if($this->getBreedPartner() != null and
				$this->getBreedPartner()->getBreedingComponent()->getInLove() > 0 and
				$this->getBreedPartner()->distance($this->entity) <= 2 and
				!$this->isBreeding()
			){
				$this->breed($this->getBreedPartner());
				return true;
			}


			// emit heart particles ...
			if($this->inLoveTimer >= self::IN_LOVE_EMIT_DELAY and $this->emitLoveParticles){
				foreach($this->entity->getLevel()->getPlayers() as $player){ // don't know if this is the correct one :/
					if($player->distance($this->entity) <= 49){
						$pk = new ActorEventPacket();
						$pk->entityRuntimeId = $this->entity->getId();
						$pk->event = ActorEventPacket::TAME_SUCCESS; // i think this plays the "heart" animation
						$player->dataPacket($pk);
					}
				}
				$this->inLoveTimer = 0;
			}else if($this->emitLoveParticles){
				$this->inLoveTimer++;
			}

			// search for partner
			if($this->partnerSearchTimer >= self::SEARCH_FOR_PARTNER_DELAY and
				$this->getBreedPartner() == null
			){
				$validTarget = $this->findAnotherEntityInLove(PluginConfiguration::getInstance()->getMaxFindPartnerDistance()); // find another target within 20 blocks
				if($validTarget != false){
					$this->setBreedPartner($validTarget); // now my target is my "in love" partner - this entity will move to the other entity
					/**
					 * @var $validTarget IntfCanBreed
					 */
					$validTarget->getBreedingComponent()->setBreedPartner($this->entity); // set the other one's breed partner to ourselves
				}
				$this->partnerSearchTimer = 0;
			}else{
				$this->partnerSearchTimer++; // we only search every 300 ticks if we find a partner
			}
			return true;
		}
		return false;
	}

	/**
	 * Method that finds other entities of the same species in LOVE
	 *
	 * @param int $range the range (documentation says 8)
	 * @return Entity | bool
	 */
	private function findAnotherEntityInLove(int $range){
		$entityFound = false;
		foreach($this->entity->getLevel()->getEntities() as $otherEntity){
			/**
			 * @var $otherEntity IntfCanBreed|BaseEntity
			 */
			if(strcmp(get_class($otherEntity), get_class($this->entity)) == 0 and // must be of the same species
				$otherEntity->distance($this->entity) <= $range and // must be in range
				$otherEntity->getBreedingComponent()->getInLove() > 0 and // must be in love
				$otherEntity->getId() != $this->entity->getId() and // should be another entity of the same type
				$otherEntity->getBreedingComponent()->getBreedPartner() == null
			){ // shouldn't have another breeding partner
				$entityFound = $otherEntity;
				break;
			}
		}
		return $entityFound;
	}

	/**
	 * @param Entity $partner
	 */
	private function breed(Entity $partner){
		// yeah we found ourselves - now breed and reset target
		$this->resetBreedStatus();
		/**
		 * @var $partner Entity|BaseEntity|IntfCanBreed
		 */
		$partner->getBreedingComponent()->resetBreedStatus();
		// spawn a baby entity which may be owned by a player
		$owner = null;
		if($this->entity instanceof IntfTameable){
			$owner = $this->entity->getOwner();
		}
		PureEntities::getInstance()->scheduleCreatureSpawn($this->entity, $this->entity->getNetworkId(), $this->entity->getLevel(),
			"Animal", true, $this->entity, $owner);
	}

	/**
	 * Method to increase the age for adult / baby entities
	 */
	public function increaseAge(){
		if($this->ageTickTimer >= self::AGE_TICK_DELAY){
			if($this->isBaby()){
				$newAge = $this->getAge() + $this->ageTickTimer;
				if($newAge >= 0){
					$newAge = self::BREED_NOT_POSSIBLE_TICKS; // cannot breed for 5 minutes ...
				}
				$this->setAge($newAge); // going to positive. when age reached 0 or more, it will be an adult ...
			}else if(!$this->isBaby() and $this->getAge() > 0){
				$newAge = $this->getAge() - $this->ageTickTimer;
				if($newAge < 0){
					$newAge = 0;
				}
				$this->setAge($newAge); // going from positive to null (because when age > 0 it cannot breed)
			}
			$this->ageTickTimer = 0;
		}else{
			$this->ageTickTimer++;
		}
	}

	/**
	 * Feed a entity with feedable items
	 * @param Player $player the player that feeds this entity ...
	 * @return bool if feeding was successful true is returned
	 */
	public function feed(Player $player) : bool{
		if($this->getAge() > 0){
			$pk = new ActorEventPacket();
			$pk->entityRuntimeId = $this->entity->getId();
			$pk->event = ActorEventPacket::TAME_FAIL; // this "plays" fail animation on entity
			$player->dataPacket($pk);
			return false;
		}
		if($this->isBaby()){ // when a baby gets fed with wheat, it grows up a little faster
			$age = $this->getAge();
			$age += self::FEED_INCREASE_AGE;
			$this->setAge($age);
		}else{
			// this makes the entity fall in love - and search for a partner ...
			$this->setInLove(self::DEFAULT_IN_LOVE_TICKS);
			// checkTarget method recognizes the "in love" and tries to find a partner
			// when feeding was successful and entity is in love mode emit heart particles (only once for now)
			$pk = new ActorEventPacket();
			$pk->entityRuntimeId = $this->entity->getId();
			$pk->event = ActorEventPacket::TAME_SUCCESS; // i think this plays the "heart" animation
			$player->dataPacket($pk);
		}
		return true;
	}

	/**
	 * This method has to be called by the entity to tick this breeding extension
	 */
	public function tick(){
		// we should also check for any blocks of interest for the entity
		$this->increaseAge();

		// for a baby force to set the baseTarget to the parent (if it's available)
		if($this->isBaby() and
			$this->getParent() !== null and
			$this->getParent()->isAlive() and
			!$this->getParent()->isClosed() and
			($this->entity->getBaseTarget() === null or !$this->entity->getBaseTarget() instanceof Player)
		){
			$this->entity->setBaseTarget($this->getParent());
			if($this->getParent()->distance($this->entity) <= 4){
				$this->entity->stayTime = 100; // wait 100 ticks before moving after the parent ;)
			}
		}
	}

}