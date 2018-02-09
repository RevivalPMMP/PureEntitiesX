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

namespace revivalpmmp\pureentities\traits;

use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\utils\TickCounter;

trait CanPanic{
	private $normalSpeed = 1.0;
	private $panicSpeed = 1.2;
	/**
	 * @var null|TickCounter
	 */
	private $panicCounter = null;
	private $panicEnabled = true;
	private $panicTicks = 100;

	/*
	 * Need to add the following to each creature that can panic
	 *
	 * $this->panicEnabled = PluginConfiguration::getInstance()->getEnablePanic();
	 * $this->panicTicks = PluginConfiguration::getInstance()->getPanicTicks();
	 *
	 * Considering an initPanic function.
	 */

	public function setPanicSpeed(float $panicSpeed){
		$this->panicSpeed = $panicSpeed;
	}

	public function getPanicSpeed() : float{
		return $this->panicSpeed;
	}

	public function setNormalSpeed(float $normalSpeed){
		$this->normalSpeed = $normalSpeed;
	}

	public function getNormalSpeed() : float{
		return $this->normalSpeed;
	}

	/**
	 * This has to be called by onUpdate / entityBaseTick
	 *
	 * @param int $tickDiff
	 * @return bool true if the entity is still in panic
	 */
	public function panicTick(int $tickDiff = 1) : bool{
		if($this->isInPanic()){
			PureEntities::logOutput("$this: is in panic. Checking if expired.");
			if($this->panicCounter->isTicksExpired($tickDiff)){
				PureEntities::logOutput("$this: panic expired. Resetting entity status.");
				$this->unsetInPanic();
				return false; // not in panic anymore
			}
			PureEntities::logOutput("$this: still panicking.");
			return true; // Still panicking
		}
		PureEntities::logOutput("$this: not panicking");
		return false; // Not panicking
	}

	/**
	 * Checks if this entity is in panic mode (flee mode)
	 *
	 * @return bool
	 */
	public function isInPanic() : bool{
		return $this->panicCounter !== null;
	}

	/**
	 * Sets an entity in panic mode.
	 */
	public function setInPanic(){
		$this->panicCounter = new TickCounter($this->panicTicks); // x ticks in panic
		/**
		 * @var $this IntfCanPanic
		 */
		if(!$this instanceof IntfCanBreed or ($this instanceof IntfCanBreed && !$this->getBreedingComponent()->isBaby())){
			$this->speed = $this->getPanicSpeed();
		}
		$this->moveTime = $this->panicTicks; // move for x ticks
		PureEntities::logOutput("$this: in panic now [speed:" . $this->speed . "] [duration:" . $this->panicTicks . "]");
	}

	/**
	 * Unsets panic for an entity
	 */
	public function unsetInPanic(){
		$this->panicCounter = null;
		/**
		 * @var $this IntfCanPanic
		 */
		$this->speed = $this->getNormalSpeed();
		$this->setBaseTarget(null);
		PureEntities::logOutput("$this: unset panic now [speed:" . $this->speed . "]");
	}

	public function panicEnabled() : bool{
		return $this->panicEnabled;
	}
}