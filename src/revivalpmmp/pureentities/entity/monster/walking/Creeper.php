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

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Explosive;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\item\Item;
use pocketmine\level\Explosion;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

class Creeper extends WalkingMonster implements Explosive{
	const NETWORK_ID = Data::NETWORK_IDS["creeper"];
	const DATA_POWERED = 19;

	private $bombTime = 0;

	private $explodeBlocks = false;

	private $powered = 0;

	public function initEntity() : void{
		parent::initEntity();
		$this->speed = 0.9;
		$this->explodeBlocks = (PureEntities::getInstance()->getConfig()->getNested("creeper.block-breaking-explosion", 0) == 0 ? false : true);
	}

	public function isPowered(){
		return $this->powered;
	}

	public function setPowered($value = true){
		$value ? $this->powered = 1 : $this->powered = 0;
		$this->getDataPropertyManager()->setPropertyValue(self::DATA_POWERED, self::DATA_TYPE_BYTE, $this->powered);
	}

	public function loadNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::loadNBT();
			if($this->namedtag->hasTag(NBTConst::NBT_KEY_POWERED)){
				$this->powered = $this->namedtag->getInt(NBTConst::NBT_KEY_POWERED, 0, true);
				$this->setPowered($this->powered);
			}
		}
	}

	public function saveNBT() : void{
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::saveNBT();
			$this->namedtag->setInt(NBTConst::NBT_KEY_POWERED, $this->powered, true);
		}
	}

	public function getName() : string{
		return "Creeper";
	}

	public function explode(){
		$this->server->getPluginManager()->callEvent($ev = new ExplosionPrimeEvent($this, 2.8));

		if(!$ev->isCancelled()){
			$explosion = new Explosion($this, $ev->getForce(), $this);
			$ev->setBlockBreaking($this->explodeBlocks); // this is configuration!
			if($ev->isBlockBreaking()){
				$explosion->explodeA();
			}
			$explosion->explodeB();
		}
		$this->kill();
	}

	public function onUpdate(int $currentTick) : bool{
		$tickDiff = $currentTick - $this->lastUpdate;

		if($this->getBaseTarget() !== null){
			$x = $this->getBaseTarget()->x - $this->x;
			$y = $this->getBaseTarget()->y - $this->y;
			$z = $this->getBaseTarget()->z - $this->z;

			$diff = abs($x) + abs($z);

			if($this->getBaseTarget() instanceof Creature && $this->getBaseTarget()->distanceSquared($this) <= 4.5){
				$this->bombTime += $tickDiff;
				if($this->bombTime >= 64 && $this->isAlive()){
					$this->explode();
					return false;
				}
			}else{
				$this->bombTime -= $tickDiff;
				if($this->bombTime < 0){
					$this->bombTime = 0;
				}
			}
			if($diff > 0){
				$this->motion->x = $this->getSpeed() * 0.15 * ($x / $diff);
				$this->motion->z = $this->getSpeed() * 0.15 * ($z / $diff);
				$this->yaw = rad2deg(-atan2($x / $diff, $z / $diff));
			}
			$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
		}

		return parent::onUpdate($currentTick);
	}

	public function attackEntity(Entity $player){
		// the creeper doesn't attack - it simply explodes
	}

	public function getDrops() : array{
		if($this->isLootDropAllowed()){
			return [Item::get(Item::GUNPOWDER, 0, mt_rand(0, 2))];
		}else{
			return [];
		}
	}

	public function getMaxHealth() : int{
		return 20;
	}

	public function updateXpDropAmount() : void{
		$this->xpDropAmount = 5;
	}


}
