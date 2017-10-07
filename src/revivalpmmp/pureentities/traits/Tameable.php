<?php
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


namespace revivalpmmp\pureentities\traits;

use pocketmine\Player;
use pocketmine\math\Vector3;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\entity\BaseEntity;

/**
 * This trait should be used by mobs that can be tamed. It's intention is to reduce
 * the duplication of code between entities.  This concept was developled while
 * updating movement code for getting owner positions.
 */

trait Tameable {


    /**
     * Returns a position near the player (owner) of this entity
     *
     * @return Vector3|null the position near the owner
     */

    private function getPositionNearOwner(Player $owner, BaseEntity $pet): Vector3 {
        $x = $owner->x + (mt_rand(2, 3) * (mt_rand(0, 1) ==1 ?: -1));
        $z = $owner->z + (mt_rand(2, 3) * (mt_rand(0, 1) ==1 ?: -1));
        $pos = PureEntities::getInstance()->getSuitableHeightPosition($x, $owner->y, $z, $pet->getLevel());
        if ($pos !== null) {
            return new Vector3($x, $pos->y, $z);
        } else {
            return null;
        }
    }
}