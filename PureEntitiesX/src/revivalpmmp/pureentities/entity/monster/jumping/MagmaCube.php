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

namespace revivalpmmp\pureentities\entity\monster\jumping;

use pocketmine\item\Item;
use revivalpmmp\pureentities\entity\monster\JumpingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class MagmaCube extends JumpingMonster {
    const NETWORK_ID = Data::MAGMA_CUBE;

    public function getSpeed(): float {
        return $this->speed;
    }

    public function initEntity() {
        parent::initEntity();
        $this->width = Data::WIDTHS[self::NETWORK_ID];
        $this->height = Data::HEIGHTS[self::NETWORK_ID];
        $this->speed = 0.8;

        $this->fireProof = true;
        $this->setDamage([0, 3, 4, 6]);
    }

    public function getName(): string {
        return "MagmaCube";
    }

    /**
     * Attack a player
     *
     * @param Entity $player
     */
    public function attackEntity(Entity $player) {
        if ($this->attackDelay > 10 && $this->distanceSquared($player) < 1) {
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
            switch (mt_rand(0, 1)) {
                case 0:
                    $drops[] = Item::get(Item::NETHERRACK, 0, 1);
                    break;
                case 1:
                    $drops[] = Item::get(Item::MAGMA_CREAM, 0, 1);
                    break;
            }
        }
        return $drops;
    }

    public function getKillExperience(): int {
        // normally it would be set by small/medium/big sized - but as we have it not now - i'll make it more static
        return mt_rand(3, 6);
    }


}
