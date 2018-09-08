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

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfShearable;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\traits\Shearable;

class SnowGolem extends WalkingMonster implements ProjectileSource, IntfCanInteract, IntfShearable{
	use Shearable;
	const NETWORK_ID = Data::NETWORK_IDS["snow_golem"];

	public function initEntity() : void{
		parent::initEntity();

		$this->setFriendly(true);
		$this->setSheared($this->isSheared()); // set data from NBT
		$this->maxShearDrops = 0;
	}

	public function getName() : string{
		return "SnowGolem";
	}

	/**
	 * Attack the player
	 *
	 * @param Entity $player
	 */
	public function attackEntity(Entity $player){
		if($this->attackDelay > 23 && $this->distanceSquared($player) <= 55){
			$this->attackDelay = 0;

			$f = 1.2;
			$yaw = $this->yaw + mt_rand(-220, 220) / 10;
			$pitch = $this->pitch + mt_rand(-120, 120) / 10;
			$nbt = new CompoundTag("", [
				"Pos" => new ListTag("Pos", [
					new DoubleTag("", $this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5)),
					new DoubleTag("", $this->y + 1),
					new DoubleTag("", $this->z + (cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5))
				]),
				"Motion" => new ListTag("Motion", [
					new DoubleTag("", -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI)),
					new DoubleTag("", -sin($pitch / 180 * M_PI)),
					new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI))
				]),
				"Rotation" => new ListTag("Rotation", [
					new FloatTag("", $yaw),
					new FloatTag("", $pitch)
				]),
			]);

			/** @var Projectile $snowball */
			$snowball = Entity::createEntity("Snowball", $this->getLevel(), $nbt, $this);
			$snowball->setMotion($snowball->getMotion()->multiply($f));

			$this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($snowball));
			if($launch->isCancelled()){
				$snowball->kill();
			}else{
				$snowball->spawnToAll();
				$this->level->addSound(new LaunchSound($this), $this->getViewers());
			}

			$this->checkTamedMobsAttack($player);
		}
	}

	public function getDrops() : array{
		if($this->isLootDropAllowed()){
			return [Item::get(Item::SNOWBALL, 0, mt_rand(0, 15))];
		}else{
			return [];
		}
	}

	public function getMaxHealth() : int{
		return 4;
	}

	// ------------------------------------------------------------------------------------------------------------------------
	// functions for snowgolem
	// ------------------------------------------------------------------------------------------------------------------------


	/**
	 * This method is called when a player is looking at this entity. This
	 * method shows an interactive button or not
	 *
	 * @param Player $player the player to show a button eventually to
	 */
	public function showButton(Player $player){
		if($player->getInventory() != null){ // sometimes, we get null on getInventory?! F**k
			if($player->getInventory()->getItemInHand()->getId() === ItemIds::SHEARS && !$this->isSheared()){
				InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_SHEAR, $player);
				return;
			}
		}
		parent::showButton($player);
	}

	/**
	 * loads data from nbt and fills internal variables
	 */
	public function loadNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			if(($pumpkin = $this->namedtag->getByte(NBTConst::NBT_KEY_PUMPKIN, NBTConst::NBT_INVALID_BYTE)) !== NBTConst::NBT_INVALID_BYTE){
				$this->sheared = boolval($pumpkin);
			}
		}
	}

	/**
	 * Stores internal variables to NBT
	 */
	public function saveNBT() : void{
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::saveNBT();
			$this->namedtag->setInt(NBTConst::NBT_KEY_PUMPKIN, $this->sheared ? 0 : 1); // default: has pumpkin on his head (1 - pumpkin on head, 0 - pumpkin off!)
		}
	}


}
