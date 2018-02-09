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

use pocketmine\Player;

interface IntfTameable{

	public function setTamed(bool $tamed);

	public function isTamed() : bool;

	/**
	 * @return mixed null|Player
	 */
	public function getOwner();

	public function setOwner(Player $player);

	public function getOwnerName();

	/**
	 * @return mixed (array)
	 */
	public function getTameFoods();

	/**
	 * Called when an entity gets tamed by a player
	 *
	 * @param Player $player
	 * @return mixed
	 */
	public function attemptToTame(Player $player);

	/**
	 * This method is called from SetTamedOwnerTask to map the owner after the player logged in
	 * Implementation details in Wolf.php
	 * @return mixed
	 */
	public function mapOwner();

	/**
	 * Should return true when tamed entity is sitting
	 * @return bool
	 */
	public function isSitting() : bool;

	/**
	 * Sets entity sitting or not.
	 *
	 * @param bool $sit
	 */
	public function setSitting(bool $sit = true);

}