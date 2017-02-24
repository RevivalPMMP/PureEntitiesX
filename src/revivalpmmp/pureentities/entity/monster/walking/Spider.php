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

use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;

class Spider extends WalkingMonster {
    const NETWORK_ID = Data::SPIDER;

    public $width = 1.3;
    public $height = 1.12;

    public function getSpeed(): float {
        return 1.13;
    }

    public function initEntity() {
        parent::initEntity();

        $this->setDamage([0, 2, 2, 3]);
    }

    public function getName() {
        return "Spider";
    }

    public function attackEntity(Entity $player) {
        if ($this->attackDelay > 10 && (($this->isFriendly() && !($player instanceof Player)) || !$this->isFriendly())) {
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);

            $this->checkTamedMobsAttack($player);
        }
    }

    public function getDrops() {
        $drops = [];
        array_push($drops, Item::get(Item::STRING, 0, mt_rand(0, 2)));
        switch (mt_rand(0, 2)) {
            case 0:
                array_push($drops, Item::get(Item::SPIDER_EYE, 0, 1));
                break;
        }
        return $drops;
    }

    public function getMaxHealth() {
        return 16;
    }

}
