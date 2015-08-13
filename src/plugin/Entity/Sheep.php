<?php

namespace plugin\Entity;

use pocketmine\entity\Colorable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class Sheep extends Animal implements Colorable{
    const NETWORK_ID = 13;

    public $width = 1.6;
    public $length = 0.8;
    public $height = 1.12;

    public function getName(){
        return "양";
    }

    public function initEntity(){
        $this->setMaxHealth(8);
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        parent::initEntity();
        $this->created = true;
    }

    public function targetOption(Player $player, $distance){
        return $player->spawned && $player->isAlive() && !$player->closed && $player->getInventory()->getItemInHand()->getId() == Item::SEEDS && $distance <= 49;
    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::WOOL, mt_rand(0, 15), 1)];
        }
        return [];
    }

}