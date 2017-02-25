<?php

namespace revivalpmmp\pureentities\event;

use pocketmine\block\Air;
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
use revivalpmmp\pureentities\data\Color;
use revivalpmmp\pureentities\entity\animal\walking\Cow;
use revivalpmmp\pureentities\entity\monster\walking\Wolf;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfShearable;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\InteractionHelper;
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
					new IntTag("EntityId", $item->getDamage()),
					new IntTag("x", $block->x),
					new IntTag("y", $block->y),
					new IntTag("z", $block->z),
				]);
				new Spawner($block->getLevel(), $nbt);
			}
		}
	}

    /**
     * We receive a DataPacketReceiveEvent - which we need for interaction with entities
     *
     * @param DataPacketReceiveEvent $event
     * @return bool
     */
	public function dataPacketReceiveEvent (DataPacketReceiveEvent $event) {
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		$return = false;
		if($packet->pid() === Info::INTERACT_PACKET) {
			if($packet->action === InteractPacket::ACTION_RIGHT_CLICK) {
                $entity = $player->level->getEntity($packet->target);
			    if ($entity instanceof IntfShearable and
                    !$entity->isSheared() and
                    strcmp(InteractionHelper::getButtonText($player), PureEntities::BUTTON_TEXT_SHEAR) == 0) {
			        PureEntities::logOutput("$entity: dataPacketReceiveEvent->shear", PureEntities::DEBUG);
                    $return = $entity->shear($player);
                } else if ($entity instanceof Cow and strcmp(InteractionHelper::getButtonText($player), PureEntities::BUTTON_TEXT_MILK) == 0) {
                    PureEntities::logOutput("$entity: dataPacketReceiveEvent->milk", PureEntities::DEBUG);
                    $return = $entity->milk($player);
                } else if ($entity instanceof IntfCanBreed and
                    strcmp(InteractionHelper::getButtonText($player), PureEntities::BUTTON_TEXT_FEED) == 0 and
                    $entity->getBreedingExtension() !== false) { // normally, this shouldn't be needed (because IntfCanBreed needs this method! - that's why i don't like php that much!)
                    PureEntities::logOutput("$entity: dataPacketReceiveEvent->feed", PureEntities::DEBUG);
                    $return = $entity->getBreedingExtension()->feed($player); // feed the sheep
                    // decrease wheat in players hand
                    $itemInHand = $player->getInventory()->getItemInHand();
                    if ($itemInHand != null) {
                        $player->getInventory()->getItemInHand()->setCount($itemInHand->getCount() - 1);
                    }
                } else if ($entity instanceof IntfTameable and strcmp(InteractionHelper::getButtonText($player), PureEntities::BUTTON_TEXT_TAME) == 0) {
                    PureEntities::logOutput("$entity: dataPacketReceiveEvent->tame", PureEntities::DEBUG);
                    $return = $entity->tame($player);
                } else if ($entity instanceof Wolf) {
                    if (strcmp(InteractionHelper::getButtonText($player), PureEntities::BUTTON_TEXT_SIT) == 0) {
                        PureEntities::logOutput("$entity: dataPacketReceiveEvent->sit", PureEntities::DEBUG);
                        $entity->setSitting(!$entity->isSitting());
                        $return = true;
                    } else if (strcmp(InteractionHelper::getButtonText($player), PureEntities::BUTTON_TEXT_DYE) == 0) {
                        $color = Color::convert($player->getInventory()->getItemInHand()->getDamage());
                        PureEntities::logOutput("$entity: dataPacketReceiveEvent->dye with color: $color", PureEntities::DEBUG);
                        $entity->setCollarColor($color);
                        $return = true;
                    }
                }
			}
		}
		return $return;
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