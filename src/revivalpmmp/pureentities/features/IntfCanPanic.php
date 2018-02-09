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

namespace revivalpmmp\pureentities\features;

/**
 * Interface IntfCanPanic
 *
 * This interface needs to be implemented by entities that can have panic when getting attacked.
 *
 * @package revivalpmmp\pureentities\features
 */
interface IntfCanPanic{


	public function setPanicSpeed(float $panicSpeed);

	public function getPanicSpeed() : float;

	public function setNormalSpeed(float $normalSpeed);

	public function getNormalSpeed() : float;

	/**
	 * This has to be called by onUpdate / entityBaseTick
	 *
	 * @param int $tickDiff
	 * @return bool true if the entity is still in panic
	 */
	public function panicTick(int $tickDiff = 1) : bool;

	/**
	 * Checks if this entity is in panic mode (flee mode)
	 *
	 * @return bool
	 */
	public function isInPanic();

	/**
	 * Sets an entity in panic mode.
	 */
	public function setInPanic();

	/**
	 * Unsets panic for an entity
	 */
	public function unsetInPanic();

	public function panicEnabled() : bool;
}