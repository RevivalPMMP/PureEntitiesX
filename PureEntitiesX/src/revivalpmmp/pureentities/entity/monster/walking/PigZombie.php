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

namespace revivalpmmp\pureentities\entity\monster\walking;

use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\item\GoldSword;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\entity\Creature;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class PigZombie extends WalkingMonster {
    const NETWORK_ID = Data::PIG_ZOMBIE;

    private $angry = 0;
    public $eyeHeight = 1.62;

    public function getSpeed(): float {
        return $this->speed;
    }

    public function initEntity() {
        parent::initEntity();
        $this->width = Data::WIDTHS[self::NETWORK_ID];
        $this->height = Data::HEIGHTS[self::NETWORK_ID];
        $this->speed = 1.15;

        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            if (isset($this->namedtag->Angry)) {
                $this->angry = (int)$this->namedtag["Angry"];
            }
        }

        $this->fireProof = true;
        $this->setDamage([0, 5, 9, 13]);
    }

    public function saveNBT() {
        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            parent::saveNBT();
            $this->namedtag->Angry = new IntTag("Angry", $this->angry);
        }
    }

    public function getName(): string {
        return "PigZombie";
    }

    public function isAngry(): bool {
        return $this->angry > 0;
    }

    public function setAngry(int $val) {
        $this->angry = $val;
    }

    public function targetOption(Creature $creature, float $distance): bool {
        return $this->isAngry() && parent::targetOption($creature, $distance);
    }

    public function attack(EntityDamageEvent $source) {
        parent::attack($source);

        if (!$source->isCancelled()) {
            $this->setAngry(1000);
        }
    }

    public function spawnTo(Player $player) {
        parent::spawnTo($player);

        $pk = new MobEquipmentPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->item = new GoldSword();
        $pk->inventorySlot = 10;
        $pk->hotbarSlot = 10;
        $player->dataPacket($pk);
    }

    /**
     * Attack the player
     *
     * @param Entity $player
     */
    public function attackEntity(Entity $player) {
        if ($this->attackDelay > 10 && $this->distanceSquared($player) < 1.44) {
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK,
                MobDamageCalculator::calculateFinalDamage($player, $this->getDamage()));
            $player->attack($ev);

            $this->checkTamedMobsAttack($player);
        }
    }

    public function getDrops(): array {
        $drops = [];
        if ($this->isLootDropAllowed()) {
            array_push($drops, Item::get(Item::ROTTEN_FLESH, 0, mt_rand(0, 1)));
            array_push($drops, Item::get(Item::GOLD_INGOT, 0, mt_rand(0, 1)));
        }
        return $drops;
    }

    public function getMaxHealth(): int {
        return 20;
    }

    public function getKillExperience(): int {
        // adult: 5, baby: 12
        return 5;
    }


}
