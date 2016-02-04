<?php

namespace milk\entitymanager\entity\animal\walking;

use milk\entitymanager\entity\animal\WalkingAnimal;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\entity\Creature;

class Ocelot extends WalkingAnimal{
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
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->Angry = new IntTag("Angry", $this->angry);
    }

    public function getName() : string{
        return "Ocelot";
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if($creature instanceof Player){
            return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::RAW_FISH && $distance <= 49;
        }
        return false;
    }

    public function getDrops(){
        return [];
    }
}
