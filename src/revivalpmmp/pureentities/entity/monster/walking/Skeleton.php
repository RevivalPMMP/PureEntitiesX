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

use pocketmine\block\Water;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\Player;
use revivalpmmp\pureentities\components\MobEquipment;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use revivalpmmp\pureentities\features\IntfCanEquip;
use revivalpmmp\pureentities\utils\ProjectileHelper;

// use pocketmine\event\Timings;

class Skeleton extends WalkingMonster implements IntfCanEquip, ProjectileSource{
	const NETWORK_ID = Data::NETWORK_IDS["skeleton"];

	/**
	 * @var MobEquipment
	 */
	protected $mobEquipment;

	protected $pickUpLoot = [];

	public function initEntity() : void{
		parent::initEntity();
		$this->attackDistance = 16;
		$this->mobEquipment = new MobEquipment($this);
		$this->mobEquipment->init();
		if($this->mobEquipment->getMainHand() === null){
			$this->equipDefaultMainHand();
		}
	}

	protected function sendSpawnPacket(Player $player) : void{
		parent::sendSpawnPacket($player);
		$this->mobEquipment->sendEquipmentUpdate($player);
	}

	public function getName() : string{
		return "Skeleton";
	}

	/**
	 * Attack the player
	 *
	 * @param Entity $player
	 */
	public function attackEntity(Entity $player){
		if($this->attackDelay > 30 && mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 55){
			$this->attackDelay = 0;

			$f = 1.2;
			$pos = $this->asPosition();
			$pos->y = $this->y + $this->getEyeHeight();
			$arrow = ProjectileHelper::createProjectile(self::ARROW, $pos, $player->add(0, $player->height / 2, 0));
			$arrow->setOwningEntity($this);
			$arrow->setMotion($this->getDirectionVector());
			$bow = $this->mobEquipment->getMainHand();
			$ev = new EntityShootBowEvent($this, $bow, $arrow, $f);
			$ev->call();
			ProjectileHelper::launchProjectile($ev->getProjectile());
		}
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->isClosed() or $this->getLevel() === null) return false;
		// Timings::$timerEntityBaseTick->startTiming();

		$hasUpdate = parent::entityBaseTick($tickDiff);

		$time = $this->getLevel() ? $this->getLevel()->getTime() % Level::TIME_FULL : Level::TIME_NIGHT;
		if(
			!$this->isOnFire() //if not already on fire
			&& ($time < Level::TIME_SUNSET || $time > Level::TIME_SUNRISE) // If time inferior of TIME_NIGHT and superior of TIME_SUNRISE
			&& !($this->getLevel()->getBlock($this) instanceof Water) // IF not in water
			&& $this->level->getBlockSkyLightAt((int) floor($this->x), (int) floor($this->y), (int) floor($this->z)) >= 14 //If is in the sun
			&& $this->getMobEquipment()->getHelmet() === null
		){
			$this->setOnFire(2);
		}

		// Timings::$timerEntityBaseTick->stopTiming();
		return $hasUpdate;
	}

	public function getDrops() : array{
		$drops = [];
		if($this->isLootDropAllowed()){
			array_push($drops, Item::get(Item::ARROW, 0, mt_rand(0, 2)));
			array_push($drops, Item::get(Item::BONE, 0, mt_rand(0, 2)));
		}
		return $drops;
	}

	public function getMaxHealth() : int{
		return 20;
	}

	public function updateXpDropAmount() : void{
		$this->xpDropAmount = 5;
	}

	public function getMobEquipment() : MobEquipment{
		return $this->mobEquipment;
	}

	public function getPickupLoot() : array{
		return $this->pickUpLoot;
	}

	protected function equipDefaultMainHand() : void{
		$this->mobEquipment->setMainHand(ItemFactory::get(Item::BOW));
	}
}
