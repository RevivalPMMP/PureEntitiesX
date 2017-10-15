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
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\sound\ExpPickupSound;
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
                    $this->updatePlayerXp($target, $xpToGain);
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


    /**
     * This function handles updating player XP levels and progress when collecting an Xp Orb.
     *
     * @param Player $player
     * @param int $xpGained
     */
	private function updatePlayerXp(Player $player, int $xpGained) {
        $playerXpLevel = $player->getXpLevel();
        $playerXpProgress = $player->getXpProgress();

        $playerXpLevelGap = $this->getXpLevelGap($player->getXpLevel());
        $playerCurrentXp = ($playerXpProgress * $playerXpLevelGap) + $xpGained;
        $updatePlayer = true;

        while ($updatePlayer) {

            // Update internal progress counter with new Xp information
            $playerXpProgress = $playerCurrentXp / $playerXpLevelGap;

            if ($playerXpProgress >= 1) {
                $playerXpLevel++;
                $playerCurrentXp = $playerCurrentXp - $playerXpLevelGap;
                $playerXpLevelGap = $this->getXpLevelGap($player->getXpLevel());
            } else {
                $player->setXpLevel($playerXpLevel);
                $player->namedtag->XpLevel = new IntTag("XpLevel", $playerXpLevel);
                $player->setXpProgress($playerXpProgress);
                $player->namedtag->XpP = new FloatTag("XpP", $playerXpProgress);
                $updatePlayer = false;
            }
        }
    }

    /**
     * This function calculates and returns the total amount of Xp required to reach the level passed in.
     * Level values passed into this function should be positive integers or zero.
     *
     * @param int $level
     * @return int
     */
    private function calculateLevelXp(int $level = 1) : int {
        // This will return the total amount of Xp required to reach the level passed into the function.

        if ($level == 0) {
            return 0;
        } elseif ($level >= 1 and $level <= 16) {
            return (($level ** 2) + (6 * $level));
        } elseif ($level >= 17 and $level <= 31) {
            return (2.5 * ($level ** 2) - (40.5 * $level) + 360);
        } elseif ($level >= 32) {
            return (4.5 * ($level ** 2) - (162.5 * $level) + 2220);
        } else {
            PureEntities::logOutput("$this: CalculateLevelXp received invalid level $level", PureEntities::CRITICAL);
            return -1;
        }
    }

    /**
     * This is the amount of Xp required to reach the next level from the player's
     * current level without considering the player's total Xp.
     *
     * eg.  If the player is currently on Xp Level 7, then they had to have a minimum
     * of 91 Xp to reach that level.  To reach Xp Level 8, they need a total Xp of 112.
     * The difference from what is needed to reach Xp Level 8 and what is needed
     * to reach Xp Level 7 is 112 - 91 = 21.
     * So this would return 21.
     *
     * @param int $currentLevel
     * @return int
     *
     */
    private function getXpLevelGap(int $currentLevel) : int {
        return ($this->calculateLevelXp($currentLevel + 1) - $this->calculateLevelXp($currentLevel));
    }
}
