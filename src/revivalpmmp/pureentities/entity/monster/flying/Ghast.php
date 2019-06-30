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

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\entity\monster\FlyingMonster;
use revivalpmmp\pureentities\entity\projectile\LargeFireball;

class Ghast extends FlyingMonster implements ProjectileSource{
	const NETWORK_ID = Data::NETWORK_IDS["ghast"];

	public function __construct(Level $level, CompoundTag $nbt){
		$this->height = Data::HEIGHTS[self::NETWORK_ID];
		$this->width = Data::WIDTHS[self::NETWORK_ID];
		parent::__construct($level, $nbt);
	}

	public function initEntity() : void{
		parent::initEntity();
		$this->speed = 1.2;
		$this->fireProof = true;
		$this->setDamage([0, 0, 0, 0]);
	}

	public function getName() : string{
		return "Ghast";
	}

	public function targetOption(Creature $creature, float $distance) : bool{
		return (!($creature instanceof Player) || ($creature->isSurvival() && $creature->spawned)) && $creature->isAlive() && !$creature->isClosed() && $distance <= 10000;
	}

	public function attackEntity(Entity $player){
		if($this->attackDelay > 30 && mt_rand(1, 32) < 4 && $this->distance($player) <= 100){
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
			$fireball = new LargeFireball($this->level, $nbt, $this);

			$fireball->setExplode(true);

			$launch = new ProjectileLaunchEvent($fireball);
			$launch->call();
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
			return [Item::get(Item::GUNPOWDER, 0, mt_rand(0, 2))];
		}else{
			return [];
		}
	}

	public function getMaxHealth() : int{
		return 10;
	}

	public function updateXpDropAmount() : void{
		$this->xpDropAmount = 5;
	}


}
