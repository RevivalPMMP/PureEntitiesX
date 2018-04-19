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


namespace revivalpmmp\pureentities\traits;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\entity\animal\walking\Cow;
use revivalpmmp\pureentities\entity\animal\walking\Mooshroom;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\PureEntities;

trait Shearable{

	private $sheared = false;
	private $timeSheared = 0; // Used to determine how many ticks the Entity has been sheared.
	private $maxShearDrops = 1;
	private $shearItems;

	/**
	 * This needs to be overridden in each class to handle drop specifics.
	 *
	 * @param Player $player
	 * @return bool
	 */
	public function shear(Player $player) : bool{
		if($this->isSheared() or ($this instanceof IntfCanBreed and $this->getBreedingComponent()->isBaby())){
			return false;
		}else{
			$meta = ($this instanceof Sheep ? $this->color : 0);
			if($this->maxShearDrops <= 1){
				$dropCount = ($this->maxShearDrops == 1 ? 1 : 0);
			}else{
				$dropCount = mt_rand(1, $this->maxShearDrops);
			}
			if($dropCount != 0){
				$player->getLevel()->dropItem($this->asVector3(), Item::get($this->shearItems, $meta, $dropCount));
			}
			$this->setSheared(true);
			return true;
		}
	}

	public function isSheared() : bool{
		return $this->sheared;
	}

	public function setSheared(bool $sheared){
		$this->sheared = $sheared;
		$this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHEARED, $sheared);
		if($this instanceof Mooshroom and $sheared == true){

			/**
			 * @var Cow $newCow
			 */
			$newCow = PureEntities::create(Data::NETWORK_IDS["cow"], $this->asLocation());
			$loaded = false;
			while(!$loaded){
				$newCow->setPosition($this->asVector3());
				$newCow->setRotation($this->getYaw(), $this->getPitch());
				if($newCow->temporalVector !== null){
					$loaded = true;
				}
			}
			$this->close();
		}
	}
}