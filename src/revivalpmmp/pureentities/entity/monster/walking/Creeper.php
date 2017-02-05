<?php

/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2017 RevivalPMMP

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>. */

namespace revivalpmmp\pureentities\entity\monster\walking;

use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Explosive;
use revivalpmmp\pureentities\entity\monster\walking\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\math\Vector2;
use pocketmine\nbt\tag\IntTag;
use pocketmine\item\Item;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\PureEntities;

class Creeper extends WalkingMonster implements Explosive{
    const NETWORK_ID = Data::CREEPER;
    const DATA_POWERED = 19;

    public $width = 0.72;
    public $height = 1.8;

    private $bombTime = 0;

    private $explodeBlocks = false;

    public function getSpeed() : float{
        return 0.9;
    }

    public function initEntity(){
        parent::initEntity();

        if(isset($this->namedtag->IsPowered)){
            $this->setDataProperty(self::DATA_POWERED, self::DATA_TYPE_BYTE, $this->namedtag->IsPowered ? 1 : 0);
        }elseif(isset($this->namedtag->powered)){
            $this->setDataProperty(self::DATA_POWERED, self::DATA_TYPE_BYTE, $this->namedtag->powered ? 1 : 0);
        }

        if(isset($this->namedtag->BombTime)){
            $this->bombTime = (int) $this->namedtag["BombTime"];
        }

        $this->explodeBlocks = (PureEntities::getInstance()->getConfig()->getNested("creeper.block-breaking-explosion", 0) == 0 ? false : true);
    }

    public function isPowered(){
        return $this->getDataProperty(self::DATA_POWERED) == 1;
    }

    public function setPowered($value = true){
        $this->namedtag->powered = $value;
        $this->setDataProperty(self::DATA_POWERED, self::DATA_TYPE_BYTE, $value ? 1 : 0);
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->BombTime = new IntTag("BombTime", $this->bombTime);
    }

    public function getName(){
        return "Creeper";
    }

    public function explode(){
        $this->server->getPluginManager()->callEvent($ev = new ExplosionPrimeEvent($this, 2.8));

        if(!$ev->isCancelled()){
            $explosion = new Explosion($this, $ev->getForce(), $this);
            $ev->setBlockBreaking($this->explodeBlocks); // this is configuration!
            if($ev->isBlockBreaking()){
                $explosion->explodeA();
            }
            $explosion->explodeB();
            $this->close();
        }
    }

    public function onUpdate($currentTick){
        $tickDiff = $currentTick - $this->lastUpdate;

        if ($this->baseTarget !== null) {
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;

            $diff = abs($x) + abs($z);

            if ($this->baseTarget instanceof Creature && $this->baseTarget->distanceSquared($this) <= 4.5) {
                PureEntities::logOutput("Creeper($this): my target is a creature. I want to bomb now!", PureEntities::DEBUG);
                $this->bombTime += $tickDiff;
                if ($this->bombTime >= 64) {
                    PureEntities::logOutput("Creeper($this): my target is a creature. I exploooooode!", PureEntities::DEBUG);
                    $this->explode();
                    return false;
                }
            } else {
                PureEntities::logOutput("Creeper($this): my target is not a creature or too far away. Resetting bomb time!", PureEntities::DEBUG);
                $this->bombTime -= $tickDiff;
                if ($this->bombTime < 0) {
                    $this->bombTime = 0;
                }

                $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
            }
            if ($diff > 0) {
                $this->yaw = rad2deg(-atan2($x / $diff, $z / $diff));
            }
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
        }

        return parent::onUpdate($currentTick);
    }

    public function attackEntity(Entity $player){
        // the creeper doesn't attack - it simply explodes
    }

    public function getDrops(){
        return [Item::get(Item::GUNPOWDER, 0, mt_rand(0, 2))];
    }

    public function getMaxHealth() {
        return 20;
    }

}
