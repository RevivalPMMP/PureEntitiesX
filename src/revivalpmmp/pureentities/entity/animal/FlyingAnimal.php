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

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\FlyingEntity;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PureEntities;

// use pocketmine\event\Timings;

abstract class FlyingAnimal extends FlyingEntity implements Animal{

	public function getSpeed() : float{
		return 0.7;
	}

	public function initEntity() : void{
		parent::initEntity();

		if($this->getDataFlag(self::DATA_FLAG_BABY, 0) === null){
			$this->setDataFlag(self::DATA_FLAG_BABY, self::DATA_TYPE_BYTE, 0);
		}
	}

	public function isBaby() : bool{
		return $this->getDataFlag(self::DATA_FLAG_BABY, 0);
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		// Timings::$timerEntityBaseTick->startTiming();

		$hasUpdate = parent::entityBaseTick($tickDiff);

		// BaseEntity::entityBaseTick checks and can trigger despawn.  After calling it, we need to verify
		// that the entity is still valid for updates before performing any other tasks on it.
		if($this->isClosed() or !$this->isAlive()){
			// Timings::$timerEntityBaseTick->stopTiming();
			return false;
		}

		if(!$this->hasEffect(Effect::WATER_BREATHING) && $this->isUnderwater()){
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

		// Timings::$timerEntityBaseTick->stopTiming();
		return $hasUpdate;
	}

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
				$this->pitch = 22;
				$this->x = $this->lastX;
				$this->y = $this->lastY;
				$this->z = $this->lastZ;
			}
		}elseif(
			$target instanceof Vector3
			&& $this->distance($target) <= 1
		){
			$this->moveTime = 0;
		}
		return true;
	}

	public function showButton(Player $player){
		if($this instanceof IntfTameable){
			$itemInHand = $player->getInventory()->getItemInHand()->getId();
			$tameFood = $this->getTameFoods();
			if(!$this->isTamed() and in_array($itemInHand, $tameFood)){
				InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_TAME, $player);
				PureEntities::logOutput("Button text set to Tame.");
			}else if($this->isTamed()){ // Offer sit or stand.
				if($this->isSitting()){
					InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_STAND, $player);
					PureEntities::logOutput("Button text set to Stand.");
				}else{
					InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_SIT, $player);
					PureEntities::logOutput("Button text set to Sit.");
				}
			}
		}
	}

}
