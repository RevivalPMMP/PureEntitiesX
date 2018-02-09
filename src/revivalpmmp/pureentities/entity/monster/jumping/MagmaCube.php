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

namespace revivalpmmp\pureentities\entity\monster\jumping;

use pocketmine\item\Item;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\monster\JumpingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class MagmaCube extends JumpingMonster{
	const NETWORK_ID = Data::NETWORK_IDS["magma_cube"];

	private $cubeSize = -1; // 0 = Tiny, 1 = Small, 2 = Big
	private $cubeDimensions = array(0.51, 1.02, 2.04);


	public function initEntity(){
		parent::initEntity();
		if($this->cubeSize == -1){
			$this->cubeSize = mt_rand(0, 2);
			$this->saveNBT();
		}

		$this->width = $this->cubeDimensions[$this->cubeSize];
		$this->height = $this->cubeDimensions[$this->cubeSize];
		$this->speed = 0.8;

		$this->fireProof = true;
		$this->setDamage([0, 3, 4, 6]);
	}

	public function saveNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::saveNBT();
			$this->namedtag->setByte(NBTConst::NBT_KEY_CUBE_SIZE, $this->cubeSize);
		}
	}

	public function loadFromNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::loadNBT();
			if(($cubeSize = $this->namedtag->getByte(NBTConst::NBT_KEY_CUBE_SIZE, NBTConst::NBT_INVALID_BYTE)) !== NBTConst::NBT_INVALID_BYTE){
				$this->cubeSize = $cubeSize;
			}
		}
	}

	public function getName() : string{
		return "MagmaCube";
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

	public function getDrops() : array{
		$drops = [];
		if($this->isLootDropAllowed()){
			switch(mt_rand(0, 1)){
				case 0:
					$drops[] = Item::get(Item::NETHERRACK, 0, 1);
					break;
				case 1:
					$drops[] = Item::get(Item::MAGMA_CREAM, 0, 1);
					break;
			}
		}
		return $drops;
	}

	public function getXpDropAmount() : int{
		// normally it would be set by small/medium/big sized - but as we have it not now - i'll make it more static
		if($this->cubeSize == 2){
			return 4;
		}else if($this->cubeSize == 1){
			return 2;
		}else{
			return 1;
		}
	}


}
