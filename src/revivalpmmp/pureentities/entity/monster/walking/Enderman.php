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

use pocketmine\block\Block;
use pocketmine\block\Pumpkin;
use pocketmine\entity\Creature;
use pocketmine\math\Vector3;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class Enderman extends WalkingMonster {
    const NETWORK_ID = Data::ENDERMAN;

    public $height = 2.875;
    public $width = 1.094;
    public $length = 0.5;
    public $speed = 1.21;

    public function getSpeed(): float {
        return $this->speed;
    }

    public function initEntity() {
        parent::initEntity();

        $this->setDamage([0, 4, 7, 10]);
    }

    public function getName() {
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
            $player->attack($ev->getFinalDamage(), $ev);

            $this->checkTamedMobsAttack($player);
        }
    }

    public function onUpdate($currentTick) : bool {
    	$id = $this->level->getBlock($this->getSide(Vector3::SIDE_DOWN))->getId();
    	if($id === Block::STILL_WATER or $id === Block::WATER or $id === Block::LAVA or $id === Block::STILL_LAVA) {
    		$vec = self::asVector3()->add(mt_rand(-15,15), 0, mt_rand(-15,15));
    		$y = $this->level->getHighestBlockAt($vec->x, $vec->z);
    		$this->teleport($vec->add(0,$y)); // TODO: get more accurate distances
	    }
	    return parent::onUpdate($currentTick);
    }
	public function getDrops() : array {
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
    public function playerLooksAt (Player $player) {
        // if the player wears a pumpkin, the enderman doesn't attack the player
        if (!$player->getInventory()->getHelmet() instanceof Pumpkin) {
            $this->setBaseTarget($player);
        }

    }


}
