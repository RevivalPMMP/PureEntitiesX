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

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Dirt;
use pocketmine\block\Grass;
use pocketmine\block\TallGrass;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use revivalpmmp\pureentities\components\BreedingComponent;
use revivalpmmp\pureentities\data\Color;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfCanPanic;
use revivalpmmp\pureentities\features\IntfShearable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\traits\Breedable;
use revivalpmmp\pureentities\traits\CanPanic;
use revivalpmmp\pureentities\traits\Feedable;
use revivalpmmp\pureentities\traits\Shearable;

class Sheep extends WalkingAnimal implements IntfCanBreed, IntfCanInteract, IntfShearable, IntfCanPanic{
	use Breedable, CanPanic, Feedable, Shearable;
	const NETWORK_ID = Data::NETWORK_IDS["sheep"];

	const DATA_COLOR_INFO = 16;


	/**
	 * @var int
	 */
	private $color = Color::WHITE; // default: white

	public function getName() : string{
		return "Sheep";
	}

	public static function getRandomColor() : int{
		$rand = "";
		$rand .= str_repeat(Color::WHITE . " ", 818);
		$rand .= str_repeat(Color::GRAY . " ", 50);
		$rand .= str_repeat(Color::LIGHT_GRAY . " ", 50);
		$rand .= str_repeat(Color::BROWN . " ", 30);
		$rand .= str_repeat(Color::BLACK . " ", 50);
		$rand .= str_repeat(Color::PINK . " ", 2);
		$arr = explode(" ", $rand);
		return intval($arr[mt_rand(0, count($arr) - 1)]);
	}

	public function initEntity() : void{
		parent::initEntity();
		$this->breedableClass = new BreedingComponent($this);
		$this->breedableClass->init();
		$this->feedableItems = array(Item::WHEAT);
		$this->maxShearDrops = 3;
		$this->shearItems = Item::WOOL;
		$this->setColor($this->getColor());
		$this->setSheared($this->isSheared());
	}

	public function checkTarget(bool $checkSkip = true){
		if(($checkSkip and $this->isCheckTargetAllowedBySkip()) or !$checkSkip){
			if($this->isSheared()){
				$currentBlock = $this->getCurrentBlock();
				if($currentBlock !== null and
					($currentBlock instanceof Grass or $currentBlock instanceof TallGrass or strcmp($currentBlock->getName(), "Double Tallgrass") == 0)
				){ // only grass blocks are eatable by sheep)
					$this->blockOfInterestReached($currentBlock);
				}
			}
			// and of course, we should call the parent check target method (which has to call breeding methods)
			parent::checkTarget(false);
		}
	}

	public function getDrops() : array{
		$drops = [];
		if($this->isLootDropAllowed() and !$this->isSheared() && !$this->getBreedingComponent()->isBaby()){
			// http://minecraft.gamepedia.com/Sheep - drop 1 wool when not a baby and died
			array_push($drops, Item::get(Item::WOOL, self::getColor(), 1));
			if($this->isOnFire()){
				array_push($drops, Item::get(Item::MUTTON_COOKED, 0, mt_rand(1, 2)));
			}else{
				array_push($drops, Item::get(Item::MUTTON_RAW, 0, mt_rand(1, 2)));
			}
		}
		return $drops;
	}

	/**
	 * The initEntity method of parent uses this function to get the max health and set in NBT
	 *
	 * @return int
	 */
	public function getMaxHealth() : int{
		return 8;
	}

	/**
	 * loads data from nbt and fills internal variables
	 */
	public function loadNBT(){
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			if($this->namedtag->hasTag(NBTConst::NBT_KEY_SHEARED)){
				$sheared = $this->namedtag->getByte(NBTConst::NBT_KEY_SHEARED, false, true);
				$this->sheared = (bool) $sheared;
			}

			if($this->namedtag->hasTag(NBTConst::NBT_KEY_COLOR)){
				$color = $this->namedtag->getByte(NBTConst::NBT_KEY_COLOR, self::getRandomColor());
				$this->color = (int) $color;
			} else {
				$this->color = self::getRandomColor();
			}
		}
	}

	/**
	 * Stores internal variables to NBT
	 */
	public function saveNBT() : void{
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::saveNBT();
			$this->namedtag->setTag(new ByteTag(NBTConst::NBT_KEY_SHEARED, $this->sheared));
			$this->namedtag->setTag(new ByteTag(NBTConst::NBT_KEY_COLOR, $this->color));
		}
		$this->breedableClass->saveNBT();
	}

	// ------------------------------------------------------------
	// very sheep specific functions
	// ------------------------------------------------------------


	/**
	 * Gets the color of the sheep
	 *
	 * @return int
	 */
	public function getColor() : int{
		return $this->color;
	}

	/**
	 * Set the color of the sheep
	 *
	 * @param int $color
	 */
	public function setColor(int $color){
		$this->color = $color;
		$this->getDataPropertyManager()->setPropertyValue(self::DATA_COLOUR, self::DATA_TYPE_BYTE, $color);
	}

	/**
	 * When a sheep is sheared, it tries to eat grass. This method signalizes, that the entity reached
	 * a grass block or something that can be eaten.
	 *
	 * @param Block $block
	 */
	protected function blockOfInterestReached($block){
		PureEntities::logOutput("$this(blockOfInterestReached): $block");
		$this->stayTime = 100; // let this entity stay still
		// play eat grass animation but only when there are players near ...
		foreach($this->getLevel()->getPlayers() as $player){ // don't know if this is the correct one :/
			if($player->distance($this) <= 49){
				$pk = new ActorEventPacket();
				$pk->entityRuntimeId = $this->getId();
				$pk->event = ActorEventPacket::EAT_GRASS_ANIMATION;
				$player->dataPacket($pk);
			}
		}
		// after the eat grass has been played, we reset the block through air
		if($block->getId() == Block::GRASS or $block->getId() == Block::TALL_GRASS){ // grass blocks are replaced by dirt blocks ...
			$this->getLevel()->setBlock($block, new Dirt());
		}else{
			$this->getLevel()->setBlock($block, new Air());
		}
		// this sheep is not sheared anymore ... ;)
		$this->setSheared(false);
		// reset base target. otherwise the entity will not move anymore :D
		$this->setBaseTarget(null);
		$this->checkTarget(false); // find a new target to move to ...
	}

	public function updateXpDropAmount() : void{
		if($this->getBreedingComponent()->checkInLove()){
			$this->xpDropAmount = mt_rand(1, 7);
		}
		if(!$this->getBreedingComponent()->isBaby()){
			$this->xpDropAmount = mt_rand(1, 3);
		}
	}


}
