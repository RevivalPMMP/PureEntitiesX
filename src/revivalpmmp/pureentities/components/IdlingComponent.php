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

namespace revivalpmmp\pureentities\components;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\utils\TickCounter;

/**
 * Class IdlingComponent
 *
 * This component class is implemented in BaseEntity to have support for idling entities. This class encapsulates all
 * the stuff we need to deal with idling entities.
 *
 * @package revivalpmmp\pureentities\components
 */
class IdlingComponent{

	protected $idling = false;
	/**
	 * @var TickCounter
	 */
	private $idlingTickCounter;

	/**
	 * @var TickCounter
	 */
	private $idlingCounter;

	/**
	 * @var BaseEntity
	 */
	private $baseEntity;

	/**
	 * @var int
	 */
	private $lastIdleStatus = 0;

	/**
	 * Defines the time that has to expire at least between two idle statuses
	 * @var int
	 */
	private $idleTimeBetween = 0;
	/**
	 * Defines min idle in ticks
	 * @var int
	 */
	private $idleMin = 0;

	/**
	 * Defines max idle in ticks
	 * @var int
	 */
	private $idleMax = 0;

	/**
	 * Chance for an entity to idle
	 * @var int
	 */
	private $idleChance = 0;

	public function __construct(BaseEntity $baseEntity){
		$this->baseEntity = $baseEntity;
		$pluginConfig = PluginConfiguration::getInstance();
		$this->idleChance = $pluginConfig->getIdleChance();
		$this->idleTimeBetween = $pluginConfig->getIdleTimeBetween();
		$this->idleMin = $pluginConfig->getIdleMin();
		$this->idleMax = $pluginConfig->getIdleMax();

		// prevent crash while save/restore NBT
		$this->idlingTickCounter = new TickCounter(0);
		$this->idlingCounter = new TickCounter(0);
	}

