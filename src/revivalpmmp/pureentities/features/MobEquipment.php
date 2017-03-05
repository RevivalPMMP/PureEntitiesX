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


namespace revivalpmmp\pureentities\features;


use pocketmine\item\DiamondBoots;
use pocketmine\item\DiamondChestplate;
use pocketmine\item\DiamondHelmet;
use pocketmine\item\DiamondLeggings;
use pocketmine\item\DiamondSword;
use pocketmine\item\GoldBoots;
use pocketmine\item\GoldChestplate;
use pocketmine\item\GoldHelmet;
use pocketmine\item\GoldLeggings;
use pocketmine\item\GoldSword;
use pocketmine\item\IronBoots;
use pocketmine\item\IronChestplate;
use pocketmine\item\IronHelmet;
use pocketmine\item\IronLeggings;
use pocketmine\item\IronSword;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\LeatherBoots;
use pocketmine\item\LeatherCap;
use pocketmine\item\LeatherPants;
use pocketmine\item\LeatherTunic;
use pocketmine\item\StoneSword;
use pocketmine\item\Tool;
use pocketmine\item\WoodenSword;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\PureEntities;

class MobEquipment {

    const NBT_KEY_HAND_ITEMS = "HandItems";
    const NBT_KEY_ARMOR_ITEMS = "ArmorItems";

    /**
     * @var Item|null
     */
    private $mainHand;

    /**
     * @var Item|null
     */
    private $helmet;

    /**
     * @var Item|null
     */
    private $boots;

    /**
     * @var Item|null
     */
    private $leggings;

    /**
     * @var Item|null
     */
    private $chestplate;

    /**
     * The entity that this equipment object is for
     *
     * @var BaseEntity
     */
    private $entity;

    private $damageReductionInPercent = 0;

    private $damageIncreaseByWeapon = 0;

    public function __construct(BaseEntity $entity) {
        $this->entity = $entity;
    }

    /**
     * Initializes the MobEquipment
     */
    public function init () {
        $this->loadFromNBT();
    }

    /**
     * @return mixed
     */
    public function getMainHand() {
        return $this->mainHand;
    }

    /**
     * @param mixed $mainHand
     */
    public function setMainHand($mainHand) {
        if ($this->mainHand !== $mainHand) {
            $this->mainHand = $mainHand;
            $this->recalculateDamageIncreaseByWeapon();
            $this->storeToNBT();
            $this->sendHandItemsToAllClients();
        }
    }

    /**
     * @return mixed
     */
    public function getHelmet() {
        return $this->helmet;
    }

    /**
     * @param mixed $helmet
     */
    public function setHelmet($helmet) {
        if ($this->helmet !== $helmet) {
            $this->helmet = $helmet;
            $this->recalculateArmorDamageReducement();
            $this->storeToNBT();
            $this->sendArmorUpdateToAllClients();
        }
    }

    /**
     * @return mixed
     */
    public function getBoots() {
        return $this->boots;
    }

    /**
     * @param mixed $boots
     */
    public function setBoots($boots) {
        if ($this->boots !== $boots) {
            $this->boots = $boots;
            $this->recalculateArmorDamageReducement();
            $this->storeToNBT();
            $this->sendArmorUpdateToAllClients();
        }

    }

    /**
     * @return mixed
     */
    public function getLeggings() {
        return $this->leggings;
    }

    /**
     * @param mixed $leggings
     */
    public function setLeggings($leggings) {
        if ($this->leggings !== $leggings) {
            $this->leggings = $leggings;
            $this->recalculateArmorDamageReducement();
            $this->storeToNBT();
            $this->sendArmorUpdateToAllClients();
        }
    }

    /**
     * @return mixed
     */
    public function getChestplate() {
        return $this->chestplate;
    }

    /**
     * @param mixed $chestplate
     */
    public function setChestplate($chestplate) {
        if ($this->chestplate !== $chestplate) {
            $this->chestplate = $chestplate;
            $this->recalculateArmorDamageReducement();
            $this->storeToNBT();
            $this->sendArmorUpdateToAllClients();
        }
    }

    /**
     * Checks if any hand (weapon) items NBT tag is set
     *
     * @return bool
     */
    public function isHandItemsSet () {
        return isset($this->entity->namedtag->HandItems);
    }

    /**
     * Checks if any amor items NBT tag is set
     *
     * @return bool
     */
    public function isArmorItemsSet () {
        return isset($this->entity->namedtag->ArmorItems);
    }

