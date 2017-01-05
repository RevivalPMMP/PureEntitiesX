<?php

namespace revivalpmmp\pureentities\event;

use pocketmine\block\Air;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\tile\Tile;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\tile\Spawner;

class EventListener implements Listener {

	private $plugin;

	public function __construct(PureEntities $plugin) {
		$this->plugin = $plugin;
	}

	public function PlayerInteractEvent(PlayerInteractEvent $ev){
		if($ev->getFace() == 255 || $ev->getAction() != PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}

		$item = $ev->getItem();
		$block = $ev->getBlock();
		if($item->getId() === Item::SPAWN_EGG && $block->getId() == Item::MONSTER_SPAWNER){
			$ev->setCancelled();

			$tile = $block->level->getTile($block);
			if($tile != null && $tile instanceof Spawner){
				$tile->setSpawnEntityType($item->getDamage());
			}else{
				if($tile != null){
					$tile->close();
				}
				$nbt = new CompoundTag("", [
					new StringTag("id", Tile::MOB_SPAWNER),
					new IntTag("EntityId", $item->getId()),
					new IntTag("x", $block->x),
					new IntTag("y", $block->y),
					new IntTag("z", $block->z),
				]);
				new Spawner($block->getLevel()->getChunk((int) $block->x >> 4, (int) $block->z >> 4), $nbt);
			}
		}
	}

	public function dataPacketReceiveEvent (DataPacketReceiveEvent $event) {
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		if($packet->pid() === Info::INTERACT_PACKET) {
			if($packet->action === InteractPacket::ACTION_RIGHT_CLICK) {
				foreach($player->level->getEntities() as $entity) {
					if($entity instanceof Sheep && $entity->distance($player) <= 4) {
						if($entity->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHEARED) === true) {
							return false;
						} else {
							$player->getLevel()->dropItem($entity, Item::get(Item::WOOL, 0, mt_rand(1, 3)));
							$entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHEARED, true);
							$player->setDataProperty(Entity::DATA_INTERACTIVE_TAG, Entity::DATA_TYPE_STRING, "");
							return true;
						}
					}
				}
			}
		}
		return false;
	}

	public function BlockPlaceEvent(BlockPlaceEvent $ev){
		if($ev->isCancelled()){
			return;
		}

		$block = $ev->getBlock();
		if($block->getId() == Item::JACK_O_LANTERN || $block->getId() == Item::PUMPKIN){
			if(
				$block->getSide(Vector3::SIDE_DOWN)->getId() == Item::SNOW_BLOCK
				&& $block->getSide(Vector3::SIDE_DOWN, 2)->getId() == Item::SNOW_BLOCK
			){
				for($y = 1; $y < 3; $y++){
					$block->getLevel()->setBlock($block->add(0, -$y, 0), new Air());
				}
				$entity = PureEntities::create("SnowGolem", Position::fromObject($block->add(0.5, -2, 0.5), $block->level));
				if($entity != null){
					$entity->spawnToAll();
				}
				$ev->setCancelled();
			}elseif(
				$block->getSide(Vector3::SIDE_DOWN)->getId() == Item::IRON_BLOCK
				&& $block->getSide(Vector3::SIDE_DOWN, 2)->getId() == Item::IRON_BLOCK
			){
				$first = $block->getSide(Vector3::SIDE_EAST);
				$second = $block->getSide(Vector3::SIDE_EAST);
				if(
					$first->getId() == Item::IRON_BLOCK
					&& $second->getId() == Item::IRON_BLOCK
				){
					$block->getLevel()->setBlock($first, new Air());
					$block->getLevel()->setBlock($second, new Air());
				}else{
					$first = $block->getSide(Vector3::SIDE_NORTH);
					$second = $block->getSide(Vector3::SIDE_SOUTH);
					if(
						$first->getId() == Item::IRON_BLOCK
						&& $second->getId() == Item::IRON_BLOCK
					){
						$block->getLevel()->setBlock($first, new Air());
						$block->getLevel()->setBlock($second, new Air());
					}else{
						return;
					}
				}

				if($second != null){
					$entity = PureEntities::create("IronGolem", Position::fromObject($block->add(0.5, -2, 0.5), $block->level));
					if($entity != null){
						$entity->spawnToAll();
					}

					$block->getLevel()->setBlock($entity, new Air());
					$block->getLevel()->setBlock($block->add(0, -1, 0), new Air());
					$ev->setCancelled();
				}
			}
		}
	}

}