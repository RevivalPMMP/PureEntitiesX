<?php

namespace revivalpmmp\pureentities\entity\monster\walking;

use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

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
