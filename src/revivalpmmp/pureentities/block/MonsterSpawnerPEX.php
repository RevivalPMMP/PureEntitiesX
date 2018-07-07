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

/**
 * Thanks to CortexPE and NycuRO for some helpful information in TeaSpoon
 * The framework for this was based on their MonsterSpawner
 *
 *https://github.com/CortexPE/TeaSpoon/blob/master/src/CortexPE/block/MonsterSpawner.php
 */

namespace revivalpmmp\pureentities\block;


use pocketmine\block\Block;
use pocketmine\block\MonsterSpawner;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Tile;
use revivalpmmp\pureentities\tile\MobSpawner;

class MonsterSpawnerPEX extends MonsterSpawner{
	private $entityId = -1;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function canBeActivated(): bool{
		return true;
	}


	/**
	 * This method replaces the event listener that was being used for spawn eggs.
	 * It will need to be updated in the future when additional tags are supported.
	 *
	 *
	 * @param Item        $item
	 * @param Player|null $player
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null) : bool{
		if($item->getId() !== Item::SPAWN_EGG){
			return false;
		}
		$this->entityId = $item->getDamage();
		$this->generateSpawnerTile();
		return true;
	}


	/**
	 * Additional method for updating MobSpawner tile detals.  This will also need
	 * to be updated in the future when additional tag info is supported.
	 *
	 *
	 * @param Item        $item
	 * @param Block       $blockReplace
	 * @param Block       $blockClicked
	 * @param int         $face
	 * @param Vector3     $clickVector
	 * @param Player|null $player
	 * @return bool
	 */
	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$return = parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);

		if($item->getDamage() > 9){
			$this->meta = 0;
			$this->entityId = $item->getDamage();
			$this->getLevel()->setBlock($this, $this, true, false);
			$this->generateSpawnerTile();
		}

		return $return;
	}


	/**
	 * Checks for the presence of a MobSpawner in the same location as the MonsterSpawner block.
	 * If not there, it creates one, then updates the EntityId.
	 */
	private function generateSpawnerTile() {
		$tile = $this->getLevel()->getTile($this);
		if(!$tile instanceof MobSpawner){
			$nbt = MobSpawner::createNBT($this);
			$nbt->setString(Tile::TAG_ID, Tile::MOB_SPAWNER);

			/** @var MobSpawner $spawnerTile */
			$tile = Tile::createTile("MobSpawner", $this->getLevel(), $nbt);
		}
		$tile->setSpawnEntityType($this->entityId);
	}


	/**
	 * Vanilla MonsterSpanwers don't drop themselves under any circumstance.  May add the ability
	 * drop itself through config options.
	 *
	 * @param Item $item
	 * @return array
	 */
	public function getSilkTouchDrops(Item $item) : array{
		return [];
	}
}