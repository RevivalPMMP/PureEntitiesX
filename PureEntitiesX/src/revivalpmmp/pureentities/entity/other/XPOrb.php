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

namespace revivalpmmp\pureentities\entity\other;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\sound\ExpPickupSound;
use revivalpmmp\pureentities\utils\XpCalculator;

class XPOrb extends Entity {
	const NETWORK_ID = 69;

	public $width = 0.25;
	public $height = 0.25;

	protected $gravity = 0.04;
	protected $drag = 0;

	protected $experience = 0;

	protected $range = 6;

	public function initEntity(){
		parent::initEntity();
        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            if (isset($this->namedtag->Experience)) {
                $this->experience = $this->namedtag["Experience"];
            } else $this->close();
        }
	}

	public function onUpdate(int $currentTick): bool {
		if($this->isClosed()){
			return false;
		}

		$tickDiff = $currentTick - $this->lastUpdate;

		$this->lastUpdate = $currentTick;

		$this->timings->startTiming();

		$hasUpdate = $this->entityBaseTick($tickDiff);

		$this->age++;

		if($this->age > 1200){
			$this->kill();
			$this->close();
			$hasUpdate = true;
		}

		$minDistance = PHP_INT_MAX;
		$target = null;
		foreach($this->getViewers() as $p){
			if(!$p->isSpectator() and $p->isAlive()){
				if(($dist = $p->distance($this)) < $minDistance and $dist < $this->range){
					$target = $p;
					$minDistance = $dist;
				}
			}
		}

		if($target !== null){
			$moveSpeed = 0.7;
			$motX = ($target->getX() - $this->x) / 8;
			$motY = ($target->getY() + $target->getEyeHeight() - $this->y) / 8;
			$motZ = ($target->getZ() - $this->z) / 8;
			$motSqrt = sqrt($motX * $motX + $motY * $motY + $motZ * $motZ);
			$motC = 1 - $motSqrt;

			if($motC > 0){
				$motC *= $motC;
				$this->motionX = $motX / $motSqrt * $motC * $moveSpeed;
				$this->motionY = $motY / $motSqrt * $motC * $moveSpeed;
				$this->motionZ = $motZ / $motSqrt * $motC * $moveSpeed;
			}

			$this->motionY -= $this->gravity;

			if($this->checkObstruction($this->x, $this->y, $this->z)){
				$hasUpdate = true;
			}

			if($this->isInsideOfSolid()){
				$this->setPosition($target);
			}

			if($minDistance <= 1.3){
                $this->kill();
                $this->close();
                $xpToGain = $this->getExperience();
                if($xpToGain > 0){
                    if ($this->getLevel() !== null) {
                        $this->level->addSound(new ExpPickupSound($target, mt_rand(0, 1000)));
                    }
                    XpCalculator::updatePlayerXp($target, $xpToGain);
                }
                $this->timings->stopTiming();

                return $hasUpdate or !$this->onGround or abs($this->motionX) > 0.00001 or abs($this->motionY) > 0.00001 or abs($this->motionZ) > 0.00001;
			}
		}

        $this->move($this->motionX, $this->motionY, $this->motionZ);

        $this->updateMovement();

        $this->timings->stopTiming();

        return $hasUpdate or !$this->onGround or abs($this->motionX) > 0.00001 or abs($this->motionY) > 0.00001 or abs($this->motionZ) > 0.00001;
    }

	public function canCollideWith(Entity $entity): bool {
		return false;
	}

	public function setExperience($exp){
		$this->experience = $exp;
	}

	public function getExperience(){
		return $this->experience;
	}

    public function spawnTo(Player $player){
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_NO_AI, true);
        $pk = new AddEntityPacket();
        $pk->type = XPOrb::NETWORK_ID;
        $pk->entityRuntimeId = $this->getId();
        $pk->position = $this->asVector3();
        $pk->motion = $this->getMotion();
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);

		parent::spawnTo($player);
	}
}
