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

namespace revivalpmmp\pureentities\entity\monster;

use pocketmine\block\Water;
use pocketmine\entity\Creature;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\Item\ItemIds;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use revivalpmmp\pureentities\entity\animal\Animal;
use revivalpmmp\pureentities\entity\monster\walking\Enderman;
use revivalpmmp\pureentities\entity\monster\walking\Wolf;
use revivalpmmp\pureentities\entity\WalkingEntity;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfShearable;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

// use pocketmine\event\Timings;

abstract class WalkingMonster extends WalkingEntity implements Monster{

	protected $attackDelay = 0;

	private $minDamage = [0, 0, 0, 0];
	private $maxDamage = [0, 0, 0, 0];

	protected $attackDistance = 2; // distance of blocks when attack can be started

	public abstract function attackEntity(Entity $player);

	/**
	 * This is only a little helper method to NOT implement that in each tamable entity. This method
	 * checks if the entity is tamed and the attacked entity is the owner. If so, the method will do
	 * nothing. Otherwise, the attackEntity method is called which has to be implemented by each monster entity.
	 *
	 * @param Entity $player
	 */
	public function checkAndAttackEntity(Entity $player){
		if($this instanceof IntfTameable and $this->isTamed()){
			if($player instanceof Player and strcasecmp($player->getName(), $this->getOwner()->getName()) === 0){
				// a tamed entity doesn't attack it's owner!
				return;
			}
		}
		$this->attackEntity($player);
	}

	public function checkTarget(bool $checkSkip = true){
		if(($checkSkip and $this->isCheckTargetAllowedBySkip()) or !$checkSkip){
			// breeding implementation (as only walking entities can breed atm)
			if($this instanceof IntfTameable){
				if($this->isTamed()){ // breeding extension only applies to tamed monsters
					if($this instanceof IntfCanBreed && $this->getBreedingComponent() !== null){
						if($this->getBreedingComponent()->getInLove() <= 0){ // when the entity is NOT in love, but tamed, it should follow the player!!!
							$target = $this->getBaseTarget();
							if(!$this->isTargetMonsterOrAnimal() or !$target->isAlive()){
								// set target to owner ...
								$player = $this->getOwner();
								if($player !== null and $player->isOnline()){
									$this->setBaseTarget($player);
								}
							}
						}
					}
				}
			}
			parent::checkTarget(false);
		}
	}

	public function getDamage(int $difficulty = null) : float{
		return mt_rand($this->getMinDamage($difficulty), $this->getMaxDamage($difficulty));
	}

	public function getMinDamage(int $difficulty = null) : float{
		if($difficulty === null or !is_numeric($difficulty) || $difficulty > 3 || $difficulty < 0){
			$difficulty = Server::getInstance()->getDifficulty();
		}
		return $this->minDamage[$difficulty];
	}

	public function getMaxDamage(int $difficulty = null) : float{
		if($difficulty === null or !is_numeric($difficulty) || $difficulty > 3 || $difficulty < 0){
			$difficulty = Server::getInstance()->getDifficulty();
		}
		return $this->maxDamage[$difficulty];
	}

	/**
	 * @param float|float[] $damage
	 * @param int           $difficulty
	 */
	public function setDamage($damage, int $difficulty = null){
		if(is_array($damage)){
			for($i = 0; $i < 4; $i++){
				$this->minDamage[$i] = $damage[$i];
				$this->maxDamage[$i] = $damage[$i];
			}
			return;
		}elseif($difficulty === null){
			$difficulty = Server::getInstance()->getDifficulty();
		}

		if($difficulty >= 1 && $difficulty <= 3){
			$this->minDamage[$difficulty] = $damage[$difficulty];
			$this->maxDamage[$difficulty] = $damage[$difficulty];
		}
	}

	public function setMinDamage($damage, int $difficulty = null){
		if(is_array($damage)){
			for($i = 0; $i < 4; $i++){
				$this->minDamage[$i] = min($damage[$i], $this->getMaxDamage($i));
			}
			return;
		}elseif($difficulty === null){
			$difficulty = Server::getInstance()->getDifficulty();
		}

		if($difficulty >= 1 && $difficulty <= 3){
			$this->minDamage[$difficulty] = min((float) $damage, $this->getMaxDamage($difficulty));
		}
	}

