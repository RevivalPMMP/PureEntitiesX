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


use pocketmine\item\Item;
use pocketmine\item\ItemIds;
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
                $this->setMainHand(Item::get($itemId));
            }
        }
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