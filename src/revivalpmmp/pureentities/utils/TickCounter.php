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

namespace revivalpmmp\pureentities\utils;

/**
 * Class TicketCounter
 *
 * A simple class that can be built in in any cases where we need to wait until ticks have been passed
 *
 * @package revivalpmmp\pureentities\utils
 */
class TickCounter{

	private $currentCounter = 0;

	private $maxCounter = 100; // 100 ticks default

	public function __construct(int $maxCounter){
		$this->maxCounter = $maxCounter;
	}

	/**
	 * Checks if the tick counter is expired. If so, it will be reset and <b>true</b> will be returned. Otherwise
	 * false is returned and the tick counter itself is increased
	 *
	 * @param int $tickDiff
	 * @return bool
	 */
	public function isTicksExpired($tickDiff = 1) : bool{
		if($this->currentCounter >= $this->maxCounter){
			$this->currentCounter = 0;
			return true;
		}
		$this->currentCounter += $tickDiff;
		return false;
	}

	/**
	 * Sets the max tick counter to be expired before isTickExpired returns true
	 *
	 * @param $maxCounter
	 */
	public function setMaxCounter($maxCounter){
		$this->maxCounter = $maxCounter;
	}

	/**
	 * @param int $currentCounter
	 */
	public function setCurrentCounter(int $currentCounter){
		$this->currentCounter = $currentCounter;
	}

	/**
	 * @return int
	 */
	public function getCurrentCounter() : int{
		return $this->currentCounter;
	}

	/**
	 * @return int
	 */
	public function getMaxCounter() : int{
		return $this->maxCounter;
	}


}