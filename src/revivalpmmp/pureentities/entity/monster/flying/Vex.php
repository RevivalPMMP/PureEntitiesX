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

namespace revivalpmmp\pureentities\entity\monster\flying;

use revivalpmmp\pureentities\entity\animal\Animal;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\entity\monster\FlyingMonster;
use revivalpmmp\pureentities\entity\monster\Monster;
use revivalpmmp\pureentities\entity\projectile\FireBall;
use revivalpmmp\pureentities\PureEntities;
use pocketmine\block\Liquid;
use pocketmine\block\StoneSlab;
use pocketmine\block\Stair;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;

class Vex extends FlyingMonster implements Monster{

	// Base created from Blaze
	// TODO create methods specific to Vexes
	const NETWORK_ID = Data::NETWORK_IDS["vex"];

	public function initEntity(){
		parent::initEntity();
		$this->width = Data::WIDTHS[self::NETWORK_ID];
		$this->height = Data::HEIGHTS[self::NETWORK_ID];
		$this->gravity = 0.04;

		$this->setDamage([0, 0, 0, 0]);
	}

	public function getName() : string{
		return "Vex";
	}

	public function checkTarget(bool $checkSkip = true){
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

			if($this->moveTime <= 0 or !$this->getBaseTarget() instanceof Vector3){
				$x = mt_rand(20, 100);
				$z = mt_rand(20, 100);
				$this->moveTime = mt_rand(300, 1200);
				$this->setBaseTarget($this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z));
			}
		}
	}

	/**
	 * @param int $dx
	 * @param int $dz
	 *
	 * @return bool
	 */
	protected function checkJump($dx, $dz){
		if($this->motionY < 0){
			return false;
		}

		if($this->motionY == $this->gravity * 2){
			return $this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) $this->y, Math::floorFloat($this->z))) instanceof Liquid;
		}else if($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.8), Math::floorFloat($this->z))) instanceof Liquid){
			$this->motionY = $this->gravity * 2;
			return true;
		}

		if($this->stayTime > 0){
			return false;
		}

		$block = $this->level->getBlock($this->add($dx, 0, $dz));
		if($block instanceof StoneSlab || $block instanceof Stair){
			$this->motionY = 0.5;
			return true;
		}
		return false;
	}

	public function updateMove($tickDiff){
		if(!$this->isMovement() or $this->isClosed()){
			return null;
		}

		if($this->isKnockback()){
			$this->move($this->motionX * $tickDiff, $this->motionY * $tickDiff, $this->motionZ * $tickDiff);
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
				$this->motionX = 0;
				$this->motionZ = 0;
			}else{
				if($this->getBaseTarget() instanceof Creature){
					$this->motionX = 0;
					$this->motionZ = 0;
					if($this->distance($this->getBaseTarget()) > $this->y - $this->getLevel()->getHighestBlockAt((int) $this->x, (int) $this->z)){
						$this->motionY = $this->gravity;
					}else{
						$this->motionY = 0;
					}
				}elseif($diff > 0){
					$this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
					$this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
				}
			}
			if($diff > 0) $this->yaw = rad2deg(-atan2($x / $diff, $z / $diff));
			$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
		}

		$dx = $this->motionX * $tickDiff;
		$dz = $this->motionZ * $tickDiff;
		$isJump = $this->checkJump($dx, $dz);
		if($this->stayTime > 0){
			$this->stayTime -= $tickDiff;
			$this->move(0, $this->motionY * $tickDiff, 0);
		}else{
			$be = new Vector2($this->x + $dx, $this->z + $dz);
			$this->move($dx, $this->motionY * $tickDiff, $dz);
			$af = new Vector2($this->x, $this->z);

			if(($be->x != $af->x || $be->y != $af->y) && !$isJump){
				$this->moveTime -= 90 * $tickDiff;
			}
		}

		if(!$isJump){
			if($this->onGround){
				$this->motionY = 0;
			}elseif($this->motionY > -$this->gravity * 4){
				$this->motionY = -$this->gravity * 4;
			}else{
				$this->motionY -= $this->gravity;
			}
		}
		$this->updateMovement();
		return $this->getBaseTarget();
	}

	/**
	 * Attack the player
	 *
	 * @param Entity $player
	 */
	public function attackEntity(Entity $player){
		return;
	}

	public function getDrops() : array{
		return [];
	}

	public function getXpDropAmount() : int{
		return 10;
	}
}