    /**
     * This should be called as soon as a player enters the server / level to update the entities
     * holding any MobEquipment class.
     *
     * @param Player $player    the player to send the data packet to
     */
    public function sendEquipmentUpdate (Player $player) {
        if ($this->isArmorItemsSet()) {
            PureEntities::logOutput("sendEquipmentUpdate: armor to " . $player->getName(), PureEntities::DEBUG);
            $player->dataPacket($this->createArmorEquipPacket());
        }
        if ($this->isHandItemsSet()) {
            PureEntities::logOutput("sendEquipmentUpdate: hand items to " . $player->getName(), PureEntities::DEBUG);
            $player->dataPacket($this->createHandItemsEquipPacket());
        }
    }

    /**
     * This method checks all blocks around for any interesting loot that is on ground and
     * interesting for the contained entity.
     *
     * @param int $blocksAround
     * @return mixed
     */
    public function isAnyLootOfIntereset(int $blocksAround) {
        // TODO: implement
        return null;
    }


    /**
     * Returns the damage reduce amount in percent by the armor the entity wears
     *
     * @return int the damage reduction in percent!
     */
    public function getArmorDamagePercentToReduce(): int {
        return $this->damageReductionInPercent;
    }

    /**
     * Returns the damage to add to "original" damage done by the entity by taking weapon into account
     *
     * @return int
     */
    public function getWeaponDamageToAdd(): int {
        return $this->damageIncreaseByWeapon;
    }

    /**
     * This method adds loot to the given drops array by checking with a 9% chance if anything is weared. If so,
     * the drop array will be extended by the equipment the entity wears
     *
     * @param $existingDrops Item the existing drops containing none or any item already
     */
    public function addLoot(array $existingDrops) {
        // Some monsters can spawn with a sword, and have a 8.5% (9.5% with Looting I, 10.5% with Looting II and 11.5% with Looting III)
        // see: http://minecraft.gamepedia.com/Sword (section: mosbs)
        if (mt_rand(0, 100) <= 9) {
            // drop all equipment
            if ($this->mainHand !== null && $this->mainHand->getId() !== ItemIds::AIR) {
                array_push($existingDrops, $this->mainHand);
            }
            if ($this->helmet !== null && $this->helmet->getId() !== ItemIds::AIR) {
                array_push($existingDrops, $this->helmet);
            }
            if ($this->boots !== null && $this->boots->getId() !== ItemIds::AIR) {
                array_push($existingDrops, $this->boots);
            }
            if ($this->chestplate !== null && $this->chestplate->getId() !== ItemIds::AIR) {
                array_push($existingDrops, $this->chestplate);
            }
            if ($this->leggings !== null && $this->leggings->getId() !== ItemIds::AIR) {
                array_push($existingDrops, $this->leggings);
            }
        }
    }

    private function recalculateDamageIncreaseByWeapon() {
        $this->damageIncreaseByWeapon = $this->getWeaponDamage();
    }

    /**
     * Recalculate the damage reduction in percent for all armor worn by entity
     */
    private function recalculateArmorDamageReducement() {
        $this->damageReductionInPercent = $this->getChestplateArmorPoints() + $this->getBootsArmorPoints() + $this->getHelmetArmorPoints() +
            $this->getLeggingsArmorPoints();
    }

    /**
     * Returns the damage to be added to normal damage when wielding at least a weapon
     * @return int
     */
    private function getWeaponDamage(): int {
        $damageToAdd = 0;
        $itemInMain = $this->getMainHand();
        /**
         * @var $itemInMain Item
         */
        if ($itemInMain !== null and $itemInMain->getId() !== ItemIds::AIR) {
            // is wearing something in main hand
            if ($this->isSwordWorn()) {
                $tier = $itemInMain->isSword(); // strange: this returns the tier (as in PMMP and Tesseract)
                switch ($tier) {
                    case Tool::TIER_WOODEN:
                    case Tool::TIER_GOLD:
                        $damageToAdd = 5;
                        break;
                    case Tool::TIER_STONE:
                        $damageToAdd = 6;
                        break;
                    case Tool::TIER_IRON:
                        $damageToAdd = 7;
                        break;
                    case Tool::TIER_DIAMOND:
                        $damageToAdd = 8;
                        break;
                    default:
                        $damageToAdd = 1;
                        break;
                }
            } else {
                // for now just add 1 damage regardless of item hold (as damage is not there for all items)
                $damageToAdd = 1;
            }
        }
        return $damageToAdd;
    }

