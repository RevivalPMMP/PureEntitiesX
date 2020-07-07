<?php
declare(strict_types=1);

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

use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\entity\Creature;
use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\Animal;
use revivalpmmp\pureentities\entity\monster\walking\PigZombie;
use revivalpmmp\pureentities\features\IntfCanEquip;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\utils\PeTimings;


abstract class WalkingEntity extends BaseEntity{

	protected function checkTarget(bool $checkSkip = true){
		if(($checkSkip and $this->isCheckTargetAllowedBySkip()) or !$checkSkip){
			PeTimings::startTiming("WalkingAnimal: checkTarget()");
			if($this->isKnockback()){
				PeTimings::stopTiming("WalkingAnimal: checkTarget()");
				return;
			}

			$target = $this->getBaseTarget();
			if(!$target instanceof Creature or !$this->targetOption($target, $this->distanceSquared($target))){
				$near = PHP_INT_MAX;
				foreach($this->getLevel()->getEntities() as $creature){
					if($creature === $this || !($creature instanceof Creature) || $creature instanceof Animal){
						continue;
					}

					if($creature instanceof BaseEntity && $creature->isFriendly() === $this->isFriendly()){
						continue;
					}

					$distance = $this->distanceSquared($creature);
					if(
						$distance <= 100
						&& $this instanceof PigZombie && $this->isAngry()
						&& $creature instanceof PigZombie && !$creature->isAngry()
					){
						$creature->setAngry(1000);
					}

					if($distance > $near or !$this->targetOption($creature, $distance)){
						continue;
					}

					$near = $distance;

					$this->moveTime = 0;
					$this->setBaseTarget($creature);
				}
			}

			if($this->getBaseTarget() instanceof Creature && $this->getBaseTarget()->isAlive()){
				PeTimings::stopTiming("WalkingAnimal: checkTarget()", true);
				return;
			}

			if(($this->moveTime <= 0 or !($this->getBaseTarget() instanceof Vector3)) and $this->motion->y === 0){
				if(!$this->idlingComponent->checkAndSetIdling()){
					$maxAttempts = 12;
					for($attempts = 0; $attempts <= $maxAttempts; $attempts++){
						$locationBlock = $this->getLevel()->getBlock($this->findRandomLocation());
						$locationBlockID = $locationBlock->getId();
						$underFirstBlock = $this->getLevel()->getBlock($locationBlock->subtract(0, 1));
						$underFirstBlockID = $this->getLevel()->getBlock($locationBlock->subtract(0, 1))->getId();
						$underSecondBlock = $this->getLevel()->getBlock($locationBlock->subtract(0, 2));
						$underFirstBlockBox = $underFirstBlock->getCollisionBoxes()[0] ?? null;
						$underFirstBlockBoxmaxY = $underFirstBlockBox === null ? 0 : $underFirstBlockBox->maxY;
						$underSecondBlockBox = $underSecondBlock->getCollisionBoxes()[0] ?? null;
						$underSecondBlockBoxmaxY = $underSecondBlockBox === null ? 0 : $underSecondBlockBox->maxY;
						//Going into Liquid ? (Avoid strange location too)
						if($underFirstBlock instanceof Liquid OR ($locationBlockID === 0 AND $underFirstBlockID === 0)){
							//May i walk into this ? (If my height is OK ...)
							if($this->eyeHeight > ($underFirstBlockBoxmaxY + (1 - $underSecondBlockBoxmaxY))){
								$this->setBaseTarget($locationBlock);
								break;
							}else{
								$this->setBaseTarget(null);
								continue;
							}
						}else{
							$this->setBaseTarget($locationBlock);
							break;
						}
					}
					if($attempts > $maxAttempts){
						$this->idlingComponent->checkAndSetIdling(true);
						PureEntities::logOutput("WalkingAnimal: checkTarget(): Not found good RandomLocation - Max attempts reached, go idling");
					}
				}
			}
			PeTimings::stopTiming("WalkingAnimal: checkTarget()", true);
		}
	}

