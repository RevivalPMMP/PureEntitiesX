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
use revivalpmmp\pureentities\data\Data;

class Enderman extends WalkingMonster{
    const NETWORK_ID = Data::ENDERMAN;

    public $width = 0.72;
    public $height = 2.8;

    public function getSpeed() : float{
        return 1.21;
    }

    public function initEntity(){
        parent::initEntity();

        $this->setDamage([0, 4, 7, 10]);
    }

    public function getName(){
        return "Enderman";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        /*if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(368, 0, 1)];
        }*/
        # It doesn't seem like ender pearls exist in PocketMine, this was probably what caused the Endermen to despawn instead of dying
        return [];
    }

}
