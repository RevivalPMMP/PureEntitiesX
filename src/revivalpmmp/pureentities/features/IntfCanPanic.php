<?php

namespace revivalpmmp\pureentities\features;

/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2017 RevivalPMMP

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>. */

/**
 * Interface IntfCanPanic
 *
 * This interface needs to be implemented by entities that can have panic when getting attacked.
 *
 * @package revivalpmmp\pureentities\features
 */
interface IntfCanPanic {

    /**
     * Should return the speed when in panic
     *
     * @return float
     */
    public function getPanicSpeed(): float;

    /**
     * Should return the normal speed (of adult) of the entity
     *
     * @return float
     */
    public function getNormalSpeed(): float;
}