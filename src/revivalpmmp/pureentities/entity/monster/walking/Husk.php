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

use pocketmine\entity\Effect;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Ageable;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class Husk extends WalkingMonster implements Ageable{
	const NETWORK_ID = Data::NETWORK_IDS["husk"];

	public function initEntity(){
		parent::initEntity();
		$this->width = Data::WIDTHS[self::NETWORK_ID];
		$this->height = Data::HEIGHTS[self::NETWORK_ID];
		$this->speed = 1.1;

		if($this->getDataFlag(self::DATA_FLAG_BABY, 0) === null){
			$this->setDataFlag(self::DATA_FLAG_BABY, self::DATA_TYPE_BYTE, 0);
		}
		$this->setDamage([0, 3, 4, 6]);
	}

	public function getName() : string{
		return "Husk";
	}

	public function isBaby() : bool{
		return $this->getDataFlag(self::DATA_FLAG_BABY, 0);
	}

	public function setHealth(float $amount){
		parent::setHealth($amount);

		if($this->isAlive()){
			if(15 < $this->getHealth()){
				$this->setDamage([0, 2, 3, 4]);
			}else if(10 < $this->getHealth()){
				$this->setDamage([0, 3, 4, 6]);
			}else if(5 < $this->getHealth()){
				$this->setDamage([0, 3, 5, 7]);
			}else{
				$this->setDamage([0, 4, 6, 9]);
			}
		}
	}

	/**
	 * Attack player
	 *
	 * @param Entity $player
	 */
	public function attackEntity(Entity $player){
		if($this->attackDelay > 10 && $this->distanceSquared($player) < 2){
			$this->attackDelay = 0;

			$ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK,
				MobDamageCalculator::calculateFinalDamage($player, $this->getDamage()));
			$player->attack($ev);
			$effect = Effect::getEffect(17)->setDuration(1800)->setAmplifier(1);
			$effect->applyEffect($player);

			$this->checkTamedMobsAttack($player);
		}
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
		// babies spawn 12 exp - not for now - as it isn't implemented yet
		return 5;
	}

}