	public function setMaxDamage($damage, int $difficulty = null){
		if(is_array($damage)){
			for($i = 0; $i < 4; $i++){
				$this->maxDamage[$i] = max((int) $damage[$i], $this->getMaxDamage($i));
			}
			return;
		}elseif($difficulty === null){
			$difficulty = Server::getInstance()->getDifficulty();
		}

		if($difficulty >= 1 && $difficulty <= 3){
			$this->maxDamage[$difficulty] = max((int) $damage, $this->getMaxDamage($difficulty));
		}
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->getLevel() == null) return false;
		if($this->server->getDifficulty() < 1){
			$this->despawnFromAll();
			$this->close();
			return false;
		}

		if($this->isClosed() or !$this->isAlive()){
			return parent::onUpdate($currentTick);
		}

		$tickDiff = $currentTick - $this->lastUpdate;
		$this->lastUpdate = $currentTick;
		$this->entityBaseTick($tickDiff);

		$target = $this->updateMove($tickDiff);
		if($this->isFriendly()){
			if(!($target instanceof Player)){
				if($target instanceof Entity && $target->distanceSquared($this) <= $this->attackDistance){
					$this->checkAndAttackEntity($target);
				}elseif(
					$target instanceof Vector3
					&& (($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2) <= 1
					&& $this->motion->y == 0
				){
					$this->moveTime = 0;
				}
			}
		}else{
			if($target instanceof Entity && $target->distanceSquared($this) <= $this->attackDistance){
				$this->checkAndAttackEntity($target);
			}elseif(
				$target instanceof Vector3
				&& $this->distanceSquared($target) <= 1
				&& $this->motion->y == 0
			){
				$this->moveTime = 0;
			}
		}
		return true;
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->isClosed() or $this->getLevel() == null) return false;
		// Timings::$timerEntityBaseTick->startTiming();

		$hasUpdate = parent::entityBaseTick($tickDiff);

		// BaseEntity::entityBaseTick checks and can trigger despawn.  After calling it, we need to verify
		// that the entity is still valid for updates before performing any other tasks on it.
		if($this->isClosed() or !$this->isAlive()){
			// Timings::$timerEntityBaseTick->stopTiming();
			return false;
		}

