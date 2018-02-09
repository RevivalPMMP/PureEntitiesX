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

use revivalpmmp\pureentities\PureEntities;

/**
 * Class PeTimings
 *
 * This class is only a helper class to determine how long e.g. a specific feature takes time
 * in milliseconds. You need to call startTiming and then stopTiming and take the response from
 * stopTiming.
 *
 * @package revivalpmmp\pureentities\utils
 */
class PeTimings{

	private static $timingsHolder = [];

	/**
	 * Remember the timestamp when we started a timed function. This method has to be
	 * called when we want to start record timings for a specific function or part of code
	 *
	 * @param string $description
	 */
	public static function startTiming(string $description){
		self::$timingsHolder[$description] = round(microtime(1));
	}

	/**
	 * This stops the timing and returns the expired time between start and stop in milliseconds
	 *
	 * @param string $description
	 * @param bool   $logToFile
	 * @return float
	 */
	public static function stopTiming(string $description, bool $logToFile = false){
		$timeExpired = round(microtime(1)) - self::$timingsHolder[$description];

		if($logToFile){
			PureEntities::logOutput("PeTimings[$description]: took $timeExpired microseconds to complete.");
		}

		return $timeExpired;
	}

}