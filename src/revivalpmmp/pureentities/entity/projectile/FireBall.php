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

namespace revivalpmmp\pureentities\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\level\Level;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\Player;

abstract class FireBall extends Projectile{

	protected $damage = 4;

	protected $drag = 0.01;
	protected $gravity = 0.05;

	protected $isCritical;
	protected $canExplode = false;

	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null, bool $critical = false){
		$this->isCritical = $critical;
		parent::__construct($level, $nbt, $shootingEntity);
	}

	public function isExplode() : bool{
		return $this->canExplode;
	}

	public function setExplode(bool $bool){
		$this->canExplode = $bool;
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->isClosed()){
			return false;
		}

		$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);

		if(!$this->isCollided and $this->isCritical){
			$this->level->addParticle(new CriticalParticle($this->add(
				$this->width / 2 + mt_rand(-100, 100) / 500,
				$this->height / 2 + mt_rand(-100, 100) / 500,
				$this->width / 2 + mt_rand(-100, 100) / 500)));
		}elseif($this->onGround){
			$this->isCritical = false;
		}

		if($this->ticksLived > 1200 or $this->isCollided){
			if($this->isCollided and $this->canExplode){
				$ev = $ev = new ExplosionPrimeEvent($this, 2.8);
				$ev->call();
				if(!$ev->isCancelled() && $this->getLevel() !== null){
					$explosion = new Explosion($this, $ev->getForce(), $this->getOwningEntity());
					if($ev->isBlockBreaking()){
						$explosion->explodeA();
					}
					$explosion->explodeB();
				}
			}
			$this->kill();
			$hasUpdate = true;
		}

		$this->timings->stopTiming();

		return $hasUpdate;
	}

	public function spawnTo(Player $player) : void{
		$pk = new AddActorPacket();
		$pk->type = static::NETWORK_ID;
		$pk->entityRuntimeId = $this->getId();
		$pk->position = $this->asVector3();
		$pk->motion = $this->getMotion();
		$pk->metadata = $this->propertyManager->getAll();
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}

}
