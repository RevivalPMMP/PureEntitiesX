<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\entity\ProjectileSource;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\entity\Projectile;

class Ghast extends FlyMonster implements ProjectileSource{
    const NETWORK_ID = 41;

    public $width = 4;
    public $height = 4;

    protected $speed = 1.2;

    public function initEntity(){
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        $this->setMinDamage([0, 4, 6, 9]);
        $this->setMaxDamage([0, 4, 6, 9]);
        parent::initEntity();
        $this->created = true;
    }

    public function getName(){
        return "Ghast";
    }

	public function attackEntity(Entity $player){
        if($this->attackDelay > 30 && mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 200){
            $this->attackDelay = 0;
        
            $f = 2;
            $yaw = $this->yaw + mt_rand(-220, 220) / 10;
            $pitch = $this->pitch + mt_rand(-120, 120) / 10;
            $nbt = new CompoundTag("", [
                "Pos" => new ListTag("Pos", [
                    new DoubleTag("", $this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 2)),
                    new DoubleTag("", $this->y + 2),
                    new DoubleTag("", $this->z +(cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 2))
                ]),
                "Motion" => new ListTag("Motion", [
                    new DoubleTag("", -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI)),
                    new DoubleTag("", -sin($pitch / 180 * M_PI) * $f),
                    new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI))
                ]),
                "Rotation" => new ListTag("Rotation", [
                    new FloatTag("", $yaw),
                    new FloatTag("", $pitch)
                ]),
            ]);

            /** @var Projectile $fireball */
            $fireball = Entity::createEntity("FireBall", $this->chunk, $nbt, $this);
            if($fireball instanceof FireBall)
            	$fireball->setExplode(true);
            $fireball->setMotion ( $fireball->getMotion ()->multiply ( $f ) );
            $ev = new EntityShootBowEvent($this, Item::get(Item::ARROW, 0, 1), $fireball, $f);

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
        return [];
    }

}
