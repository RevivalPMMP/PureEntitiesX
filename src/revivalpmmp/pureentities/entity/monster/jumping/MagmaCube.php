<?php

namespace revivalpmmp\pureentities\entity\monster\jumping;

use pocketmine\item\Item;
use revivalpmmp\pureentities\entity\monster\JumpingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class MagmaCube extends JumpingMonster{
    const NETWORK_ID = 42;

    public $width = 1.2;
    public $height = 1.2;

    public function getSpeed() : float{
        return 0.8;
    }

    public function initEntity(){
        parent::initEntity();

        $this->fireProof = true;
        $this->setDamage([0, 3, 4, 6]);
    }

    public function getName(){
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
        $drops = [];
        switch(mt_rand(0, 2)){
            case 0:
                $drops[] = Item::get(Item::NETHERRACK, 0, 1);
                break;
            case 1:
                $drops[] = Item::get(Item::BLAZE_ROD, 0, 1);
                break;
            case 2:
                $drops[] = Item::get(Item::GUNPOWDER, 0, 1);
                break;
        }
        return $drops;
    }

}
