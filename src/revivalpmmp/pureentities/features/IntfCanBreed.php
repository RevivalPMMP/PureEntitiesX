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
 * Interface IntfCanBreed
 *
 * Interface to be implemented by entities that can breed or can be a baby.
 *
 * @package revivalpmmp\pureentities\features
 */
interface IntfCanBreed extends IntfFeedable{

	/**
	 * Has to return the Breedable class initiated within entity
	 *
	 * @return mixed
	 */
	public function getBreedingComponent();

	/**
	 * Has to return the network id for the associated entity
	 *
	 * @return mixed
	 */
	public function getNetworkId();

}