		$this->attackDelay += $tickDiff;
		if($this instanceof Enderman){
			if($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) $this->y, Math::floorFloat($this->z))) instanceof Water){
				$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
				$this->attack($ev);
				$this->move(mt_rand(-20, 20), mt_rand(-20, 20), mt_rand(-20, 20));
			}
		}elseif($this->getLevel() !== null){
			if(!$this->hasEffect(Effect::WATER_BREATHING) && $this->isUnderwater()){
				$hasUpdate = true;
				$airTicks = $this->getDataPropertyManager()->getPropertyValue(self::DATA_AIR, Entity::DATA_TYPE_SHORT) - $tickDiff;
				if($airTicks <= -20){
					$airTicks = 0;
					$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
					$this->attack($ev);
				}
				$this->getDataPropertyManager()->setPropertyValue(self::DATA_AIR, self::DATA_TYPE_SHORT, $airTicks);
			}else{
				$this->getDataPropertyManager()->setPropertyValue(self::DATA_AIR, self::DATA_TYPE_SHORT, 300);
			}
		}

		// tick the breeding extension if it's available
		if($this instanceof IntfCanBreed && $this->getBreedingComponent() !== null){
			// we should also check for any blocks of interest for the entity
			$this->getBreedingComponent()->checkInLove();
			// tick the breedable class embedded
			$this->getBreedingComponent()->tick();
		}

		// Timings::$timerEntityBaseTick->stopTiming();
		return $hasUpdate;
	}

	/**
	 * This function checks if the entity (this) is currently tamed and has a valid owner and
	 * the current target is a monster or an animal. If so, the entity helps the player to attack a monster.
	 *
	 * @return bool
	 */
	protected function isTargetMonsterOrAnimal() : bool{
		$isTargetMonster = false;

		if($this instanceof IntfTameable and $this->isTamed() and $this->getOwner() !== null and
			($this->getBaseTarget() instanceof Monster or $this->getBaseTarget() instanceof Animal)
		){
			$isTargetMonster = true;
		}

		return $isTargetMonster;
	}

	/**
	 * @param Player $player
	 */
	public function showButton(Player $player){
		if($this->isFriendly()){
			if($player->getInventory() != null){ // sometimes, we get null on getInventory?!
				$itemInHand = $player->getInventory()->getItemInHand()->getId();
				if($this instanceof IntfShearable and $itemInHand === Item::SHEARS and !$this->isSheared()){
					InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_SHEAR, $player);
					PureEntities::logOutput("Button text set to Shear.");

				}else if($this instanceof IntfTameable){

					$feedableItems = $this->getFeedableItems();
					$hasFeedableItemsInHand = in_array($itemInHand, $feedableItems);
					$tameFood = $this->getTameFoods();
					if(!$this->isTamed() and in_array($itemInHand, $tameFood)){
						InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_TAME, $player);
						PureEntities::logOutput("Button text set to Tame.");
					}else if($this->isTamed() and $hasFeedableItemsInHand){
						InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_FEED, $player);
						PureEntities::logOutput("Button text set to Feed for tameable entity.");
					}else if($this instanceof Wolf and $this->isTamed() and $itemInHand == ItemIds::DYE){
						InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_DYE, $player);
						PureEntities::logOutput("Button text was set to Dye for tamed Wolf.");
					}else if($this->isTamed()){ // Offer sit or stand.
						if($this->isSitting()){
							InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_STAND, $player);
							PureEntities::logOutput("Button text set to Stand.");
						}else{
							InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_SIT, $player);
							PureEntities::logOutput("Button text set to Sit.");
						}
					}

				}else if($this instanceof IntfCanBreed){
					$feedableItems = $this->getFeedableItems();
					$hasFeedableItemsInHand = in_array($itemInHand, $feedableItems);
					if($hasFeedableItemsInHand){
						InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_FEED, $player);
						PureEntities::logOutput("Button text set to Feed.");
					}
				}else{ // No button type interactions necessary.
					$damage = $player->getInventory()->getItemInHand()->getDamage();
					InteractionHelper::displayButtonText("", $player);
				}
			}

		}
	}

	/**
	 * @param Creature $creature
	 * @param float    $distance
	 * @return bool
	 */
	public function targetOption(Creature $creature, float $distance) : bool{
		$targetOption = false;

		if($this->isFriendly()){
			if(!$this->isTargetMonsterOrAnimal() and $creature instanceof Player){ // a player requests the target option
				if($creature != null and $creature->getInventory() != null){ // sometimes, we get null on getInventory?! F**k
					$itemInHand = $creature->getInventory()->getItemInHand()->getId();
					if($this instanceof IntfTameable){
						$tameFood = $this->getTameFoods();
						if(!$this->isTamed() and in_array($itemInHand, $tameFood) and $distance <= PluginConfiguration::getInstance()->getMaxInteractDistance()){
							$targetOption = true;
						}else if($this instanceof IntfCanBreed){
							if($this->isTamed() and $distance <= PluginConfiguration::getInstance()->getMaxInteractDistance()){ // tamed - it can breed!!!
								$feedableItems = $this->getFeedableItems();
								$hasFeedableItemsInHand = in_array($itemInHand, $feedableItems);
								if($hasFeedableItemsInHand){
									// check if the entity is able to follow - but only on a distance of 6 blocks
									$targetOption = $creature->spawned && $creature->isAlive() && !$creature->isClosed();
								}else{
									// reset base target when it was player before (follow by holding wheat)
									if($this->isFollowingPlayer($creature)){ // we've to reset follow when there's nothing interesting in hand
										// reset base target!
										$this->setBaseTarget($this->getBreedingComponent()->getBreedPartner()); // reset base target to breed partner (or NULL, if there's none)
									}
								}
							}
						}
					}
				}
			}
		}else{
			// when the entity is not friendly, it attacks the player!!!
			$targetOption = ($this instanceof Monster && (!($creature instanceof Player) || ($creature->isSurvival() && $creature->spawned)) && $creature->isAlive() && !$creature->isClosed() && $distance <= 81);
		}
		return $targetOption;
	}

}
