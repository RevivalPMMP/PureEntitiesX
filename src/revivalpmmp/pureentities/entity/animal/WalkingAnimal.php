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

namespace revivalpmmp\pureentities\entity\animal;

use pocketmine\entity\Creature;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\Item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\entity\WalkingEntity;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfShearable;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

// use pocketmine\event\Timings;

abstract class WalkingAnimal extends WalkingEntity implements Animal{

	// for eating grass etc. pp
	protected $blockInterestTime = 0;

	public function getSpeed() : float{
		return 0.7;
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

		if($this->getLevel() !== null && !$this->hasEffect(Effect::WATER_BREATHING) && $this->isUnderwater()){
			$hasUpdate = true;
			$airTicks = $this->getDataPropertyManager()->getPropertyValue(self::DATA_AIR, Entity::DATA_TYPE_SHORT) - $tickDiff;
			if($airTicks <= -20){
				$airTicks = 0;
				$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
				$this->attack($ev);
			}
			$this->getDataPropertyManager()->setPropertyValue(Entity::DATA_AIR, Entity::DATA_TYPE_SHORT, $airTicks);
		}else{
			$this->getDataPropertyManager()->setPropertyValue(Entity::DATA_AIR, Entity::DATA_TYPE_SHORT, 300);
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
	 * This method is called from the server framework for each entity. This is our main
	 * entry point when it comes to tracing how all that stuff works. With each server
	 * tick each entity is ticked by calling this entry method.
	 *
	 * @param $currentTick
	 * @return bool
	 */
	public function onUpdate(int $currentTick) : bool{
		if($this->getLevel() == null) return false;
		if($this->isClosed() or !$this->isAlive()){
			return parent::onUpdate($currentTick);
		}
		$tickDiff = $currentTick - $this->lastUpdate;
		$this->lastUpdate = $currentTick;
		$this->entityBaseTick($tickDiff);

		$target = $this->updateMove($tickDiff);
		if($target instanceof Player){
			if($this->distance($target) <= 2){
				$this->pitch = 22; // pitch is the angle for looking up or down while yaw is looking left/right
				$this->setPosition(new Vector3($this->lastX, $this->lastY, $this->lastZ));
			}
		}elseif(
			$target instanceof Vector3
			&& $this->distanceSquared($target) <= 1
		){
			$this->moveTime = 0;
		}
		return true;
	}

	/**
	 * Does the check for interesting blocks and sets the baseTarget if an interesting block is found
	 */
	protected function getCurrentBlock(){
		$block = null;
		// no creature is the target, so we can check if there's any interesting block for the entity
		if($this->blockInterestTime > 0){ // we take a look at interesting blocks only each 300 ticks!
			$this->blockInterestTime--;
		}else{ // it's time to check for any interesting block the entity is on
			$this->blockInterestTime = PluginConfiguration::getInstance()->getBlockOfInterestTicks();
			$temporalVector = new Vector3($this->x, $this->y - $this->height / 2, $this->z);
			$block = $this->level->getBlock($temporalVector);
		}
		return $block;
	}


	/**
	 * Returns all blocks around in a flat way - meaning, there is no search in y axis, only what the entity provides
	 * with it's y property.
	 *
	 * @param int $range the range in blocks
	 * @return array an array of Block
	 */
	protected function getBlocksFlatAround(int $range){
		if($this instanceof BaseEntity){
			$blocksAround = [];

			$minX = $this->x - $range;
			$maxX = $this->x + $range;
			$minZ = $this->z - $range;
			$maxZ = $this->z + $range;
			$temporalVector = new Vector3($this->x, $this->y - $this->height / 2, $this->z);

			for($x = $minX; $x <= $maxX; $x++){
				for($z = $minZ; $z <= $maxZ; $z++){
					$blocksAround[] = $this->level->getBlock($temporalVector->setComponents($x, $temporalVector->y, $this->z));
				}
			}

			return $blocksAround;
		}
		return [];
	}

	/**
	 * @param Creature $creature
	 * @param float    $distance
	 * @return bool
	 */
	public function targetOption(Creature $creature, float $distance) : bool{
		$targetOption = false;
		if($creature instanceof Player){ // a player requests the target option
			if($creature != null and $creature->getInventory() != null){ // sometimes, we get null on getInventory?!
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
								$targetOption = $creature->spawned && $creature->isAlive() && !$creature->closed;
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
		return $targetOption;
	}


	// TODO: Consider moving this to WalkingEntity to reduce code duplication.

	/**
	 * The general showButton function is implemented here for entities that are walking animals
	 * and are interactive - we're working with interfaces here.
	 *
	 * @param Player $player
	 */
	public function showButton(Player $player){
		if($player->getInventory() != null){ // sometimes, we get null on getInventory?!
			$itemInHand = $player->getInventory()->getItemInHand()->getId();
			// Redefining how to determine button circumstance.  There are several animals that are breedable
			// without being tameable (ie. Sheep, Cows, Mooshroom, Pigs, Chicken)
			PureEntities::logOutput("Player looking at $this");
			PureEntities::logOutput("showButton: Item in Hand $itemInHand");
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
				}else if($this instanceof Sheep and $itemInHand == ItemIds::DYE and
					$player->getInventory()->getItemInHand()->getDamage() > 0
				){
					InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_DYE, $player);
					PureEntities::logOutput("Button text set to Dye for sheep.");
				}
			}else{ // No button type interactions necessary.
				$damage = $player->getInventory()->getItemInHand()->getDamage();
				PureEntities::logOutput("Player looking at $this with item in hand $itemInHand.");
				PureEntities::logOutput("Item in hand damage $damage.");
				InteractionHelper::displayButtonText("", $player);
			}
		}
	}
}