    /**
     * Returns armor points to be added by checking which chestplate the entity wears.
     *
     * Check this: Each defense point will reduce any damage dealt to the player which is absorbed by armor by 4%
     *
     * @return int
     */
    private function getChestplateArmorPoints(): int {
        $armor = 0;
        $item = $this->getChestplate();
        if ($item !== null && $item->getId() !== ItemIds::AIR) {
            if ($item instanceof LeatherTunic) {
                $armor = 3;
            } else if ($item instanceof GoldChestplate) {
                $armor = 5;
            } else if ($item instanceof IronChestplate) {
                $armor = 6;
            } else if ($item instanceof DiamondChestplate) {
                $armor = 8;
            }
        }
        return $armor * 4;
    }

    /**
     * Returns armor points to be added by checking which helmet the entity wears
     *
     * Check this: Each defense point will reduce any damage dealt to the player which is absorbed by armor by 4%
     *
     * @return int
     */
    private function getHelmetArmorPoints(): int {
        $armor = 0;
        $item = $this->getHelmet();
        if ($item !== null && $item->getId() !== ItemIds::AIR) {
            if ($item instanceof LeatherCap) {
                $armor = 1;
            } else if ($item instanceof GoldHelmet) {
                $armor = 2;
            } else if ($item instanceof IronHelmet) {
                $armor = 2;
            } else if ($item instanceof DiamondHelmet) {
                $armor = 3;
            }
        }
        return $armor * 4;
    }

    /**
     * Returns armor points to be added by checking which boots the entity wears
     *
     * Check this: Each defense point will reduce any damage dealt to the player which is absorbed by armor by 4%
     *
     * @return int
     */
    private function getBootsArmorPoints(): int {
        $armor = 0;
        $item = $this->getBoots();
        if ($item !== null && $item->getId() !== ItemIds::AIR) {
            if ($item instanceof LeatherBoots) {
                $armor = 1;
            } else if ($item instanceof GoldBoots) {
                $armor = 1;
            } else if ($item instanceof IronBoots) {
                $armor = 2;
            } else if ($item instanceof DiamondBoots) {
                $armor = 3;
            }
        }
        return $armor * 4;
    }

    /**
     * Returns armor points to be added by checking which leggings the entity wears
     *
     * Check this: Each defense point will reduce any damage dealt to the player which is absorbed by armor by 4%
     *
     * @return int
     */
    private function getLeggingsArmorPoints(): int {
        $armor = 0;
        $item = $this->getBoots();
        if ($item !== null && $item->getId() !== ItemIds::AIR) {
            if ($item instanceof LeatherPants) {
                $armor = 2;
            } else if ($item instanceof GoldLeggings) {
                $armor = 3;
            } else if ($item instanceof IronLeggings) {
                $armor = 5;
            } else if ($item instanceof DiamondLeggings) {
                $armor = 6;
            }
        }
        return $armor * 4;
    }

    /**
     * Returns true when the item held in main hand is a sword
     *
     * @return bool
     */
    private function isSwordWorn(): bool {
        return $this->getMainHand() !== null and $this->getMainHand()->getId() !== ItemIds::AIR and
            ($this->getMainHand() instanceof IronSword or $this->getMainHand() instanceof StoneSword or
                $this->getMainHand() instanceof WoodenSword or $this->getMainHand() instanceof GoldSword or
                $this->getMainHand() instanceof DiamondSword);
    }

