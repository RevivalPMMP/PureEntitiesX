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

namespace revivalpmmp\pureentities\event;

use pocketmine\block\Air;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use revivalpmmp\pureentities\data\Color;
use revivalpmmp\pureentities\entity\animal\walking\Cow;
use revivalpmmp\pureentities\entity\animal\walking\Ocelot;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;
use revivalpmmp\pureentities\entity\monster\walking\Wolf;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanEquip;
use revivalpmmp\pureentities\features\IntfShearable;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\delayed\SetTamedOwnerTask;
use revivalpmmp\pureentities\task\delayed\ShowMobEquipmentTask;

class EventListener implements Listener{

	private $plugin;

	public function __construct(PureEntities $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * We receive a DataPacketReceiveEvent - which we need for interaction with entities
	 *
	 * @param DataPacketReceiveEvent $event
	 * @return bool
	 */
	public function dataPacketReceiveEvent(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		$return = false;
		if($packet->pid() === ProtocolInfo::INVENTORY_TRANSACTION_PACKET){

			// TODO Clean this method up.
			/**
			 * @var $packet InventoryTransactionPacket
			 */
			$btnTxt = InteractionHelper::getButtonText($player);
			if($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
				$entity = $player->level->getEntity($packet->trData->entityRuntimeId);
				PureEntities::logOutput("$entity with button text $btnTxt", PureEntities::DEBUG);
				if($entity instanceof IntfShearable and !$entity->isSheared() and
					strcmp($btnTxt, PureEntities::BUTTON_TEXT_SHEAR) == 0){
					PureEntities::logOutput("$entity: dataPacketReceiveEvent->shear", PureEntities::DEBUG);
					$return = $entity->shear($player);
				}else if($entity instanceof Cow and strcmp($btnTxt, PureEntities::BUTTON_TEXT_MILK) == 0){
					PureEntities::logOutput("$entity: dataPacketReceiveEvent->milk", PureEntities::DEBUG);
					$return = $entity->milk($player);
				}else if($entity instanceof IntfCanBreed and
					strcmp($btnTxt, PureEntities::BUTTON_TEXT_FEED) == 0 and
					$entity->getBreedingComponent() !== false
				){ // normally, this shouldn't be needed (because IntfCanBreed needs this method!
					PureEntities::logOutput("$entity: dataPacketReceiveEvent->feed", PureEntities::DEBUG);
					$return = $entity->getBreedingComponent()->feed($player); // feed the entity
					// decrease food in players hand
					$itemInHand = $player->getInventory()->getItemInHand();
					if($itemInHand != null){
						$player->getInventory()->getItemInHand()->setCount($itemInHand->getCount() - 1);
					}
				}else if($entity instanceof IntfTameable and !$entity->isTamed() and strcmp($btnTxt, PureEntities::BUTTON_TEXT_TAME) == 0){
					PureEntities::logOutput("$entity: dataPacketReceiveEvent->tame", PureEntities::DEBUG);
					$return = $entity->attemptToTame($player);
				}else if($entity instanceof IntfTameable and $entity->isTamed() and strcmp($btnTxt, PureEntities::BUTTON_TEXT_SIT) == 0){
					PureEntities::logOutput("$entity: dataPacketReceiveEvent->sit", PureEntities::DEBUG);
					$entity->setSitting(true);
					if($entity instanceof Ocelot){
						$entity->setCommandedToSit(true);
					}
					$return = true;
				}else if($entity instanceof IntfTameable and $entity->isTamed() and strcmp(InteractionHelper::getButtonText($player), PureEntities::BUTTON_TEXT_STAND) == 0){
					PureEntities::logOutput("$entity: dataPacketReceiveEvent->stand", PureEntities::DEBUG);
					$entity->setSitting(false);
					if($entity instanceof Ocelot){
						$entity->setCommandedToSit(false);
					}
					$return = true;
				}else if((($entity instanceof Wolf) or ($entity instanceof Sheep)) and strcmp(InteractionHelper::getButtonText($player), PureEntities::BUTTON_TEXT_DYE) == 0){
					$color = Color::convert($player->getInventory()->getItemInHand()->getDamage());
					PureEntities::logOutput("$entity: dataPacketReceiveEvent->dye with color: $color", PureEntities::DEBUG);
					if($entity instanceof Wolf){
						$entity->setCollarColor($color);
					}elseif($entity instanceof Sheep){
						$entity->setColor($color);
					}
					$return = true;
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

	/**
	 * This method is called when a player joins the server. We have to do different stuff here - especially
	 * for mobs that are equipped - as this says we should do so: https://forums.pmmp.io/threads/mob-equipment.1212/
	 *
	 * Anyway it seems that when PlayerJoin event is called - the entity is not spawned to all players already. So
	 * then sending the EquipPacket to the player doesn't work as it's not respected by the client somehow. Therefore
	 * we set a flag in each living entity that is capable of wearing equipment to resend all equipment data to
	 * all players next time "onUpdate" is called - atm this is only implemented for walking monsters.
	 *
	 * @param PlayerJoinEvent $ev
	 */
	public function playerJoin(PlayerJoinEvent $ev){
		PureEntities::logOutput("[EventListener] playerJoin: " . $ev->getPlayer()->getName(), PureEntities::DEBUG);
		foreach($ev->getPlayer()->getLevel()->getEntities() as $entity){
			if($entity->isAlive() and !$entity->isClosed() and $entity instanceof IntfCanEquip and $entity instanceof WalkingMonster and
				PluginConfiguration::getInstance()->getEnableAsyncTasks()
			){
				$this->plugin->getScheduler()->scheduleDelayedTask(new ShowMobEquipmentTask(
					PureEntities::getInstance(), $ev->getPlayer()), 20); // send mob equipment after 20 ticks
			}else if($entity->isAlive() and $entity instanceof IntfTameable and $entity->getOwner() === null and PluginConfiguration::getInstance()->getEnableAsyncTasks()){
				// sometimes tamed wolves don't get their owner back when the player logs back in again. so we
				// need to do that at this point to be SURE that the wolf when respawned belongs to the correct player
				$this->plugin->getScheduler()->scheduleDelayedTask(new SetTamedOwnerTask(
					PureEntities::getInstance(), $entity), 20); // map owner after 20 ticks
			}
		}
	}

	/**
	 * We need this for tamed entities. So when a player quits, we need to store all tamed entities and remove
	 * them from the level. They should be there when player logs in again
	 *
	 * @param PlayerQuitEvent $ev
	 */
	public function playerQuit(PlayerQuitEvent $ev){
		PureEntities::logOutput("[EventListener] playerQuit: " . $ev->getPlayer()->getName(), PureEntities::DEBUG);
		foreach($ev->getPlayer()->getLevel()->getEntities() as $entity){
			if($entity instanceof IntfTameable and $entity->getOwner() !== null and
				strcasecmp($entity->getOwner()->getName(), $ev->getPlayer()->getName()) == 0
			){
				$entity->teleport($ev->getPlayer());
				$entity->despawnFromAll();
				PureEntities::logOutput("$entity: despawned from level because player quit: " . $ev->getPlayer());
			}
		}
	}
}