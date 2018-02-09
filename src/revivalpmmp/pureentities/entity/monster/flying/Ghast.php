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

use pocketmine\item\Item;
use revivalpmmp\pureentities\entity\monster\FlyingMonster;
use revivalpmmp\pureentities\entity\projectile\LargeFireball;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\level\Location;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;

class Ghast extends FlyingMonster implements ProjectileSource{
	const NETWORK_ID = Data::NETWORK_IDS["ghast"];

	public function initEntity(){
		parent::initEntity();
		$this->width = Data::WIDTHS[self::NETWORK_ID];
		$this->height = Data::HEIGHTS[self::NETWORK_ID];
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

			$motion = new Vector3(
				-sin(rad2deg($yaw)) * cos(rad2deg($pitch)) * $f * $f,
				-sin(rad2deg($pitch)) * $f * $f,
				cos(rad2deg($yaw)) * cos(rad2deg($pitch)) * $f * $f
			);
			$nbt = Entity::createBaseNBT($pos, $motion, $yaw, $pitch);
			$fireball = Entity::createEntity("LargeFireball", $this->level, $nbt);
			if(!($fireball instanceof LargeFireball)){
				return;
			}
			$fireball->setExplode(true);

			$this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($fireball));
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

	public function getXpDropAmount() : int{
		return 5;
	}


}
