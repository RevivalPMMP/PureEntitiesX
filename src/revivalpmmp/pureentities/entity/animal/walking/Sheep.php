<?php

/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2017 RevivalPMMP

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>. */

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\entity\Colorable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ByteTag;
use revivalpmmp\pureentities\data\Data;

class Sheep extends WalkingAnimal implements Colorable{
    const NETWORK_ID = Data::SHEEP;

    const DATA_COLOR_INFO = 16;

    const WHITE = 0;
	const ORANGE = 1;
	const MAGENTA = 2;
	const LIGHT_BLUE = 3;
	const YELLOW = 4;
	const LIME = 5;
	const PINK = 6;
	const GRAY = 7;
	const LIGHT_GRAY = 8;
	const CYAN = 9;
	const PURPLE = 10;
	const BLUE = 11;
	const BROWN = 12;
	const GREEN = 13;
	const RED = 14;
	const BLACK = 15;

    public $width = 0.625;
	public $length = 1.4375;
	public $height = 1.8;

    public function getName(){
        return "Sheep";
    }

    public function __construct(FullChunk $chunk, CompoundTag $nbt){
		if(!isset($nbt->Color)){
			$nbt->Color = new ByteTag("Color", self::getRandomColor());
		}
		parent::__construct($chunk, $nbt);
		$this->setDataProperty(self::DATA_COLOR_INFO, self::DATA_TYPE_BYTE, self::getColor());
	}

    public static function getRandomColor() : int {
		$rand = "";
		$rand .= str_repeat(self::WHITE . " ", 20);
		$rand .= str_repeat(self::ORANGE . " ", 5);
		$rand .= str_repeat(self::MAGENTA . " ", 5);
		$rand .= str_repeat(self::LIGHT_BLUE . " ", 5);
		$rand .= str_repeat(self::YELLOW . " ", 5);
		$rand .= str_repeat(self::GRAY . " ", 10);
		$rand .= str_repeat(self::LIGHT_GRAY . " ", 10);
		$rand .= str_repeat(self::CYAN . " ", 5);
		$rand .= str_repeat(self::PURPLE . " ", 5);
		$rand .= str_repeat(self::BLUE . " ", 5);
		$rand .= str_repeat(self::BROWN . " ", 5);
		$rand .= str_repeat(self::GREEN . " ", 5);
		$rand .= str_repeat(self::RED . " ", 5);
		$rand .= str_repeat(self::BLACK . " ", 10);
		$arr = explode(" ", $rand);
		return intval($arr[mt_rand(0, count($arr) - 1)]);
	}

	public function getColor() : int {
		return (int) $this->namedtag["Color"];
	}

	public function setColor(int $color){
		$this->namedtag->Color = new ByteTag("Color", $color);
	}

    public function initEntity(){
        parent::initEntity();
        $this->setMaxHealth(8);
        $this->setHealth(8);
    }

    public function targetOption(Creature $creature, float $distance) : bool {
        if($creature instanceof Player){
            if($creature->getInventory()->getItemInHand()->getId() === Item::WHEAT) {
                return $creature->spawned && $creature->isAlive() && !$creature->closed && $distance <= 49;
            } elseif($creature->getInventory()->getItemInHand()->getId() === Item::SHEARS && $this instanceof Sheep && $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SHEARED) === false) {
                $creature->setDataProperty(self::DATA_INTERACTIVE_TAG, self::DATA_TYPE_STRING, "Shear");
            } else {
                $creature->setDataProperty(self::DATA_INTERACTIVE_TAG, self::DATA_TYPE_STRING, "");
            }
        }
        return false;
    }

    public function getDrops(){
        return [Item::get(Item::WOOL, self::getColor(), mt_rand(0, 2))];
    }

}
