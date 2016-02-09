<?php

namespace milk\entitymanager\entity\monster\jumping;

use milk\entitymanager\entity\monster\JumpingMonster;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Slime extends JumpingMonster{
    const NETWORK_ID = 37;

    public $width = 1.2;
    public $height = 1.2;

    public function getSpeed() : float{
        return 0.8;
    }

    public function getName() : string{
        return "Slime";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(4);
        $this->setDamage([0, 2, 2, 3]);
    }

    public function attackEntity(Entity $player){
        // TODO
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        //TODO
        return false;
    }
    
    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::SLIMEBALL, 0, mt_rand(0, 2))];
        }
        return [];
    }
}