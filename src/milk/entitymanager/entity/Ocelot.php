<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\entity\Creature;

class Ocelot extends Monster{
    const NETWORK_ID = 22;

    public $width = 0.72;
    public $length = 0.6;
    public $height = 0.9;

    private $angry = 0;

    public function getSpeed() : float{
        return 1.5;
    }

    public function initEntity(){
        parent::initEntity();

        $this->fireProof = true;
        $this->setMaxHealth(10);

        if(isset($this->namedtag->Angry)){
            $this->angry = (int) $this->namedtag["Angry"];
        }

        $this->setDamage([0, 2, 2, 2]);
        $this->created = true;
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->Angry = new IntTag("Angry", $this->angry);
    }

    public function getName() : string{
        return "Ocelot";
    }

    public function isAngry(){
        return $this->angry > 0;
    }

    public function setAngry($val){
        $this->angry = (int) $val;
    }

    public function targetOption(Creature $creature, $distance){
        if($creature instanceof Player)
            return ($creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::RAW_FISH && $distance <= 49) or $this->isAngry();
        return parent::targetOption($creature, $distance);
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.44){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        return [];
    }

}
