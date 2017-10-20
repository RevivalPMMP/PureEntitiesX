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

use pocketmine\block\Pumpkin;
use pocketmine\entity\Creature;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class Enderman extends WalkingMonster {
    const NETWORK_ID = Data::ENDERMAN;

    public function getSpeed(): float {
        return $this->speed;
    }

    public function initEntity() {
        parent::initEntity();
        $this->width = Data::WIDTHS[self::NETWORK_ID];
        $this->height = Data::HEIGHTS[self::NETWORK_ID];
        $this->speed = 1.21;

        $this->setDamage([0, 4, 7, 10]);
    }

    public function getName(): string {
        return "Enderman";
    }

    /**
     * Attacks player ...
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
        /*if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(368, 0, 1)];
        }*/
        # It doesn't seem like Enderpearls exist in PocketMine, this was probably what caused the Endermen to despawn instead of dying
        return [];
    }

    public function targetOption(Creature $creature, float $distance): bool {
        // enderman don't attack alone. they only attack when looked at
        return false;
    }


    public function getKillExperience(): int {
        return 5;
    }

    /**
     * This method is called from InteractionHelper when a player looks at this entity
     *
     * @param Player $player
     */
    public function playerLooksAt(Player $player) {
        // if the player wears a pumpkin, the enderman doesn't attack the player
        if (!$player->getInventory()->getHelmet() instanceof Pumpkin) {
            $this->setBaseTarget($player);
        }

    }


}
