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

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\JumpingMonster;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class Slime extends JumpingMonster {
    const NETWORK_ID = Data::SLIME;

    public function getSpeed(): float {
        return $this->speed;
    }

    public function getName(): string {
        return "Slime";
    }

    public function initEntity() {
        parent::initEntity();
        $this->width = Data::WIDTHS[self::NETWORK_ID];
        $this->height = Data::HEIGHTS[self::NETWORK_ID];
        $this->speed = 0.8;

        $this->setDamage([0, 2, 2, 3]);
    }

    /**
     * Attack a player
     *
     * @param Entity $player
     */
    public function attackEntity(Entity $player) {
        if ($this->attackDelay > 10 && $this->distanceSquared($player) < 2) {
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK,
                MobDamageCalculator::calculateFinalDamage($player, $this->getDamage()));
            $player->attack($ev);

            $this->checkTamedMobsAttack($player);
        }
    }

    public function targetOption(Creature $creature, float $distance): bool {
        if ($creature instanceof Player) {
            return $creature->isAlive() && $distance <= 25;
        }
        return false;
    }

    public function getDrops(): array {
        if ($this->isLootDropAllowed()) {
            return [Item::get(Item::SLIMEBALL, 0, mt_rand(0, 2))];
        } else {
            return [];
        }
    }

    public function getMaxHealth(): int {
        return 4;
    }

    public function getKillExperience(): int {
        // normally big, small, tiny
        return mt_rand(1, 4);
    }

}
