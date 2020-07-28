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

namespace revivalpmmp\pureentities\entity\monster\walking;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Explosive;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\item\Item;
use pocketmine\level\Explosion;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use revivalpmmp\pureentities\PureEntities;

class Creeper extends WalkingMonster implements Explosive{
	const NETWORK_ID = Data::NETWORK_IDS["creeper"];
	public const TAG_POWERED = "powered";
	public const TAG_IGNITED = "ignited";
	public const TAG_FUSE = "fuse";
	public const TAG_EXPLOSION_RADIUS = "explosionRadius";

	private $explodeBlocks = false;

	public function initEntity() : void{
		parent::initEntity();
		$this->speed = 0.9;
		$this->explodeBlocks = PureEntities::getInstance()->getConfig()->getNested("creeper.block-breaking-explosion", false);
	}

	public function getName() : string{
		return "Creeper";
	}

	public function explode(){
		$ev = new ExplosionPrimeEvent($this, 3);
		$ev->call();
		if(!$ev->isCancelled()){
			if($this->isPowered()) $ev->setForce($ev->getForce() * 2);
			$explosion = new Explosion($this, $ev->getForce(), $this);
			$ev->setBlockBreaking($this->explodeBlocks); // this is configuration!
			if($ev->isBlockBreaking()){
				$explosion->explodeA();
			}
			$explosion->explodeB();
		}
		$this->close();
	}

	public function isIgnited() : bool{
		return ($this->getGenericFlag(self::DATA_FLAG_IGNITED) || boolval($this->namedtag->getByte(self::TAG_IGNITED, 0)));
	}

	public function resetFuse() : void{
		$this->namedtag->setShort(self::TAG_FUSE, 30);
	}

	public function getFuse() : int{
		return $this->namedtag->getShort(self::TAG_FUSE, 30);
	}

	public function setFuse(int $fuse) : void{
		$this->namedtag->setShort(self::TAG_FUSE, $fuse);
	}

	public function onUpdate(int $currentTick) : bool{
		$tickDiff = $currentTick - $this->lastUpdate;
		if($this->getBaseTarget() !== null){
			$x = $this->getBaseTarget()->x - $this->x;
			$y = $this->getBaseTarget()->y - $this->y;
			$z = $this->getBaseTarget()->z - $this->z;
			$diff = abs($x) + abs($z);
			if($this->isIgnited()){
				if($this->getBaseTarget()->distance($this) >= $this->getExplosionRadius()){
					$this->setMovement(true);
					$this->setIgnited(false);
				}else{
					$this->setMovement(false);
					$fuse = $this->getFuse() - $tickDiff;
					$this->setFuse($fuse);
					if($fuse <= 0){
						$this->resetFuse();
						$this->explode();
					}
				}
			}else{
				if($this->getBaseTarget()->distance($this) <= 3 && $this->getBaseTarget() instanceof Creature){
					$this->setMovement(false);
					$this->setIgnited(true);
				}else{
					$this->setMovement(true);
				}
			}
			if($diff > 0){
				$this->motion->x = $this->getSpeed() * 0.15 * ($x / $diff);
				$this->motion->z = $this->getSpeed() * 0.15 * ($z / $diff);
				$this->yaw = rad2deg(-atan2($x / $diff, $z / $diff));
			}
			$this->pitch = $y === 0 ? 0 : rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
		}elseif($this->isIgnited()){ // using flint and steel manual ignition
			$this->setMovement(false);
			$fuse = $this->getFuse() - $tickDiff;
			$this->setFuse($fuse);
			if($fuse <= 0){
				$this->resetFuse();
				$this->explode();
			}
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

	public function getExplosionRadius() : int{
		return $this->namedtag->getByte(self::TAG_EXPLOSION_RADIUS, 7);
	}

	public function setIgnited(bool $ignited) : void{
		if($ignited) $this->getLevel()->broadcastLevelEvent($this, LevelEventPacket::EVENT_SOUND_IGNITE);
		$this->resetFuse();
		$this->namedtag->setByte(self::TAG_IGNITED, intval($ignited));
		$this->setGenericFlag(self::DATA_FLAG_IGNITED, $ignited);
	}

	public function isPowered() : bool{
		return ($this->getGenericFlag(self::DATA_FLAG_POWERED) || boolval($this->namedtag->getByte(self::TAG_POWERED, 0)));
	}
}
