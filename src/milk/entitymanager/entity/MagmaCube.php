<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class MagmaCube extends Monster{
    const NETWORK_ID = 42;

    public $width = 1.2;
    public $height = 1.2;

    public function getSpeed() : float{
        return 0.8;
    }

    public function initEntity(){
        parent::initEntity();

        $this->setDamage([0, 3, 4, 6]);
        $this->created = true;
    }

    public function getName() : string{
        return "MagmaCube";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        return [];
    }

}