	/**
	 * Checks if idling is an option. If so, it sets the entity to idling mode for a random amount of time.
	 * While idling the entity just moves yaw and pitch.
	 * @return bool when the entity was set to idle mode
	 */
	public function checkAndSetIdling() : bool{
		$setToIdle = false;
		if(!$this->idling and // of course it should not idle currently
			($this->baseEntity instanceof IntfCanBreed and $this->baseEntity->getBreedingComponent()->getInLove() <= 0) and // do not rest while in love!
			!$this->baseEntity->getBaseTarget() instanceof Player and // chasing a player? no idle
			!$this->baseEntity->getBaseTarget() instanceof Creature and // chasing a creature? no idle!
			$this->isLastIdleLongEnough() // we do not want idling too often
		){
			if(mt_rand(0, 100) <= $this->idleChance){ // with a chance of x percent the entity starts to idle
				$this->idling = true;
				$this->idlingTickCounter = new TickCounter(mt_rand($this->idleMin, $this->idleMax)); // idle x and x ticks
				$this->baseEntity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IDLING, true);
				$this->idlingCounter = new TickCounter(50); // do some silly stuff every 50 ticks (moving yaw/pitch)
				$this->lastIdleStatus = time();

				$setToIdle = true;
			}
		}
		return $setToIdle;
	}

	/**
	 * Checks if last idle status is long enough. We don't want idle too often ;)
	 *
	 * @return bool
	 */
	public function isLastIdleLongEnough() : bool{
		return $this->lastIdleStatus == 0 || ($this->lastIdleStatus + $this->idleTimeBetween) < time();
	}

	/**
	 * Tries to stop the idling status of the entity. It should also be called each tick when the
	 * entity idles to unset idle status in time. Additionally we can force to stop the idle mode (in case
	 * of getting attacked by someone e.g.)
	 *
	 * @param int  $tickDiff
	 * @param bool $immediately
	 * @return bool true when the entity idle status was set to false
	 */
	public function stopIdling(int $tickDiff = 1, bool $immediately = false) : bool{
		$wokeUp = false;
		if($this->idling and ($this->idlingTickCounter->isTicksExpired($tickDiff) or $immediately)){
			$this->idling = false;
			$this->baseEntity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IDLING, false);
			$wokeUp = true;
		}else if(!$this->idling){ // should never happen!
			$wokeUp = true;
		}
		return $wokeUp;
	}

	/**
	 * Do just some idle stuff like nodding head or so
	 * @param $tickDiff
	 */
	public function doSomeIdleStuff(int $tickDiff = 1){
		if($this->idlingCounter->isTicksExpired($tickDiff)){
			// pitch: up and down, yaw: rotation around the y-axis

			// a little about yaw/pitch: http://greyminecraftcoder.blogspot.de/2015/07/entity-rotations-and-animation.html
			$yaw = $this->baseEntity->yaw;
			$pitch = $this->baseEntity->pitch;

			// rotate the entity: 0 degrees is south and increases clockwise
			$yaw = mt_rand(0, 1) ? $yaw + mt_rand(15, 45) : $yaw - mt_rand(15, 45);
			if($yaw > 360){
				$yaw = 360;
			}else if($yaw < 0){
				$yaw = 0;
			}


			// 0 degrees is horizontal, -90 is up, 90 is down. but 90 degrees looks very silly - so 60 degrees is
			// completely ok
			$pitch = mt_rand(0, 1) ? $pitch + mt_rand(10, 20) : $pitch - mt_rand(10, 20);
			if($pitch > 60){
				$pitch = 60;
			}else if($pitch < -60){
				$pitch = -60;
			}

			$this->baseEntity->setRotation($yaw, $pitch);
			if($this->baseEntity->getMotion()->x != 0){
				$this->baseEntity->getMotion()->x = 0;
			}
			if($this->baseEntity->getMotion()->z != 0){
				$this->baseEntity->getMotion()->z = 0;
			}
			$this->baseEntity->updateMovement();
		}
	}


	/**
	 * Returns if the entity is currently idling
	 * @return bool
	 */
	public function isIdling(){
		return $this->idling;
	}

	/**
	 * Loads the data for this component from entity's nbt
	 */
	public function loadFromNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			$namedTag = $this->baseEntity->namedtag;
			if($namedTag->hasTag(NBTConst::NBT_KEY_IDLE_SETTINGS)){
				$nbt = $namedTag->getCompoundTag(NBTConst::NBT_KEY_IDLE_SETTINGS);
				/**
				 * @var CompoundTag $nbt ;
				 */
				$this->idling = $nbt->getInt(NBTConst::NBT_KEY_IDLING, 1, true);
				if($this->idling){
					$this->idlingCounter = new TickCounter($nbt->getInt(NBTConst::NBT_KEY_MAX_IDLING_COUNTER, 0, true));
					$this->idlingCounter->setCurrentCounter($nbt->getInt(NBTConst::NBT_KEY_IDLING_COUNTER, 0, true));
					$this->idlingTickCounter = new TickCounter($nbt->getInt(NBTConst::NBT_KEY_MAX_IDLING_TICK_COUNTER, 0, true));
					$this->idlingTickCounter->setCurrentCounter($nbt->getInt(NBTConst::NBT_KEY_IDLING_TICK_COUNTER, 0, true));
				}
				$this->lastIdleStatus = $nbt->getInt(NBTConst::NBT_KEY_LAST_IDLE_STATUS, 0, true);
				PureEntities::logOutput($this->baseEntity . ": Idling properties set: [idling:" . $this->idling . "] [lastIdleStatus:" . $this->lastIdleStatus . "]");
			}else{
				PureEntities::logOutput($this->baseEntity . ": no idling properties found in NBT. Do not restore idling status leave default.");
			}
		}
	}

	/**
	 * Stores local data to NBT
	 */
	public function saveNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			$entityTag = $this->baseEntity->namedtag;
			$idleCompound = new CompoundTag(NBTConst::NBT_KEY_IDLE_SETTINGS);
			if($this->idling){
				$idleCompound->setInt(NBTConst::NBT_KEY_IDLING, $this->idling);
				$idleCompound->setInt(NBTConst::NBT_KEY_IDLING_COUNTER, $this->idlingCounter->getCurrentCounter());
				$idleCompound->setInt(NBTConst::NBT_KEY_MAX_IDLING_COUNTER, $this->idlingCounter->getMaxCounter());
				$idleCompound->setInt(NBTConst::NBT_KEY_IDLING_TICK_COUNTER, $this->idlingTickCounter->getCurrentCounter());
				$idleCompound->setInt(NBTConst::NBT_KEY_MAX_IDLING_TICK_COUNTER, $this->idlingTickCounter->getMaxCounter());
				$idleCompound->setInt(NBTConst::NBT_KEY_LAST_IDLE_STATUS, $this->lastIdleStatus);
			}else{
				$idleCompound->getInt(NBTConst::NBT_KEY_IDLING, $this->idling);
				$idleCompound->getInt(NBTConst::NBT_KEY_LAST_IDLE_STATUS, $this->lastIdleStatus);
			}
			$entityTag->setTag($idleCompound);
		}
	}

}