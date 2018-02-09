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


namespace revivalpmmp\pureentities\utils;


use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use revivalpmmp\pureentities\config\mobequipment\EntityConfig;
use revivalpmmp\pureentities\config\mobequipment\helper\ArmorTypeChances;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\features\IntfCanEquip;
use revivalpmmp\pureentities\PureEntities;

/**
 * Class MobEquipper
 *
 * A utility class that is called when an entity has been spawned successfully.
 *
 * @package revivalpmmp\pureentities\utils
 */
class MobEquipper{


	const DIAMOND = "diamond";
	const GOLD = "gold";
	const IRON = "iron";
	const LEATHER = "leather";

	/**
	 * Equips a mob (when IntfCanEquip is implemented) with random items
	 *
	 * @param BaseEntity $entity
	 */
	public static function equipMob(BaseEntity $entity){
		if($entity instanceof IntfCanEquip){
			// check if configuration already cached - if not create it and store it
			$entityConfig = MobEquipmentConfigHolder::getConfig($entity->getName());
			if($entityConfig === null){
				return;
			}

			/**
			 * @var $entityConfig EntityConfig
			 */
			$wearPickupChance = $entityConfig->getWearPickupChance();
			$wearChances = $entityConfig->getWearChances();
			$armorTypeChance = $entityConfig->getArmorTypeChances();

			$wearWeapon = (mt_rand(0, 100) <= $wearPickupChance->getWeaponChance());
			$wearArmor = (mt_rand(0, 100) <= $wearPickupChance->getArmorChance());

			if($wearWeapon){
				// 1/3 chance of iron sword, 2/3 iron shovel
				$weaponItem = Item::get((mt_rand(0, 3) <= 1) ? ItemIds::IRON_SWORD : ItemIds::IRON_SHOVEL);
				$entity->getMobEquipment()->setMainHand($weaponItem);
				PureEntities::logOutput("[MobEquipper] set $weaponItem as weapon for " . $entity, PureEntities::DEBUG);
			}else{
				PureEntities::logOutput("[MobEquipper] set nothing as weapon for " . $entity, PureEntities::DEBUG);
			}

			if($wearArmor){
				$armorType = self::getArmorType($armorTypeChance);
				if(mt_rand(0, 100) <= $wearChances->getFullChance()){ // full armor
					$entity->getMobEquipment()->setHelmet(self::getHelmet($armorType));
					$entity->getMobEquipment()->setLeggings(self::getLeggings($armorType));
					$entity->getMobEquipment()->setBoots(self::getBoots($armorType));
					$entity->getMobEquipment()->setChestplate(self::getChestplate($armorType));
					PureEntities::logOutput("Full $armorType armor for $entity", PureEntities::DEBUG);
				}else if(mt_rand(0, 100) <= $wearChances->getHelmetChestplateLeggingsChance()){ // all without boots
					$entity->getMobEquipment()->setHelmet(self::getHelmet($armorType));
					$entity->getMobEquipment()->setLeggings(self::getLeggings($armorType));
					$entity->getMobEquipment()->setChestplate(self::getChestplate($armorType));
					PureEntities::logOutput("Helmet, leggings and chestplate of $armorType for $entity", PureEntities::DEBUG);
				}else if(mt_rand(0, 100) <= $wearChances->getHelmetChestplateChance()){ // only helmet and chestplate
					$entity->getMobEquipment()->setHelmet(self::getHelmet($armorType));
					$entity->getMobEquipment()->setChestplate(self::getChestplate($armorType));
					PureEntities::logOutput("Helmet and chestplate of type $armorType for $entity", PureEntities::DEBUG);
				}else if(mt_rand(0, 100) <= $wearChances->getHelmetChance()){ // only helmet
					$entity->getMobEquipment()->setHelmet(self::getHelmet($armorType));
					PureEntities::logOutput("$armorType helmet for $entity", PureEntities::DEBUG);
				}else{
					PureEntities::logOutput("No armor via chance selected for $entity", PureEntities::DEBUG);
				}
			}else{
				PureEntities::logOutput("[MobEquipper] set no armor for " . $entity, PureEntities::DEBUG);
			}
		}
	}

	/**
	 * Returns the boot item in correct type
	 *
	 * @param string $armorType
	 * @return Item
	 */
	private static function getBoots(string $armorType) : Item{
		switch($armorType){
			case self::LEATHER:
				return Item::get(ItemIds::LEATHER_BOOTS);
			case self::IRON:
				return Item::get(ItemIds::IRON_BOOTS);
			case self::GOLD:
				return Item::get(ItemIds::GOLD_BOOTS);
			case self::DIAMOND:
				return Item::get(ItemIds::DIAMOND_BOOTS);
		}
		return Item::get(ItemIds::AIR);
	}

	/**
	 * Returns the chestplate / tunic item in correct type
	 *
	 * @param string $armorType
	 * @return Item
	 */
	private static function getChestplate(string $armorType) : Item{
		switch($armorType){
			case self::LEATHER:
				return Item::get(ItemIds::LEATHER_TUNIC);
			case self::IRON:
				return Item::get(ItemIds::IRON_CHESTPLATE);
			case self::GOLD:
				return Item::get(ItemIds::GOLD_CHESTPLATE);
			case self::DIAMOND:
				return Item::get(ItemIds::DIAMOND_CHESTPLATE);
		}
		return Item::get(ItemIds::AIR);
	}

	/**
	 * Returns the helmet item in correct type
	 *
	 * @param string $armorType
	 * @return Item
	 */
	private static function getHelmet(string $armorType) : Item{
		switch($armorType){
			case self::LEATHER:
				return Item::get(ItemIds::LEATHER_CAP);
			case self::IRON:
				return Item::get(ItemIds::IRON_HELMET);
			case self::GOLD:
				return Item::get(ItemIds::GOLD_HELMET);
			case self::DIAMOND:
				return Item::get(ItemIds::DIAMOND_HELMET);
		}
		return Item::get(ItemIds::AIR);
	}

	/**
	 * Returns the leggings item in correct type
	 *
	 * @param string $armorType
	 * @return Item
	 */
	private static function getLeggings(string $armorType) : Item{
		switch($armorType){
			case self::LEATHER:
				return Item::get(ItemIds::LEATHER_PANTS);
			case self::IRON:
				return Item::get(ItemIds::IRON_LEGGINGS);
			case self::GOLD:
				return Item::get(ItemIds::GOLD_LEGGINGS);
			case self::DIAMOND:
				return Item::get(ItemIds::DIAMOND_LEGGINGS);
		}
		return Item::get(ItemIds::AIR);
	}

	/**
	 * Returns the armor to be worn by the entity by chance
	 *
	 * @param $chances ArmorTypeChances
	 * @return string
	 */
	private static function getArmorType(ArmorTypeChances $chances) : string{
		if(mt_rand(0, 100) <= $chances->getDiamond()){
			return self::DIAMOND;
		}else if(mt_rand(0, 100) <= $chances->getGold()){
			return self::GOLD;
		}else if(mt_rand(0, 100) <= $chances->getIron()){
			return self::IRON;
		}else if(mt_rand(0, 100) <= $chances->getLeather()){
			return self::LEATHER;
		}else{
			PureEntities::logOutput("[MobEquipper] No type of armor selected. Fallback to leather", PureEntities::DEBUG);
		}
		return self::LEATHER;
	}

}