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

use pocketmine\item\Item;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\features\IntfShearable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\traits\Shearable;

class Mooshroom extends Cow implements IntfShearable{

	use Shearable;

	const NETWORK_ID = Data::NETWORK_IDS["mooshroom"];

	public function initEntity() : void{
		parent::initEntity();
		$this->maxShearDrops = 5;
		$this->shearItems = Item::RED_MUSHROOM;
	}

	public function getName() : string{
		return "Mooshroom";
	}

	public function loadNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::loadNBT();

			if($this->namedtag->hasTag(NBTConst::NBT_KEY_SHEARED)){

				$sheared = $this->namedtag->getByte(NBTConst::NBT_KEY_SHEARED, false, true);
				$this->sheared = (bool) $sheared;
			}
		}
	}

	public function saveNBT() : void{
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::saveNBT();
			$this->getBreedingComponent()->saveNBT();
			$this->namedtag->setByte(NBTConst::NBT_KEY_SHEARED, $this->isSheared() ? 0 : 1, true);
		}
	}

}
