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

namespace revivalpmmp\pureentities\entity\projectile;

use pocketmine\level\Level;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\entity\Projectile;
use pocketmine\entity\Entity;
use pocketmine\level\Explosion;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\event\ExplosionPrimeEvent;

class FireBall extends Projectile {
    const NETWORK_ID = Data::FIRE_BALL;

    public $width = 0.5;
    public $height = 0.5;

    protected $damage = 4;

    protected $drag = 0.01;
    protected $gravity = 0.05;

    protected $isCritical;
    protected $canExplode = false;

    public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null, bool $critical = false) {
        parent::__construct($level, $nbt, $shootingEntity);

        $this->isCritical = $critical;
    }

    public function isExplode(): bool {
        return $this->canExplode;
    }

    public function setExplode(bool $bool) {
        $this->canExplode = $bool;
    }

    public function onUpdate($currentTick) {
        if ($this->closed) {
            return false;
        }

        $this->timings->startTiming();

        $hasUpdate = parent::onUpdate($currentTick);

        if (!$this->hadCollision and $this->isCritical) {
            $this->level->addParticle(new CriticalParticle($this->add(
                $this->width / 2 + mt_rand(-100, 100) / 500,
                $this->height / 2 + mt_rand(-100, 100) / 500,
                $this->width / 2 + mt_rand(-100, 100) / 500)));
        } elseif ($this->onGround) {
            $this->isCritical = false;
        }

        if ($this->age > 1200 or $this->isCollided) {
            if ($this->isCollided and $this->canExplode) {
                $this->server->getPluginManager()->callEvent($ev = new ExplosionPrimeEvent($this, 2.8));
                if (!$ev->isCancelled()) {
                    $explosion = new Explosion($this, $ev->getForce(), $this->shootingEntity);
                    if ($ev->isBlockBreaking()) {
                        $explosion->explodeA();
                    }
                    $explosion->explodeB();
                }
            }
            $this->kill();
            $hasUpdate = true;
        }

        $this->timings->stopTiming();
        return $hasUpdate;
    }

    public function spawnTo(Player $player) {
        $pk = new AddEntityPacket();
        $pk->type = self::NETWORK_ID;
        $pk->entityRuntimeId = $this->getId();
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = $this->motionX;
        $pk->speedY = $this->motionY;
        $pk->speedZ = $this->motionZ;
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);

        parent::spawnTo($player);
    }

}
