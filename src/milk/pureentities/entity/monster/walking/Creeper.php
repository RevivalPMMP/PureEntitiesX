<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\entity\Explosive;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;

class Creeper extends WalkingMonster implements Explosive{
    const NETWORK_ID = 33;

    public $width = 0.72;
    public $height = 1.8;

    private $bombTime = 0;

    public function getSpeed() : float{
        return 0.9;
    }

    public function initEntity(){
        parent::initEntity();

        if(isset($this->namedtag->BombTime)){
            $this->bombTime = (int) $this->namedtag["BombTime"];
        }
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->BombTime = new IntTag("BombTime", $this->bombTime);
    }

    public function getName() : string{
        return "Creeper";
    }

    public function explode(){
        $this->server->getPluginManager()->callEvent($ev = new ExplosionPrimeEvent($this, 2.8));

        if(!$ev->isCancelled()){
            $explosion = new Explosion($this, $ev->getForce(), $this);
            if($ev->isBlockBreaking()){
                $explosion->explodeA();
            }
            $explosion->explodeB();
            $this->close();
        }
    }

    public function attackEntity(Entity $player){
        if($this->distanceSquared($player) > 38){
            if($this->bombTime > 0){
                $this->bombTime -= min(2, $this->bombTime);
            }
        }elseif($this->bombTime++ >= mt_rand(55, 70)){
            $this->explode();
        }
    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            switch(mt_rand(0, 2)){
                case 0:
                    return [Item::get(Item::FLINT, 0, 1)];
                case 1:
                    return [Item::get(Item::GUNPOWDER, 0, 1)];
                case 2:
                    return [Item::get(Item::REDSTONE_DUST, 0, 1)];
            }
        }
        return [];
    }

}