	/**
	 * @param int $tickDiff
	 *
	 * @return null|Vector3
	 */
	public function updateMove($tickDiff){
		if($this->isClosed() or $this->getLevel() === null or !$this->isMovement()){
			return null;
		}

		if($this->isKnockback()){
			$this->move($this->motion->x * $tickDiff, $this->motion->y, $this->motion->z * $tickDiff);
			$this->motion->y -= 0.2 * $tickDiff;
			$this->updateMovement();
			return null;
		}

		if($this->idlingComponent->isIdling() and !$this->idlingComponent->stopIdling($tickDiff)){
			$this->idlingComponent->doSomeIdleStuff($tickDiff);
			return null;
		}

		$before = $this->getBaseTarget();
		$this->checkTarget();
		if($this->getBaseTarget() instanceof Creature or $this->getBaseTarget() instanceof Block or $before !== $this->getBaseTarget() and
			$this->getBaseTarget() !== null
		){
			$x = $this->getBaseTarget()->x - $this->x;
			$y = $this->getBaseTarget()->y - $this->y;
			$z = $this->getBaseTarget()->z - $this->z;

			$distance = $this->distanceSquared($this->getBaseTarget());

			if($this instanceof IntfTameable and
				$this->getBaseTarget() instanceof Player and
				$this->isTamed() and
				$distance <= 6
			){ // before moving nearer to the player, check if distance
				// this entity is tamed and the target is the owner - hold distance 4 blocks
				$this->stayTime = 50; // rest for 50 ticks before moving on ...
			}
			$diff = abs($x) + abs($z);
			if($x ** 2 + $z ** 2 < 0.7){
				$this->motion->x = 0;
				$this->motion->z = 0;
			}elseif($diff > 0){
				$this->motion->x = $this->getSpeed() * 0.15 * ($x / $diff);
				$this->motion->z = $this->getSpeed() * 0.15 * ($z / $diff);
				$this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
			}
			if($this->getBaseTarget() instanceof Living){
				$this->pitch = $y === 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
			}else{
				$this->pitch = 0;
			}

		}else if(($target = $this->getBaseTarget()) instanceof ItemEntity and $this instanceof IntfCanEquip){ // mob equipment
			$distance = $this->distanceSquared($this->getBaseTarget());
			if($distance <= 1.5){
				/** @var ItemEntity $target */
				$this->getMobEquipment()->itemReached($target);
			}
		}

		$dx = $this->motion->x * $tickDiff;
		$dz = $this->motion->z * $tickDiff;
		$isJump = false;
		if($this->isCollidedHorizontally or $this->isUnderwater()){
			$isJump = $this->checkJump($dx, $dz);
		}

		if($this->stayTime > 0){
			$this->stayTime -= $tickDiff;
			$this->move(0, $this->motion->y * $tickDiff, 0);
		}//I cannot jump, so am i collided and cannot jump ? (Without target creature, and on ground)
		elseif($this->isCollidedHorizontally && !$isJump && !$this->getBaseTarget() instanceof Creature && $before === $this->getBaseTarget() && $this->isOnGround()){
			$this->move(0, $this->motion->y * $tickDiff, 0);
			// $this->yaw = $this->getYaw() + mt_rand(-120, 120);
			$this->motion->x = 0;
			$this->motion->z = 0;
			$this->setBaseTarget(null);
		}//I cannot jump, so am i collided and cannot jump ? (with target, and on ground)
		elseif($this->isCollidedHorizontally && !$isJump && $this->isOnGround()){
			$this->motion->x = 0;
			$this->motion->z = 0;
			$this->move(0, $this->motion->y * $tickDiff, 0);
		}else{
			$futureLocation = new Vector2($this->x + $dx, $this->z + $dz);
			$this->move($dx, $this->motion->y * $tickDiff, $dz);
			$myLocation = new Vector2($this->x, $this->z);
			if(($futureLocation->x !== $myLocation->x || $futureLocation->y !== $myLocation->y) && !$isJump){
				$this->moveTime -= 90 * $tickDiff;
			}
		}

		if(!$isJump){
			if($this->isOnGround()){
				$this->motion->y = 0;
			}else if($this->motion->y > -$this->gravity * 4){
				if(!($this->getLevel()->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.8), Math::floorFloat($this->z))) instanceof Liquid)){
					$this->motion->y -= $this->gravity * 1;
				}
			}else{
				$this->motion->y -= $this->gravity * $tickDiff;
			}

			//Handle jumping entity
			if($this->jumper && (abs($this->motion->x) + abs($this->motion->z)) > 0.05 && $this->motion->y === 0){
				$this->jump();
				foreach($this->getLevel()->getPlayers() as $player){ // don't know if this is the correct one :/
					if($player->distance($this) <= 49){
						$pk = new ActorEventPacket();
						$pk->entityRuntimeId = $this->getId();
						$pk->event = ActorEventPacket::JUMP;
						$player->dataPacket($pk);
					}
				}
			}
		}
		$this->updateMovement();
		return $this->getBaseTarget();
	}

	/**
	 * This method checks the jumping for the entity. It should only be called when isCollidedHorizontally is set to
	 * true on the entity.
	 *
	 * @param int $dx
	 * @param int $dz
	 *
	 * @return bool
	 */
	protected function checkJump($dx, $dz){
		PureEntities::logOutput("$this: entering checkJump [dx:$dx] [dz:$dz] - level: " . $this->getLevel()->getName());
		if($this->motion->y === $this->gravity * 2){ // swimming
			PureEntities::logOutput("$this: checkJump(): motionY === gravity*2");
			return $this->getLevel()->getBlock(new Vector3(Math::floorFloat($this->x), (int) $this->y, Math::floorFloat($this->z))) instanceof Liquid;
		}else{ // dive up?
			if($this->getLevel()->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.8), Math::floorFloat($this->z))) instanceof Liquid){
				PureEntities::logOutput("$this: checkJump(): instanceof liquid");
				$this->motion->y = $this->gravity * 2; // set swimming (rather walking on water ;))
				return true;
			}
		}

		if($this->motion->y > 0.1 or $this->stayTime > 0){ // when entities are "hunting" they sometimes have a really small y motion (lesser than 0.1) so we need to take this into account
			PureEntities::logOutput("$this: checkJump(): onGround:" . $this->isOnGround() . ", stayTime:" . $this->stayTime . ", motionY:" . $this->motion->y);
			return false;
		}

		if($this->getDirection() === null){ // without a direction jump calculation is not possible!
			PureEntities::logOutput("$this: checkJump(): no direction given ...");
			return false;
		}

		PureEntities::logOutput("$this: checkJump(): position is [x:" . $this->x . "] [y:" . $this->y . "] [z:" . $this->z . "]");
		//Is there anything upper ? If yes, i cannot jump ...
		$entityUpperBlock = $this->getLevel()->getBlock(new Vector3(Math::floorFloat($this->x), Math::floorFloat($this->y + $this->height) + 1, Math::floorFloat($this->z)));
		if(!empty($entityUpperBlock->getCollisionBoxes())){
			PureEntities::logOutput("$this: checkJump(): There is block upper the entity: $entityUpperBlock");
			return false;
		}

		//I'm in collision with these block, so if the hightest can be passed, jump, else, look if one of these can be passed, and go to him
		$highestBlock = 0;
		$lowestBlock = null;
		foreach($this->level->getCollisionBlocks($this->boundingBox->addCoord($this->motion->x, $this->motion->y, $this->motion->z)) as $_ => $block){

			$blockBox = $block->getCollisionBoxes()[0] ?? null;
			$blockBoxDiff = $blockBox === null ? 0 : $blockBox->maxY - $blockBox->minY;

			if($blockBox === null or ($block->getCollisionBoxes()[0]->maxY - $this->boundingBox->minY) <= 0){
				continue;
			}

			//Do the same for the upper block, id there is any collisionBox, consider as a block
			//If i jump and have 2 slab or stair, so i cannot jump over these 2
			PureEntities::logOutput("$this: CollisionBlock : $block --Height: $blockBoxDiff-- Pos: [x:" . $block->x . "] [y:" . $block->y . "] [z:" . $block->z . "]");
			$upperblock = $this->getLevel()->getBlock($block->add(0, 1, 0));
			if(!empty($upperblock->getCollisionBoxes())){
				$blockBoxDiff = $blockBoxDiff + 1;
				PureEntities::logOutput("$this: CollisionBlock - upperBlock Detected - New Height($blockBoxDiff) : $upperblock - Pos: [x:" . $upperblock->x . "] [y:" . $upperblock->y . "] [z:" . $upperblock->z . "]");
			}
			if($blockBoxDiff > $highestBlock){
				$highestBlock = $blockBoxDiff;
			}
			if($lowestBlock === null){
				$lowestBlock = $blockBoxDiff;
			}elseif($lowestBlock > $blockBoxDiff){
				$lowestBlock = $blockBoxDiff;
			}

		}
		//TODO: Tested currently with 'lowestBlock', but if i want to jump over 2 block, and the block of the left or rigth (colliding)
		//is OK for jump, monster will go up ... MAgic :D
		PureEntities::logOutput("$this: CollisionBlock : highestBlock: $highestBlock");
		if($lowestBlock > 0 && $lowestBlock <= 0.5 && $lowestBlock <= $this->getMaxJumpHeight()){
			// STAIR jump					
			$this->motion->y = $this->gravity * 4;
			PureEntities::logOutput("$this: highestBlock($highestBlock)/lowestBlock($lowestBlock): found slab or stair!");
			return true;
		}elseif($lowestBlock > 0 && $lowestBlock <= $this->getMaxJumpHeight()){
			//Normal jump
			PureEntities::logOutput("$this: highestBlock($highestBlock)lowestBlock($lowestBlock): Normal Jump - set motion to gravity * 3.2!");
			$this->motion->y = $this->gravity * 3.2;
			return true;
		}elseif($lowestBlock > 0 && $lowestBlock > $this->getMaxJumpHeight()){
			PureEntities::logOutput("$this: highestBlock($highestBlock)lowestBlock($lowestBlock): cannot pass through the upper blocks!");
		}else{
			PureEntities::logOutput("$this: highestBlock($highestBlock)lowestBlock($lowestBlock): no need to jump. Block can be passed!");
		}
		return false;

	}


	/**
	 * Finds the next random location starting from current x/y/z and sets it as base target
	 */
	public function findRandomLocation(){


		PureEntities::logOutput("$this(findRandomLocation): entering");
		$x = mt_rand(-10, 10) + $this->x;
		$z = mt_rand(-10, 10) + $this->z;
		$this->moveTime = mt_rand(60, 120);

		// set a real y coordinate ...
		$y = $this->findTargetFloor($x, $z);
		$targetFloor = new Vector3($x, $y, $z);

		return $targetFloor;
	}


	/**
	 * Checks the given X and Z location to find a close air block.
	 * If one cannot be found within 3 blocks above, or below the
	 * current Y location, then the current Y location is returned.
	 *
	 * @param float|int $x
	 * @param float|int $z
	 * @return float|int
	 */
	public function findTargetFloor($x, $z){
		if($this->level->getBlock(new Vector3($x, $this->y, $z))->getId() === Block::AIR){
			return $this->y;
		}
		//Check from current y up for air.
		for($yScan = 1; $yScan < 3; $yScan++){
			if($this->level->getBlock(new Vector3($x, $this->y + $yScan, $z))->getId() === Block::AIR){
				return $this->y + $yScan;
			}
		}

		for($yScan = -1; $yScan > -3; $yScan--){
			if($this->level->getBlock(new Vector3($x, $this->y + $yScan, $z))->getId() === Block::AIR){
				return $this->y + $yScan;
			}
		}
		return $this->y;
	}


}
