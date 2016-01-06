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
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\Player;
use pocketmine\entity\Creature;

class SnowGolem extends Monster implements ProjectileSource{
    const NETWORK_ID = 21;

    public $width = 0.65;
    public $height = 1.2;

    public function initEntity(){
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        parent::initEntity();
        $this->setFriendly(true);
        $this->created = true;
    }

    public function getName(){
        return "SnowGolem";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 23  && mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 55){
            $this->attackDelay = 0;
        
            $f = 1.2;
            $yaw = $this->yaw + mt_rand(-220, 220) / 10;
            $pitch = $this->pitch + mt_rand(-120, 120) / 10;
            $nbt = new CompoundTag("", [
                "Pos" => new ListTag("Pos", [
                    new DoubleTag("", $this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5)),
                    new DoubleTag("", $this->y + 1),
                    new DoubleTag("", $this->z +(cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5))
                ]),
                "Motion" => new ListTag("Motion", [
                    new DoubleTag("", -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI)),
                    new DoubleTag("", -sin($pitch / 180 * M_PI)),
                    new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI))
                ]),
                "Rotation" => new ListTag("Rotation", [
                    new FloatTag("", $yaw),
                    new FloatTag("", $pitch)
                ]),
            ]);

            /** @var Projectile $arrow */
            $snowball = Entity::createEntity("Snowball", $this->chunk, $nbt, $this);
            $snowball->setMotion ( $snowball->getMotion ()->multiply ( $f ) );
            
            /* Snowball damage change */
            $property = (new \ReflectionClass ( $snowball ))->getProperty ( "damage" );
            $property->setAccessible(true);
            $property->setValue ( $snowball, 2 );
            
            $ev = new EntityShootBowEvent($this, Item::get(Item::ARROW, 0, 1), $snowball, $f);

            $this->server->getPluginManager()->callEvent($ev);

            $projectile = $ev->getProjectile();
            if($ev->isCancelled()){
                $projectile->kill();
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
                Item::get(Item::SNOWBALL, 0, 15)
            ];
        }
        return [];
    }
    
    public function targetOption(Creature $creature, $distance){
    	if(! $creature instanceof Player)
    		return $creature->isAlive() && $distance <= 60;
    	return false;
    }
}
