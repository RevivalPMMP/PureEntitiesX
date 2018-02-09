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

namespace revivalpmmp\pureentities\entity\monster\walking;

use revivalpmmp\pureentities\entity\monster\Monster;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\item\Item;
use pocketmine\level\Level;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class ZombieVillager extends WalkingMonster implements Monster{
	const NETWORK_ID = Data::NETWORK_IDS["zombie_villager"];

	public function initEntity(){
		parent::initEntity();
		$this->width = Data::WIDTHS[self::NETWORK_ID];
		$this->height = Data::HEIGHTS[self::NETWORK_ID];
		$this->speed = 1.1;

		$this->setDamage([0, 3, 4, 6]);
	}

	public function getName() : string{
		return "ZombieVillager";
	}

	/**
	 * Attack a player
	 *
	 * @param Entity $player
	 */
	public function attackEntity(Entity $player){
		if($this->attackDelay > 10 && $this->distanceSquared($player) < 1){
			$this->attackDelay = 0;
			$ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK,
				MobDamageCalculator::calculateFinalDamage($player, $this->getDamage()));
			$player->attack($ev);

			$this->checkTamedMobsAttack($player);
		}
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->isClosed() or $this->getLevel() == null) return false;
		Timings::$timerEntityBaseTick->startTiming();

		$hasUpdate = parent::entityBaseTick($tickDiff);

		// BaseEntity::entityBaseTick checks and can trigger despawn.  After calling it, we need to verify
		// that the entity is still valid for updates before performing any other tasks on it.
		if($this->isClosed() or !$this->isAlive()){
			Timings::$timerEntityBaseTick->stopTiming();
			return false;
		}
		$time = $this->getLevel()->getTime() % Level::TIME_FULL;
		if(
			!$this->isOnFire()
			&& ($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE)
		){
			$this->setOnFire(100);
		}

		Timings::$timerEntityBaseTick->stopTiming();
		return $hasUpdate;
	}

	public function getDrops() : array{
		$drops = [];
		if($this->isLootDropAllowed()){
			array_push($drops, Item::get(Item::ROTTEN_FLESH, 0, mt_rand(0, 2)));
			// 2.5 percent chance of dropping one of these items.
			if(mt_rand(1, 1000) % 25 == 0){
				switch(mt_rand(1, 3)){
					case 1:
						array_push($drops, Item::get(Item::CARROT, 0, 1));
						break;
					case 2:
						array_push($drops, Item::get(Item::POTATO, 0, 1));
						break;
					case 3:
						array_push($drops, Item::get(Item::IRON_INGOT, 0, 1));
						break;
				}
			}
		}
		return $drops;
	}

	public function getMaxHealth() : int{
		return 20;
	}

	public function getXpDropAmount() : int{
		// adult: 5, baby: 12
		return 5;
	}


}
