<?php

namespace revivalpmmp\pureentities\entity\monster\walking;

use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\item\StoneSword;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\entity\Creature;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\Player;

class WitherSkeleton extends WalkingMonster{
    const NETWORK_ID = 48;
    public $width = 0.65;
    public $height = 1.8;

    public function getName(){
        return "Wither Skeleton";
    }
    public function initEntity(){
        parent::initEntity();

        $this->setDamage([0, 3, 4, 6]);
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
    public function spawnTo(Player $player){
        parent::spawnTo($player);

        $pk = new MobEquipmentPacket();
        $pk->eid = $this->getId();
        $pk->item = new StoneSword();
        $pk->slot = 10;
        $pk->selectedSlot = 10;
        $player->dataPacket($pk);
    }
    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 2){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }
    public function getDrops(){
        $drops = [];
        array_push($drops, Item::get(Item::COAL, 0, mt_rand(0, 1)));
        array_push($drops, Item::get(Item::BONE, 0, mt_rand(0, 2)));
        switch (mt_rand(0, 8)) {
          case 1:
            array_push($drops, Item::get(Item::WITHER_SKELETON_SKULL, 0, mt_rand(0, 2)));
            break;
        }
        return $drops;
    }
}
