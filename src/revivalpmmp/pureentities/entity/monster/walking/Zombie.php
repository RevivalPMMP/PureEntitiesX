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

use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\item\Item;
use pocketmine\level\Level;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PureEntities;

class Zombie extends WalkingMonster {
    const NETWORK_ID = Data::ZOMBIE;

    public $width = 0.72;
    public $height = 1.8;

    public function getSpeed() : float{
        return 1.1;
    }

    public function initEntity(){
        parent::initEntity();
        $this->setDamage([0, 2, 3, 4]);
    }

    public function getName(){
        return "Zombie";
    }

    public function setHealth($amount){
        parent::setHealth($amount);

        if($this->isAlive()){
            if(15 < $this->getHealth()){
                $this->setDamage([0, 2, 3, 4]);
            }else if(10 < $this->getHealth()){
                $this->setDamage([0, 3, 4, 6]);
            }else if(5 < $this->getHealth()){
                $this->setDamage([0, 3, 5, 7]);
            }else{
                $this->setDamage([0, 4, 6, 9]);
            }
        }
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 2){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);

            // check if the player has tamed mobs (wolf e.g.) if so - the wolves need to set
            // target to this one and attack it!
            if ($player instanceof Player) {
                foreach ($this->getTamedMobs($player) as $tamedMob) {
                    $tamedMob->setBaseTarget($this);
                    $tamedMob->stayTime = 0;
                    if ($tamedMob instanceof Wolf and $tamedMob->isSitting()) {
                        $tamedMob->setSitting(false);
                    }
                    PureEntities::logOutput("$this: setting this as target for $tamedMob", PureEntities::DEBUG);
                }
            }
        }
    }

    public function entityBaseTick($tickDiff = 1, $EnchantL = 0){
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $time = $this->getLevel()->getTime() % Level::TIME_FULL;
        if(
            !$this->isOnFire()
            && ($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE)
        ){
            $this->setOnFire(100);
        }

        Timings::$timerEntityBaseTick->stopTiming();
        return $hasUpdate;
    }

    public function getDrops(){
        $drops = [];
        array_push($drops, Item::get(Item::ROTTEN_FLESH, 0, mt_rand(0, 2)));
        switch(mt_rand(0, 5)){
            case 1:
                array_push($drops, Item::get(Item::CARROT, 0, 1));
                break;
            case 2:
                array_push($drops, Item::get(Item::POTATO, 0, 1));
                break;
            case 3:
                array_push($drops, Item::get(Item::IRON_INGOT, 0, 1));
                break;
        }
        return $drops;
    }

    public function getMaxHealth() {
        return 20;
    }

    /**
     * Returns all tamed mobs for the given player ...
     * @param Player $player
     * @return array
     */
    private function getTamedMobs (Player $player) {
        $tamedMobs = [];
        foreach($player->getLevel()->getEntities() as $entity) {
            if ($entity instanceof IntfTameable and
                $entity->isTamed() and
                strcasecmp($entity->getOwner()->getName(), $player->getName()) === 0 and
                $entity->isAlive()) {
                $tamedMobs[] = $entity;
            }
        }
        return $tamedMobs;
    }
}
