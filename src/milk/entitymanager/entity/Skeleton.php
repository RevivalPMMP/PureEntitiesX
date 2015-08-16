<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\entity\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;

class Skeleton extends Monster implements ProjectileSource{
    const NETWORK_ID = 34;

    public $width = 0.65;
    public $height = 1.8;

    public function initEntity(){
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        parent::initEntity();
        $this->created = true;
    }

    public function getName(){
        return "Skeleton";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 25 && mt_rand(1, 12) < 3 && $this->distanceSquared($player) <= 40){
            $this->attackDelay = 0;
        
            $f = 1.5;
            $yaw = $this->yaw + mt_rand(-180, 180) / 10;
            $pitch = $this->pitch + mt_rand(-90, 90) / 10;
            $nbt = new Compound("", [
                "Pos" => new Enum("Pos", [
                    new Double("", $this->x),
                    new Double("", $this->y + 1.62),
                    new Double("", $this->z)
                ]),
                "Motion" => new Enum("Motion", [
                    new Double("", -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f),
                    new Double("", -sin($pitch / 180 * M_PI) * $f),
                    new Double("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f)
                ]),
                "Rotation" => new Enum("Rotation", [
                    new Float("", $yaw),
                    new Float("", $pitch)
                ]),
            ]);

            /** @var Projectile $arrow */
            $arrow = Entity::createEntity("Arrow", $this->chunk, $nbt, $this);
            $ev = new EntityShootBowEvent($this, Item::get(Item::ARROW, 0, 1), $arrow, $f);

            $this->server->getPluginManager()->callEvent($ev);

            $projectile = $ev->getProjectile();
            if($ev->isCancelled()){
                $ev->getProjectile()->kill();
            }elseif($projectile instanceof Projectile){
                $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($projectile));
                if($launch->isCancelled()){
                    $projectile->kill();
                }else{
                    $projectile->spawnToAll();
                    $this->level->addSound(new LaunchSound($this), $this->getViewers());
                }
            }
        }
    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [
                Item::get(Item::BONE, 0, mt_rand(0, 2)),
                Item::get(Item::ARROW, 0, mt_rand(0, 3)),
            ];
        }
        return [];
    }

}
