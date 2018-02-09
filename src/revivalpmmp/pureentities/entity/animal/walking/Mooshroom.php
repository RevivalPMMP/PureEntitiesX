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

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\components\BreedingComponent;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfShearable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\traits\Breedable;
use revivalpmmp\pureentities\traits\Feedable;
use revivalpmmp\pureentities\traits\Shearable;

class Mooshroom extends WalkingAnimal implements IntfCanBreed, IntfCanInteract, IntfShearable{
	use Shearable, Breedable, Feedable;
	const NETWORK_ID = Data::NETWORK_IDS["mooshroom"];

	public function initEntity(){
		parent::initEntity();
		$this->width = Data::WIDTHS[self::NETWORK_ID];
		$this->height = Data::HEIGHTS[self::NETWORK_ID];
		$this->feedableItems = array(Item::WHEAT);
		$this->breedableClass = new BreedingComponent($this);
		$this->breedableClass->init();
		$this->maxShearDrops = 5;
		$this->shearItems = Item::RED_MUSHROOM;
	}

	public function getName() : string{
		return "Mooshroom";
	}

	public function loadNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::loadNBT();
			if(($sheared = $this->namedtag->getByte(NBTConst::NBT_KEY_SHEARED, NBTConst::NBT_INVALID_BYTE) != NBTConst::NBT_INVALID_BYTE)){
				$this->sheared = boolval($sheared);
			}
		}
	}

	public function saveNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::saveNBT();
			$this->breedableClass->saveNBT();
			$this->namedtag->setByte(NBTConst::NBT_KEY_SHEARED, $this->isSheared() ? 0 : 1);
		}
	}

	public function getDrops() : array{
		$drops = [];
		if($this->isLootDropAllowed()){
			array_push($drops, Item::get(Item::LEATHER, 0, mt_rand(0, 2)));
			if($this->isOnFire()){
				array_push($drops, Item::get(Item::COOKED_BEEF, 0, mt_rand(1, 3)));
			}else{
				array_push($drops, Item::get(Item::RAW_BEEF, 0, mt_rand(1, 3)));
			}
		}
		return $drops;
	}

	public function getMaxHealth() : int{
		return 10;
	}

	public function getXpDropAmount() : int{
		if($this->getBreedingComponent()->isBaby()){
			return mt_rand(1, 7);
		}
		return mt_rand(1, 3);
	}

}
