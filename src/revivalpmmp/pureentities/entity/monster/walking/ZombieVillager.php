<?php

namespace revivalpmmp\pureentities\entity\monster\walking;

use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\item\Item;
use pocketmine\level\Level;
use revivalpmmp\pureentities\data\Data;

class ZombieVillager extends WalkingMonster{
    const NETWORK_ID = Data::ZOMBIE_VILLAGER;

    public $width = 0.72;
    public $height = 1.8;

    public function getSpeed() : float{
        return 1.1;
    }

    public function initEntity(){
        parent::initEntity();

        $this->setDamage([0, 3, 4, 6]);
    }

    public function getName(){
        return "ZombieVillager";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function entityBaseTick($tickDiff = 1){
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $time = $this->getLevel()->getTime() % Level::TIME_FULL;
        if(
            !$this->isOnFire()
            && ($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE)
        ){
            $this->setOnFire(100);
        }

        Timings::$timerEntityBaseTick->startTiming();
        return $hasUpdate;
    }

    public function getDrops(){
      $drops = [];
      array_push($drops, Item::get(Item::ROTTEN_FLESH, 0, mt_rand(0, 2)));
      switch(mt_rand(0, 5)){
          case 1:
              array_push($drops, Item::get(Item::CARROT, 0, 1));
              break;
          case 2:
              array_push($drops, Item::get(Item::POTATO, 0, 1));
              break;
          case 3:
              array_push($drops, Item::get(Item::IRON_INGOT, 0, 1));
              break;
      }
      return $drops;
    }

}
