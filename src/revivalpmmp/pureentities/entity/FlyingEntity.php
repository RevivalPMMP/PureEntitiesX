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
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\Animal;
use revivalpmmp\pureentities\entity\monster\flying\Blaze;

abstract class FlyingEntity extends BaseEntity{

	protected function checkTarget(bool $checkSkip = true){
		if(($checkSkip and $this->isCheckTargetAllowedBySkip()) or !$checkSkip){
			if($this->isKnockback()){
				return;
			}

			$target = $this->getBaseTarget();
			if(!($target instanceof Creature) or !$this->targetOption($target, $this->distanceSquared($target))){
				$near = PHP_INT_MAX;
				foreach($this->getLevel()->getEntities() as $creature){
					if($creature === $this || !($creature instanceof Creature) || $creature instanceof Animal){
						continue;
					}

					if($creature instanceof BaseEntity && $creature->isFriendly() == $this->isFriendly()){
						continue;
					}

					if(($distance = $this->distanceSquared($creature)) > $near or !$this->targetOption($creature, $distance)){
						continue;
					}

					$near = $distance;
					$this->setBaseTarget($creature);
				}
			}

			if($this->getBaseTarget() instanceof Creature && $this->getBaseTarget()->isAlive()){
				return;
			}

			$maxY = max($this->getLevel()->getHighestBlockAt((int) $this->x, (int) $this->z) + 15, 120);
			if($this->moveTime <= 0 or !$this->getBaseTarget() instanceof Vector3){
				$x = mt_rand(20, 100);
				$z = mt_rand(20, 100);
				if($this->y > $maxY){
					$y = mt_rand(-12, -4);
				}else{
					$y = mt_rand(-10, 10);
				}
				$this->moveTime = mt_rand(300, 1200);
				$this->setBaseTarget($this->add(mt_rand(0, 1) ? $x : -$x, $y, mt_rand(0, 1) ? $z : -$z));
			}
		}
	}

	public function updateMove($tickDiff){
		if(!$this->isMovement() or $this->isClosed()){
			return null;
		}

		if($this->isKnockback()){
			$this->move($this->motion->x * $tickDiff, $this->motion->y * $tickDiff, $this->motion->z * $tickDiff);
			$this->updateMovement();
			return null;
		}

		$before = $this->getBaseTarget();
		$this->checkTarget();
		if($this->getBaseTarget() instanceof Player or $before !== $this->getBaseTarget()){
			$x = $this->getBaseTarget()->x - $this->x;
			$y = $this->getBaseTarget()->y - $this->y;
			$z = $this->getBaseTarget()->z - $this->z;

			$diff = abs($x) + abs($z);
			if($x ** 2 + $z ** 2 < 0.5){
				$this->motion->x = 0;
				$this->motion->z = 0;
			}elseif($diff > 0){
				$this->motion->x = $this->getSpeed() * 0.15 * ($x / $diff);
				$this->motion->z = $this->getSpeed() * 0.15 * ($z / $diff);
				$this->motion->y = $this->getSpeed() * 0.27 * ($y / $diff);
				$this->yaw = rad2deg(-atan2($x / $diff, $z / $diff));
			}
			$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
		}

		$target = $this->getBaseTarget();
		$isJump = false;
		$dx = $this->motion->x * $tickDiff;
		$dy = $this->motion->y * $tickDiff;
		$dz = $this->motion->z * $tickDiff;

		$be = new Vector2($this->x + $dx, $this->z + $dz);
		$this->move($dx, $dy, $dz);
		$af = new Vector2($this->x, $this->z);

		if($be->x != $af->x || $be->y != $af->y){
			if($this instanceof Blaze){
				$x = 0;
				$z = 0;
				if($be->x - $af->x != 0){
					$x = $be->x > $af->x ? 1 : -1;
				}
				if($be->y - $af->y != 0){
					$z = $be->y > $af->y ? 1 : -1;
				}

				$vec = new Vector3(Math::floorFloat($be->x) + $x, $this->y, Math::floorFloat($be->y) + $z);
				$block = $this->level->getBlock($vec->add($x, 0, $z));
				$block2 = $this->level->getBlock($vec->add($x, 1, $z));
				if(!$block->canPassThrough()){
					$bb = $block2->getBoundingBox();
					if(
						$this->motion->y > -$this->gravity * 4
						&& ($block2->canPassThrough() || ($bb == null || $bb->maxY - $this->y <= 1))
					){
						$isJump = true;
						if($this->motion->y >= 0.3){
							$this->motion->y += $this->gravity;
						}else{
							$this->motion->y = 0.3;
						}
					}
				}

				if(!$isJump){
					$this->moveTime -= 90 * $tickDiff;
				}
			}else{
				$this->moveTime -= 90 * $tickDiff;
			}
		}

		if($this instanceof Blaze){
			if($this->onGround && !$isJump){
				$this->motion->y = 0;
			}else if(!$isJump){
				if($this->motion->y > -$this->gravity * 4){
					$this->motion->y = -$this->gravity * 4;
				}else{
					$this->motion->y -= $this->gravity;
				}
			}
		}
		$this->updateMovement();
		return $target;
	}

}