    private function storeToNBT () {
        // feet, legs, chest, head - store armor content to NBT
        $armor[0] = new CompoundTag("0", [
            "Count" => new IntTag("Count", 1),
            "Damage" => new IntTag("Damage", 10),
            "id" => new IntTag("id", $this->boots !== null ? $this->boots->getId() : ItemIds::AIR),
        ]);
        $armor[1] = new CompoundTag("1", [
            "Count" => new IntTag("Count", 1),
            "Damage" => new IntTag("Damage", 10),
            "id" => new IntTag("id", $this->leggings !== null ? $this->leggings->getId() : ItemIds::AIR),
        ]);
        $armor[2] = new CompoundTag("2", [
            "Count" => new IntTag("Count", 1),
            "Damage" => new IntTag("Damage", 10),
            "id" => new IntTag("id", $this->chestplate !== null ? $this->chestplate->getId() : ItemIds::AIR),
        ]);
        $armor[3] = new CompoundTag("3", [
            "Count" => new IntTag("Count", 1),
            "Damage" => new IntTag("Damage", 10),
            "id" => new IntTag("id", $this->helmet !== null ? $this->helmet->getId() : ItemIds::AIR),
        ]);

        $this->entity->namedtag->ArmorItems = new ListTag(self::NBT_KEY_ARMOR_ITEMS, $armor);

        // store hand item to NBT
        $hands[0] = new CompoundTag("0", [
            "Count" => new ByteTag("Count", 1),
            "Damage" => new IntTag("Damage", 10),
            "id" => new IntTag("id", $this->getMainHand() !== null ? $this->getMainHand()->getId() : ItemIds::AIR),
        ]);
        $this->entity->namedtag->HandItems = new ListTag(self::NBT_KEY_HAND_ITEMS, $hands);
    }

    private function loadFromNBT () {
        PureEntities::logOutput("MobEquipment: loadFromNBT for " . $this->entity, PureEntities::DEBUG);
        if ($this->isArmorItemsSet()) {
            PureEntities::logOutput("MobEquipment: armorItems set for " . $this->entity, PureEntities::DEBUG);
            $nbt = $this->entity->namedtag[self::NBT_KEY_ARMOR_ITEMS];
            if ($nbt instanceof ListTag) {
                $itemId = $nbt[0]["id"];
                $this->boots = Item::get($itemId);
                $itemId = $nbt[1]["id"];
                $this->leggings = Item::get($itemId);
                $itemId = $nbt[2]["id"];
                $this->chestplate = Item::get($itemId);
                $itemId = $nbt[3]["id"];
                $this->helmet = Item::get($itemId);
                PureEntities::logOutput("MobEquipment: loaded from NBT [boots:" . $this->boots . "] [legs:" . $this->leggings . "] " .
                    "[chest:" . $this->chestplate . "] [helmet:" . $this->helmet . "]", PureEntities::DEBUG);
            }
        }

        if ($this->isHandItemsSet()) {
            PureEntities::logOutput("MobEquipment: handItems set for " . $this->entity, PureEntities::DEBUG);
            $nbt = $this->entity->namedtag[self::NBT_KEY_HAND_ITEMS];
            if ($nbt instanceof ListTag) {
                $itemId = $nbt[0]["id"];
                PureEntities::logOutput("MobEquipment: found hand item (id): $itemId -> set it now!", PureEntities::DEBUG);
                $this->mainHand = Item::get($itemId);
            }
        }
        $this->recalculateArmorDamageReducement();
        $this->recalculateDamageIncreaseByWeapon();
    }

    /**
     * Sends all armor items equipment to the clients for the embedded entity
     */
    public function sendArmorUpdateToAllClients () {
        $pk = $this->createArmorEquipPacket();
        $this->sendPacketToPlayers($pk);
    }

    /**
     * Sends all armor items equipment to the clients for the embedded entity
     */
    public function sendHandItemsToAllClients () {
        $pk = $this->createHandItemsEquipPacket();
        $this->sendPacketToPlayers($pk);
    }

    private function createArmorEquipPacket () : MobArmorEquipmentPacket {
        $pk = new MobArmorEquipmentPacket();
        $pk->eid = $this->entity->getId();
        $pk->slots = [
            $this->helmet !== null ? $this->helmet : Item::get(ItemIds::AIR),
            $this->chestplate !== null ? $this->chestplate : Item::get(ItemIds::AIR),
            $this->leggings !== null ? $this->leggings : Item::get(ItemIds::AIR),
            $this->boots !== null ? $this->boots : Item::get(ItemIds::AIR)
        ];
        $pk->encode();
        $pk->isEncoded = true;
        return $pk;
    }

    private function createHandItemsEquipPacket () : MobEquipmentPacket {
        $pk = new MobEquipmentPacket();
        $pk->eid = $this->entity->getId();
        $pk->item = $this->mainHand !== null ? $this->mainHand : Item::get(ItemIds::AIR);
        $pk->slot = 0;
        $pk->selectedSlot = 0;
        return $pk;
    }

    private function sendPacketToPlayers (DataPacket $packet) {
        foreach ($this->entity->getLevel()->getServer()->getOnlinePlayers() as $player) {
            $player->dataPacket($packet);
        }
    }


}