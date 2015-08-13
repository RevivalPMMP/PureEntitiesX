<?php

namespace plugin\Entity;

use pocketmine\entity\Rideable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class Pig extends Animal implements Rideable{
    const NETWORK_ID = 12;

    public $width = 1.6;
    public $length = 0.8;
    public $height = 1.12;

    public function getName(){
        return "돼지";
    }

    public function initEntity(){
        $this->setMaxHealth(10);
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        parent::initEntity();
        $this->created = true;
    }

    public function targetOption(Player $player, $distance){
        return $player->spawned && $player->isAlive() && !$player->closed && $player->getInventory()->getItemInHand()->getId() == Item::CARROT && $distance <= 49;
    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::RAW_PORKCHOP, 0, 1)];
        }
        return [];
    }

}