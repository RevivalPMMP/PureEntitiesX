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

namespace revivalpmmp\pureentities\entity\animal\flying;


use pocketmine\entity\Creature;
use pocketmine\item\Item;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\animal\FlyingAnimal;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\traits\Tameable;

class Parrot extends FlyingAnimal implements IntfTameable, IntfCanInteract{
	use Tameable;

	const NETWORK_ID = Data::NETWORK_IDS["parrot"];
	private $birdType; // 0 = red, 1 = blue, 2 = green, 3 = cyan, 4 = silver

	public function initEntity() : void{
		parent::initEntity();
		$this->fireProof = false;
		$this->tameFoods = array(
			Item::SEEDS,
			Item::BEETROOT_SEEDS,
			Item::MELON_SEEDS,
			Item::PUMPKIN_SEEDS,
			Item::WHEAT_SEEDS
		);
		if(empty($this->birdType)){
			$this->setBirdType($this->getRandomBirdType());
		}

		if($this->isTamed()){
			$this->mapOwner();
			if($this->owner === null){
				PureEntities::logOutput("$this: is tamed but player not online. Cannot set tamed owner. Will be set when player logs in ..", PureEntities::NORM);
			}
		}
	}

	public function loadNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::loadNBT();
			if($this->namedtag->hasTag(NBTConst::NBT_KEY_BIRDTYPE)){
				$birdType = $this->namedtag->getByte(NBTConst::NBT_KEY_BIRDTYPE, $this->getRandomBirdType(), true);
				$this->setBirdType($birdType);
			}
		}
	}

	public function saveNBT() : void{
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::saveNBT();
			$this->namedtag->setByte(NBTConst::NBT_KEY_BIRDTYPE, $this->birdType, true);
		}
	}

	public function getName() : string{
		return "Parrot";
	}

	public function targetOption(Creature $creature, float $distance) : bool{
		return false;
	}

	public function getDrops() : array{
		return [Item::get(Item::FEATHER, 0, mt_rand(1, 2))];
	}

	public function getMaxHealth() : int{
		return 6;
	}

	public function updateXpDropAmount() : void{
		$this->xpDropAmount = mt_rand(1, 3);
	}

	public function getRandomBirdType() : int{
		return mt_rand(0, 4);
	}

	public function setBirdType(int $type){
		$this->birdType = $type;
		$this->getDataPropertyManager()->setPropertyValue(self::DATA_VARIANT, self::DATA_TYPE_INT, $type);
	}

	public function getBirdType(){
		return $this->birdType;
	}
}