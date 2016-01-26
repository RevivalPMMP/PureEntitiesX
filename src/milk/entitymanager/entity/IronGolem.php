<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\entity\Creature;
use pocketmine\Player;

class IronGolem extends Monster{
    const NETWORK_ID = 20;

    public $width = 1.3;
    public $height = 1.8;

    public function getSpeed() : float{
        return 1.2;
    }

    public function initEntity(){
        $this->setMaxHealth(100);
        parent::initEntity();

        $this->setFriendly(true);
        $this->setDamage([0, 3, 4, 6]);
        $this->created = true;
    }

    public function getName() : string{
        return "IronGolem";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 4){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
            $player->setMotion(new Vector3(0, 0.7, 0));
        }
    }

    public function getDrops(){
        $drops = [];
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            switch(mt_rand(0, 2)){
                case 0:
                    $drops[] = Item::get(Item::FEATHER, 0, 1);
                    break;
                case 1:
                    $drops[] = Item::get(Item::CARROT, 0, 1);
                    break;
                case 2:
                    $drops[] = Item::get(Item::POTATO, 0, 1);
                    break;
            }
        }
        return $drops;
    }
    public function targetOption(Creature $creature, $distance){
        if(! $creature instanceof Player)
            return $creature->isAlive() && $distance <= 60;
        return false;
    }
}
