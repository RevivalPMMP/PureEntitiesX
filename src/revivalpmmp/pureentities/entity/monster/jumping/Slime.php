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

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\monster\JumpingMonster;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class Slime extends JumpingMonster{
	const NETWORK_ID = Data::NETWORK_IDS["slime"];

	private $cubeSize = -1; // 0 = Tiny, 1 = Small, 2 = Big

	public function __construct(Level $level, CompoundTag $nbt){
		$this->loadFromNBT($nbt);
		if($this->cubeSize == -1){
			$this->cubeSize = self::getRandomSlimeSize();
		}

		$this->width = 0.51;
		$this->height = 0.51;
		parent::__construct($level, $nbt);
		$this->setScale($this->cubeSize);
	}

	public function initEntity() : void{
		parent::initEntity();
		$this->speed = 0.8;

		$this->setDamage([0, 2, 2, 3]);
	}

	public function saveNBT() : void{
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::saveNBT();
			$this->namedtag->setByte(NBTConst::NBT_KEY_CUBE_SIZE, $this->cubeSize, true);
		}
	}

	public function loadFromNBT(CompoundTag $nbt){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			if($nbt->hasTag(NBTConst::NBT_KEY_CUBE_SIZE)){
				$cubeSize = $nbt->getByte(NBTConst::NBT_KEY_CUBE_SIZE, self::getRandomSlimeSize());
				$this->cubeSize = $cubeSize;
			}
		}
	}

	public function getName() : string{
		return "Slime";
	}

	public static function getRandomSlimeSize() : int{
		($size = mt_rand(1, 3)) !== 3 ?: $size = 4;
		return $size;
	}

	/**
	 * Attack a player
	 *
	 * @param Entity $player
	 */
	public function attackEntity(Entity $player){
		if($this->attackDelay > 10 && $this->distanceSquared($player) < 2){
			$this->attackDelay = 0;

			$ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK,
				MobDamageCalculator::calculateFinalDamage($player, $this->getDamage()));
			$player->attack($ev);

			$this->checkTamedMobsAttack($player);
		}
	}

	public function targetOption(Creature $creature, float $distance) : bool{
		if($creature instanceof Player){
			return $creature->isAlive() && $distance <= 25;
		}
		return false;
	}

	public function getDrops() : array{
		if($this->isLootDropAllowed() and $this->cubeSize == 0){
			return [Item::get(Item::SLIMEBALL, 0, mt_rand(0, 2))];
		}else{
			return [];
		}
	}

	public function getMaxHealth() : int{
		return 4;
	}

	public function updateXpDropAmount() : void{
		if($this->cubeSize == 2){
			$this->xpDropAmount = 4;
		}else if($this->cubeSize == 1){
			$this->xpDropAmount = 2;
		}else{
			$this->xpDropAmount = 1;
		}
	}

}
