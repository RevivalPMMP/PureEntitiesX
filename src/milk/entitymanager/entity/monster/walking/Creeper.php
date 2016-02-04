<?php

namespace milk\entitymanager\entity\monster\walking;

use milk\entitymanager\entity\monster\WalkingMonster;
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
    public $eyeHeight = 1.62;

    private $bombTime = 0;

    public function getSpeed() : float{
        return 0.9;
    }

    public function initEntity(){
        if(isset($this->namedtag->BombTime)){
            $this->bombTime = (int) $this->namedtag["BombTime"];
        }
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        parent::initEntity();
    }

    public function saveNBT(){
        $this->namedtag->BombTime = new IntTag("BombTime", $this->bombTime);
        parent::saveNBT();
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
            if($this->bombTime > 0) $this->bombTime -= min(2, $this->bombTime);
        }else{
            $this->bombTime++;
            if($this->bombTime >= mt_rand(55, 70)) $this->explode();
        }
    }

    public function getDrops(){
        $drops = [];
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            switch(mt_rand(0, 2)){
                case 0 :
                    $drops[] = Item::get(Item::FLINT, 0, 1);
                    break;
                case 1 :
                    $drops[] = Item::get(Item::GUNPOWDER, 0, 1);
                    break;
                case 2 :
                    $drops[] = Item::get(Item::REDSTONE_DUST, 0, 1);
                    break;
            }
        }
        return $drops;
    }

}