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

namespace revivalpmmp\pureentities\entity\animal;

use pocketmine\entity\Creature;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\entity\WalkingEntity;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\math\Vector3;
use pocketmine\Player;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfFeedable;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

abstract class WalkingAnimal extends WalkingEntity implements Animal {

    // for eating grass etc. pp
    protected $blockInterestTime = 0;

    public function getSpeed(): float {
        return 0.7;
    }

    public function entityBaseTick(int $tickDiff = 1): bool {
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if (!$this->hasEffect(Effect::WATER_BREATHING) && $this->isInsideOfWater()) {
            $hasUpdate = true;
            $airTicks = $this->getDataProperty(self::DATA_AIR) - $tickDiff;
            if ($airTicks <= -20) {
                $airTicks = 0;
                $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
                $this->attack($ev);
            }
            $this->setDataProperty(Entity::DATA_AIR, Entity::DATA_TYPE_SHORT, $airTicks);
        } else {
            $this->setDataProperty(Entity::DATA_AIR, Entity::DATA_TYPE_SHORT, 300);
        }

        // tick the breeding extension if it's available
        if ($this instanceof IntfCanBreed && $this->getBreedingComponent() !== null) {
            // we should also check for any blocks of interest for the entity
            $this->getBreedingComponent()->checkInLove();
            // tick the breedable class embedded
            $this->getBreedingComponent()->tick();
        }

        Timings::$timerEntityBaseTick->stopTiming();
        return $hasUpdate;
    }

    /**
     * This method is called from the server framework for each entity. This is our main
     * entry point when it comes to tracing how all that stuff works. With each server
     * tick each entity is ticked by calling this entry method.
     *
     * @param $currentTick
     * @return bool
     */
    public function onUpdate(int $currentTick): bool {
        if (!$this->isAlive()) {
            if (++$this->deadTicks >= 23) {
                $this->close();
                return false;
            }
            return true;
        }

        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
        $this->entityBaseTick($tickDiff);

        $target = $this->updateMove($tickDiff);
        if ($target instanceof Player) {
            if ($this->distance($target) <= 2) {
                $this->pitch = 22; // pitch is the angle for looking up or down while yaw is looking left/right
                $this->x = $this->lastX;
                $this->y = $this->lastY;
                $this->z = $this->lastZ;
            }
        } elseif (
            $target instanceof Vector3
            && $this->distanceSquared($target) <= 1
        ) {
            $this->moveTime = 0;
        }
        return true;
    }

    /**
     * Does the check for interesting blocks and sets the baseTarget if an interesting block is found
     */
    protected function getCurrentBlock() {
        $block = null;
        // no creature is the target, so we can check if there's any interesting block for the entity
        if ($this->blockInterestTime > 0) { // we take a look at interesting blocks only each 300 ticks!
            $this->blockInterestTime--;
        } else { // it's time to check for any interesting block the entity is on
            $this->blockInterestTime = PluginConfiguration::getInstance()->getBlockOfInterestTicks();
            $temporalVector = new Vector3($this->x, $this->y - $this->height / 2, $this->z);
            $block = $this->level->getBlock($temporalVector);
        }
        return $block;
    }


    /**
     * Returns all blocks around in a flat way - meaning, there is no search in y axis, only what the entity provides
     * with it's y property.
     *
     * @param int $range the range in blocks
     * @return array an array of Block
     */
    protected function getBlocksFlatAround(int $range) {
        if ($this instanceof BaseEntity) {
            $blocksAround = [];

            $minX = $this->x - $range;
            $maxX = $this->x + $range;
            $minZ = $this->z - $range;
            $maxZ = $this->z + $range;
            $temporalVector = new Vector3($this->x, $this->y - $this->height / 2, $this->z);

            for ($x = $minX; $x <= $maxX; $x++) {
                for ($z = $minZ; $z <= $maxZ; $z++) {
                    $blocksAround[] = $this->level->getBlock($temporalVector->setComponents($x, $temporalVector->y, $this->z));
                }
            }

            return $blocksAround;
        }
        return [];
    }

    /**
     * @param Creature $creature
     * @param float $distance
     * @return bool
     */
    public function targetOption(Creature $creature, float $distance): bool {
        $targetOption = false;
        if ($this instanceof IntfCanBreed || $this instanceof IntfFeedable) {
            if ($creature != null and $creature instanceof Player) { // a player requests the target option
                if ($creature->getInventory() != null) { // sometimes, we get null on getInventory?! F**k
                    $feedableItems = $this->getFeedableItems();
                    if (in_array($creature->getInventory()->getItemInHand()->getId(), $feedableItems)) {
                        // check if the sheep is able to follow - but only on a distance of 6 blocks
                        $targetOption = $creature->spawned && $creature->isAlive() && !$creature->closed && $distance <= PluginConfiguration::getInstance()->getMaxInteractDistance();
                        // sheeps only follow when <= 5 blocks away. otherwise, forget the player as target!
                        if (!$targetOption and $this->isFollowingPlayer($creature) and !$this->getBreedingComponent()->isBaby()) {
                            $this->setBaseTarget($this->getBreedingComponent()->getBreedPartner()); // reset base target to breed partner (or NULL, if there's none)
                        }
                    } else {
                        // reset base target when it was player before (follow by holding wheat)
                        if ($this->isFollowingPlayer($creature)) { // we've to reset follow when there's nothing interesting in hand
                            // reset base target!
                            $this->setBaseTarget($this->getBreedingComponent()->getBreedPartner()); // reset base target to breed partner (or NULL, if there's none)
                        }
                    }
                }
            }
        }
        return $targetOption;
    }

    /**
     * The general showButton function is implemented here for entities that are walking animals
     * and can interact with - we're working with interfaces here.
     *
     * @param Player $player
     */
    public function showButton(Player $player) {
        if ($this instanceof IntfCanBreed || $this instanceof IntfFeedable) {
            if (in_array($player->getInventory()->getItemInHand()->getId(), $this->getFeedableItems())) {
                InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_FEED, $player);
            } else {
                InteractionHelper::displayButtonText("", $player);
            }
        } else {
            InteractionHelper::displayButtonText("", $player);
        }
    }


}
