<?php

namespace milk\pureentities\entity\monster\flying;

use milk\pureentities\entity\animal\Animal;
use milk\pureentities\entity\BaseEntity;
use milk\pureentities\entity\monster\FlyingMonster;
use milk\pureentities\entity\projectile\FireBall;
use milk\pureentities\PureEntities;
use pocketmine\block\Liquid;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\entity\ProjectileSource;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;

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

    public function getName(){
        return "Blaze";
    }

    protected function checkTarget(){
        if($this->isKnockback()){
            return;
        }

        $target = $this->baseTarget;
        if(!($target instanceof Creature) or !$this->targetOption($target, $this->distanceSquared($target))){
            $near = PHP_INT_MAX;
            foreach ($this->getLevel()->getEntities() as $creature){
                if($creature === $this || !($creature instanceof Creature) || $creature instanceof Animal){
                    continue;
                }

                if($creature instanceof BaseEntity && $creature->isFriendly() == $this->isFriendly()){
                    continue;
                }

                if(($distance = $this->distanceSquared($creature)) > $near or !$this->targetOption($creature, $distance)){
                    continue;
                }

                $near = $distance;
                $this->baseTarget = $creature;
            }
        }

        if(
            $this->baseTarget instanceof Creature
            && $this->baseTarget->isAlive()
        ){
            return;
        }

        if($this->moveTime <= 0 or !$this->baseTarget instanceof Vector3){
            $x = mt_rand(20, 100);
            $z = mt_rand(20, 100);
            $this->moveTime = mt_rand(300, 1200);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
        }
    }

    /**
     * @param int $dx
     * @param int $dz
     *
     * @return bool
     */
    protected function checkJump($dx, $dz){
        if($this->motionY < 0){
            return false;
        }

        if($this->motionY == $this->gravity * 2){
            return $this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) $this->y, Math::floorFloat($this->z))) instanceof Liquid;
        }else if($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.8), Math::floorFloat($this->z))) instanceof Liquid){
            $this->motionY = $this->gravity * 2;
            return true;
        }

        if($this->stayTime > 0){
            return false;
        }

        $block = $this->level->getBlock($this->add($dx, 0, $dz));
        if($block instanceof Slab || $block instanceof Stair){
            $this->motionY = 0.5;
            return true;
        }
        return false;
    }

    public function updateMove($tickDiff){
        if(!$this->isMovement()){
            return null;
        }

        if($this->isKnockback()){
            $this->move($this->motionX * $tickDiff, $this->motionY * $tickDiff, $this->motionZ * $tickDiff);
            $this->updateMovement();
            return null;
        }

        $before = $this->baseTarget;
        $this->checkTarget();
        if($this->baseTarget instanceof Player or $before !== $this->baseTarget){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;

            $diff = abs($x) + abs($z);
            if($x ** 2 + $z ** 2 < 0.5){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                if($this->baseTarget instanceof Creature){
                    $this->motionX = 0;
                    $this->motionZ = 0;
                    if($this->distance($this->baseTarget) > $this->y - $this->getLevel()->getHighestBlockAt((int) $this->x, (int) $this->z)){
                        $this->motionY = $this->gravity;
                    }else{
                        $this->motionY = 0;
                    }
                }else{
                    $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                    $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
                }
            }
            $this->yaw = rad2deg(-atan2($x / $diff, $z / $diff));
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }

        $dx = $this->motionX * $tickDiff;
        $dz = $this->motionZ * $tickDiff;
        $isJump = $this->checkJump($dx, $dz);
        if($this->stayTime > 0){
            $this->stayTime -= $tickDiff;
            $this->move(0, $this->motionY * $tickDiff, 0);
        }else{
            $be = new Vector2($this->x + $dx, $this->z + $dz);
            $this->move($dx, $this->motionY * $tickDiff, $dz);
            $af = new Vector2($this->x, $this->z);

            if(($be->x != $af->x || $be->y != $af->y) && !$isJump){
                $this->moveTime -= 90 * $tickDiff;
            }
        }

        if(!$isJump){
            if($this->onGround){
                $this->motionY = 0;
            }elseif($this->motionY > -$this->gravity * 4){
                $this->motionY = -$this->gravity * 4;
            }else{
                $this->motionY -= $this->gravity;
            }
        }
        $this->updateMovement();
        return $this->baseTarget;
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
            $fireball = PureEntities::create("FireBall", $pos, $this);
            if(!($fireball instanceof FireBall)){
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