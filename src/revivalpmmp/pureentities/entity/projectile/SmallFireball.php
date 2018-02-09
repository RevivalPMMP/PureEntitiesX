<?php
/**
 * Created by PhpStorm.
 * User: aaron
 * Date: 2/3/2018
 * Time: 11:01 PM
 */

namespace revivalpmmp\pureentities\entity\projectile;


use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use revivalpmmp\pureentities\data\Data;

class SmallFireball extends FireBall{
	const NETWORK_ID = Data::NETWORK_IDS["small_fireball"];

	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null, bool $critical = false){
		parent::__construct($level, $nbt, $shootingEntity, $critical);
		$this->height = Data::HEIGHTS[self::NETWORK_ID];
		$this->width = Data::WIDTHS[self::NETWORK_ID];
	}

}