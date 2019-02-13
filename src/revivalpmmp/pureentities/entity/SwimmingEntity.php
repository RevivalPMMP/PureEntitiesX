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

namespace revivalpmmp\pureentities\entity;

use pocketmine\entity\Creature;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use revivalpmmp\pureentities\entity\animal\Animal;

abstract class SwimmingEntity extends BaseEntity{

	/*
	 * TODO:
	 * Adjust updateMove and set gravity to 0 if entity is in water
	 */

	protected function checkTarget(bool $checkSkip = true){
		if(($checkSkip and $this->isCheckTargetAllowedBySkip()) or !$checkSkip){
			if($this->isKnockback()){
				return;
			}

			$target = $this->getBaseTarget();
			if(!$target instanceof Creature or !$this->targetOption($target, $this->distanceSquared($target))){
				$near = PHP_INT_MAX;
				foreach($this->getLevel()->getEntities() as $creature){
					if($creature === $this || !($creature instanceof Creature) || $creature instanceof Animal){
						continue;
					}

					if($creature instanceof BaseEntity && $creature->isFriendly() == $this->isFriendly()){
						continue;
					}

					$distance = $this->distanceSquared($creature);

					if($distance > $near or !$this->targetOption($creature, $distance)){
						continue;
					}
					$near = $distance;

					$this->moveTime = 0;
					$this->setBaseTarget($creature);
				}
			}

			if($this->getBaseTarget() instanceof Creature && $this->getBaseTarget()->isAlive()){
				return;
			}

			if($this->moveTime <= 0 or !($this->getBaseTarget() instanceof Vector3)){
				$x = mt_rand(20, 100);
				$z = mt_rand(20, 100);
				$this->moveTime = mt_rand(300, 1200);
				$this->setBaseTarget($this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z));
			}
		}
	}

	/**
	 * @param int $tickDiff
	 *
	 * @return null|Vector3
	 */
	public function updateMove($tickDiff){
		if(!$this->isMovement() or $this->isClosed()){
			return null;
		}

		if($this->isKnockback()){
			$this->move($this->motion->x * $tickDiff, $this->motion->y, $this->motion->z * $tickDiff);
			$this->motion->y -= 0.2 * $tickDiff;
			$this->updateMovement();
			return null;
		}

		$before = $this->getBaseTarget();
		$this->checkTarget();
		if($this->getBaseTarget() instanceof Creature or $before !== $this->getBaseTarget()){
			$x = $this->getBaseTarget()->x - $this->x;
			$y = $this->getBaseTarget()->y - $this->y;
			$z = $this->getBaseTarget()->z - $this->z;

			$diff = abs($x) + abs($z);
			if($x ** 2 + $z ** 2 < 0.7){
				$this->motion->x = 0;
				$this->motion->z = 0;
			}elseif($diff > 0){
				$this->motion->x = $this->getSpeed() * 0.15 * ($x / $diff);
				$this->motion->z = $this->getSpeed() * 0.15 * ($z / $diff);
				$this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
			}
			$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
		}

		$dx = $this->motion->x * $tickDiff;
		$dz = $this->motion->z * $tickDiff;
		if($this->stayTime > 0){
			$this->stayTime -= $tickDiff;
			$this->move(0, $this->motion->y * $tickDiff, 0);
		}else{
			$be = new Vector2($this->x + $dx, $this->z + $dz);
			$this->move($dx, $this->motion->y * $tickDiff, $dz);
			$af = new Vector2($this->x, $this->z);

			if(($be->x != $af->x || $be->y != $af->y)){
				$this->moveTime -= 90 * $tickDiff;
			}
		}

		$this->updateMovement();
		return $this->getBaseTarget();
	}
}
