<?php

namespace milk\pureentities\entity\monster\flying;

use milk\pureentities\entity\monster\FlyingMonster;
use milk\pureentities\entity\projectile\FireBall;
use milk\pureentities\PureEntities;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\entity\ProjectileSource;
use pocketmine\level\Location;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3;

class Blaze extends FlyingMonster implements ProjectileSource{
    const NETWORK_ID = 43;

    public $width = 0.72;
    public $height = 1.8;
    public $gravity = 0.04;

    public function initEntity(){
        parent::initEntity();

        $this->fireProof = true;
        $this->setDamage([0, 0, 0, 0]);
    }

    public function getName() : string{
        return "Blaze";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 20 && mt_rand(1, 32) < 4 && $this->distance($player) <= 18){
            $this->attackDelay = 0;
        
            $f = 1.2;
            $yaw = $this->yaw + mt_rand(-220, 220) / 10;
            $pitch = $this->pitch + mt_rand(-120, 120) / 10;
            $pos = new Location(
                $this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5),
                $this->getEyeHeight(),
                $this->z +(cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5),
                $yaw,
                $pitch,
                $this->level
            );
            /** @var FireBall $fireball */
            $fireball = PureEntities::create("FireBall", $pos, $this);
            if($fireball == null){
                return;
            }

            $fireball->setExplode(true);
            $fireball->setMotion(new Vector3(
                -sin(rad2deg($yaw)) * cos(rad2deg($pitch)) * $f * $f,
                -sin(rad2deg($pitch)) * $f * $f,
                cos(rad2deg($yaw)) * cos(rad2deg($pitch)) * $f * $f
            ));

            $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($fireball));
            if($launch->isCancelled()){
                $fireball->kill();
            }else{
                $fireball->spawnToAll();
                $this->level->addSound(new LaunchSound($this), $this->getViewers());
            }
        }
    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::GLOWSTONE_DUST, 0, mt_rand(0, 2))];
        }
        return [];
    }

}
