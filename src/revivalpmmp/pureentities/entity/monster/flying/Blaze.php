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

use pocketmine\block\Liquid;
use pocketmine\block\Stair;
use pocketmine\block\StoneSlab;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\entity\animal\Animal;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\entity\monster\FlyingMonster;
use revivalpmmp\pureentities\entity\projectile\SmallFireball;

class Blaze extends FlyingMonster implements ProjectileSource{
	const NETWORK_ID = Data::NETWORK_IDS["blaze"];

	public function initEntity() : void{
		parent::initEntity();
		$this->gravity = 0.04;

		$this->fireProof = true;
		$this->setDamage([0, 0, 0, 0]);
	}

	public function getName() : string{
		return "Blaze";
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
		if($this->motion->y < 0){
			return false;
		}

		if($this->motion->y == $this->gravity * 2){
			return $this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) $this->y, Math::floorFloat($this->z))) instanceof Liquid;
		}else if($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.8), Math::floorFloat($this->z))) instanceof Liquid){
			$this->motion->y = $this->gravity * 2;
			return true;
		}

		if($this->stayTime > 0){
			return false;
		}

		$block = $this->level->getBlock($this->add($dx, 0, $dz));
		if($block instanceof StoneSlab || $block instanceof Stair){
			$this->motion->y = 0.5;
			return true;
		}
		return false;
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
			}else{
				if($this->getBaseTarget() instanceof Creature){
					$this->motion->x = 0;
					$this->motion->z = 0;
					if($this->distance($this->getBaseTarget()) > $this->y - $this->getLevel()->getHighestBlockAt((int) $this->x, (int) $this->z)){
						$this->motion->y = $this->gravity;
					}else{
						$this->motion->y = 0;
					}
				}elseif($diff > 0){
					$this->motion->x = $this->getSpeed() * 0.15 * ($x / $diff);
					$this->motion->z = $this->getSpeed() * 0.15 * ($z / $diff);
				}
			}
			if($diff > 0) $this->yaw = rad2deg(-atan2($x / $diff, $z / $diff));
			$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
		}

		$dx = $this->motion->x * $tickDiff;
		$dz = $this->motion->z * $tickDiff;
		$isJump = $this->checkJump($dx, $dz);
		if($this->stayTime > 0){
			$this->stayTime -= $tickDiff;
			$this->move(0, $this->motion->y * $tickDiff, 0);
		}else{
			$be = new Vector2($this->x + $dx, $this->z + $dz);
			$this->move($dx, $this->motion->y * $tickDiff, $dz);
			$af = new Vector2($this->x, $this->z);

			if(($be->x != $af->x || $be->y != $af->y) && !$isJump){
				$this->moveTime -= 90 * $tickDiff;
			}
		}

		if(!$isJump){
			if($this->onGround){
				$this->motion->y = 0;
			}elseif($this->motion->y > -$this->gravity * 4){
				$this->motion->y = -$this->gravity * 4;
			}else{
				$this->motion->y -= $this->gravity;
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
		if($this->attackDelay > 20 && $this->distance($player) <= 18){
			$this->attackDelay = 0;

			$f = 1.2;
			$yaw = $this->yaw + mt_rand(-220, 220) / 10;
			$pitch = $this->pitch + mt_rand(-120, 120) / 10;
			$pos = new Location(
				$this->x + (-sin(rad2deg($yaw)) * cos(rad2deg($pitch)) * 0.5),
				$this->y + $this->getEyeHeight(),
				$this->z + (cos(rad2deg($yaw)) * cos(rad2deg($pitch)) * 0.5),
				$yaw,
				$pitch,
				$this->level
			);

			$motion = $player->subtract($this);
			$nbt = Entity::createBaseNBT($pos, $motion, $yaw, $pitch);
			$fireball = Entity::createEntity("SmallFireball", $this->level, $nbt);
			if(!($fireball instanceof SmallFireball)){
				return;
			}
			$fireball->setExplode(true);

			$this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($fireball));
			if($launch->isCancelled()){
				$fireball->kill();
			}else{
				$fireball->spawnToAll();
				$this->level->addSound(new LaunchSound($this), $this->getViewers());
			}
		}
	}

	public function getDrops() : array{
		if($this->isLootDropAllowed()){
			return [Item::get(Item::BLAZE_ROD, 0, mt_rand(0, 1))];
		}else{
			return [];
		}
	}

	public function updateXpDropAmount() : void{
		$this->xpDropAmount = 10;
	}


}
