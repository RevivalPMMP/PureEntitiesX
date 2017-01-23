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
use pocketmine\item\GoldSword;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\entity\Creature;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;

class PigZombie extends WalkingMonster{
    const NETWORK_ID = Data::PIG_ZOMBIE;

    private $angry = 0;

    public $width = 0.72;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    public function getSpeed() : float{
        return 1.15;
    }

    public function initEntity(){
        parent::initEntity();

        if(isset($this->namedtag->Angry)){
            $this->angry = (int) $this->namedtag["Angry"];
        }

        $this->fireProof = true;
        $this->setDamage([0, 5, 9, 13]);
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->Angry = new IntTag("Angry", $this->angry);
    }

    public function getName(){
        return "PigZombie";
    }

    public function isAngry() : bool{
        return $this->angry > 0;
    }

    public function setAngry(int $val){
        $this->angry = $val;
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        return $this->isAngry() && parent::targetOption($creature, $distance);
    }

    public function attack($damage, EntityDamageEvent $source){
        parent::attack($damage, $source);

        if(!$source->isCancelled()){
            $this->setAngry(1000);
        }
    }

    public function spawnTo(Player $player){
        parent::spawnTo($player);

        $pk = new MobEquipmentPacket();
        $pk->eid = $this->getId();
        $pk->item = new GoldSword();
        $pk->slot = 10;
        $pk->selectedSlot = 10;
        $player->dataPacket($pk);
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.44){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        $drops = [];
        array_push($drops, Item::get(Item::ROTTEN_FLESH, 0, mt_rand(0, 1)));
        array_push($drops, Item::get(Item::GOLD_INGOT, 0, mt_rand(0, 1)));
        return $drops;
    }

}
