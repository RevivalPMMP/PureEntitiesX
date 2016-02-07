<?php

namespace milk\entitymanager\entity\monster\walking;

use milk\entitymanager\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class Silverfish extends WalkingMonster{
    const NETWORK_ID = 39;

    public $width = 0.7;
    public $height = 0.7;

    public function getSpeed() : float{
        return 1.4;
    }

    public function initEntity(){
        parent::initEntity();

        $this->setDamage([0, 1, 1, 1]);
    }

    public function getName() : string{
        return "Silverfish";
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
