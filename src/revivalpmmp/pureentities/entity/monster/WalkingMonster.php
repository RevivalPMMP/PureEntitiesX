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

namespace revivalpmmp\pureentities\entity\monster;

use pocketmine\entity\Creature;
use revivalpmmp\pureentities\entity\monster\walking\Enderman;
use revivalpmmp\pureentities\entity\WalkingEntity;
use pocketmine\block\Water;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

abstract class WalkingMonster extends WalkingEntity implements Monster{

    protected $attackDelay = 0;

    private $minDamage = [0, 0, 0, 0];
    private $maxDamage = [0, 0, 0, 0];

    protected $attackDistance = 2; // distance of blocks when attack can be started

    public abstract function attackEntity(Entity $player);

    public function checkTarget() {
        // breeding implementation (as only walking entities can breed atm)
        if ($this instanceof IntfTameable) {
            if ($this->isTamed()) { // breeding extension only applies to tamed monsters
                if ($this instanceof IntfCanBreed && $this->getBreedingExtension() !== null) {
                    if (!$this->getBreedingExtension()->checkInLove()) { // when the entity is NOT in love, but tamed, it should follow the player!!!
                        $target = $this->getBaseTarget();
                        if (!$target instanceof Monster or !$target->isAlive()) {
                            // set target to owner ...
                            $player = $this->getOwner();
                            if ($player !== null and $player->isOnline()) {
                                $this->setBaseTarget($player);
                            }
                        }
                    }
                    // tick the breedable class embedded
                    $this->getBreedingExtension()->tick();
                }
            }
        }

        return parent::checkTarget();
    }

    /**
     * @param Creature $creature
     * @param float $distance
     * @return bool
     */
    public function targetOption(Creature $creature, float $distance) : bool {
        $targetOption = false;

        if ($this->isFriendly()) {
            if ($creature instanceof Player) { // a player requests the target option
                if ($creature != null and $creature->getInventory() != null) { // sometimes, we get null on getInventory?! F**k
                    $itemInHand = $creature->getInventory()->getItemInHand()->getId();
                    if ($this instanceof IntfTameable) {
                        PureEntities::logOutput("WalkingMonster($this): targetOption(): is instanceof IntfTameable", PureEntities::DEBUG);
                        $tameFood = $this->getTameFoods();
                        if (!$this->isTamed() and in_array($itemInHand, $tameFood) and $distance <= PluginConfiguration::getInstance()->getMaxInteractDistance()) {
                            PureEntities::logOutput("WalkingMonster($this): targetOption(): not tamed. Displaying 'tame' button", PureEntities::DEBUG);
                            InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_TAME, $creature, $this);
                            $targetOption = true;
                        } else if ($this instanceof IntfCanBreed) {
                            PureEntities::logOutput("WalkingMonster($this): targetOption(): is instanceof IntfCanBreed. Continue with check.", PureEntities::DEBUG);
                            if ($this->isTamed()) { // tamed - it can breed!!!
                                PureEntities::logOutput("WalkingMonster($this): targetOption(): is tamed. Continue with check.", PureEntities::DEBUG);
                                $feedableItems = $this->getFeedableItems();
                                $hasFeedableItemsInHand = in_array($itemInHand, $feedableItems);
                                if ($hasFeedableItemsInHand) {
                                    PureEntities::logOutput("WalkingMonster($this): targetOption(): has feedable items in hand.", PureEntities::DEBUG);
                                    if ($distance <= PluginConfiguration::getInstance()->getMaxInteractDistance()) { // we can feed a sheep! and it makes no difference if it's an adult or a baby ...
                                        InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_FEED, $creature, $this);
                                    }
                                    // check if the entity is able to follow - but only on a distance of 6 blocks
                                    $targetOption = $creature->spawned && $creature->isAlive() && !$creature->closed && $distance <= PluginConfiguration::getInstance()->getMaxInteractDistance();
                                    PureEntities::logOutput("WalkingMonster($this): targetOption is $targetOption and distance is $distance", PureEntities::DEBUG);
                                } else if (!$hasFeedableItemsInHand) { // when no feedable things are in hand - we need to sit down the entity
                                    PureEntities::logOutput("WalkingMonster($this): targetOption(): has no feedable items in hand. Display sit (when distance is ok)", PureEntities::DEBUG);
                                    if ($distance <= PluginConfiguration::getInstance()->getMaxInteractDistance()) {
                                        InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_SIT, $creature, $this);
                                        PureEntities::logOutput("WalkingMonster($this): has no feedable items in hand. Display sit", PureEntities::DEBUG);
                                    } else {
                                        PureEntities::logOutput("WalkingMonster($this): targetOption(): Distance was not ok for sit command.", PureEntities::DEBUG);
                                        InteractionHelper::displayButtonText("", $creature, $this);
                                    }
                                } else {
                                    PureEntities::logOutput("WalkingMonster($this): targetOption(): Nothing applies. Display 'empty' button.", PureEntities::DEBUG);
                                    InteractionHelper::displayButtonText("", $creature, $this);
                                    // reset base target when it was player before (follow by holding wheat)
                                    if ($this->isFollowingPlayer($creature)) { // we've to reset follow when there's nothing interesting in hand
                                        // reset base target!
                                        $this->setBaseTarget($this->getBreedingExtension()->getBreedPartner()); // reset base target to breed partner (or NULL, if there's none)
                                    }
                                }
                            } else { // not tamed - so feeding is not possible, also sit is not possible!
                                PureEntities::logOutput("WalkingMonster($this): targetOption(): Not tamed. So cannot feed or sit.", PureEntities::DEBUG);
                                InteractionHelper::displayButtonText("", $creature, $this);
                            }
                        } else {
                            PureEntities::logOutput("WalkingMonster($this): targetOption(): Is not IntfCanBreed.", PureEntities::DEBUG);
                            InteractionHelper::displayButtonText("", $creature, $this);
                        }
                    } else {
                        PureEntities::logOutput("WalkingMonster($this): targetOption(): Not tameable. Displaying nothing.", PureEntities::DEBUG);
                        InteractionHelper::displayButtonText("", $creature, $this);
                    }

                }
            }
        } else {
            // when the entity is not friendly, it attacks the player!!!
            $targetOption = ($this instanceof Monster && (!($creature instanceof Player) || ($creature->isSurvival() && $creature->spawned)) && $creature->isAlive() && !$creature->closed && $distance <= 81);
        }
        return $targetOption;
    }

    public function getDamage(int $difficulty = null) : float{
        return mt_rand($this->getMinDamage($difficulty), $this->getMaxDamage($difficulty));
    }

    public function getMinDamage(int $difficulty = null) : float{
        if($difficulty === null or !is_numeric($difficulty) || $difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        return $this->minDamage[$difficulty];
    }

    public function getMaxDamage(int $difficulty = null) : float{
        if($difficulty === null or !is_numeric($difficulty) || $difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        return $this->maxDamage[$difficulty];
    }

    /**
     * @param float|float[] $damage
     * @param int $difficulty
     */
    public function setDamage($damage, int $difficulty = null){
        if(is_array($damage)){
            for($i = 0; $i < 4; $i++){
                $this->minDamage[$i] = $damage[$i];
                $this->maxDamage[$i] = $damage[$i];
            }
            return;
        }elseif($difficulty === null){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        if($difficulty >= 1 && $difficulty <= 3){
            $this->minDamage[$difficulty] = $damage[$difficulty];
            $this->maxDamage[$difficulty] = $damage[$difficulty];
        }
    }

    public function setMinDamage($damage, int $difficulty = null){
        if(is_array($damage)){
            for($i = 0; $i < 4; $i++){
                $this->minDamage[$i] = min($damage[$i], $this->getMaxDamage($i));
            }
            return;
        }elseif($difficulty === null){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        if($difficulty >= 1 && $difficulty <= 3){
            $this->minDamage[$difficulty] = min((float) $damage, $this->getMaxDamage($difficulty));
        }
    }

    public function setMaxDamage($damage, int $difficulty = null){
        if(is_array($damage)){
            for($i = 0; $i < 4; $i++){
                $this->maxDamage[$i] = max((int) $damage[$i], $this->getMaxDamage($i));
            }
            return;
        }elseif($difficulty === null){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        if($difficulty >= 1 && $difficulty <= 3){
            $this->maxDamage[$difficulty] = max((int) $damage, $this->getMaxDamage($difficulty));
        }
    }

    public function onUpdate($currentTick){
        if($this->server->getDifficulty() < 1){
            $this->close();
            return false;
        }

        if(!$this->isAlive()){
            if(++$this->deadTicks >= 23){
                $this->close();
                return false;
            }
            return true;
        }

        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
        $this->entityBaseTick($tickDiff);

        $target = $this->updateMove($tickDiff);
        if($this->isFriendly()){
            PureEntities::logOutput("$this: isFriendly", PureEntities::DEBUG);
            if(!($target instanceof Player)){
                if($target instanceof Entity && $target->distanceSquared($this) <= $this->attackDistance){
                    $this->attackEntity($target);
                }elseif(
                    $target instanceof Vector3
                    &&(($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2) <= 1
                ){
                    $this->moveTime = 0;
                }
            }
        }else{
            PureEntities::logOutput("$this: is NOT Friendly and target is $target", PureEntities::DEBUG);
            if($target instanceof Entity  && $target->distanceSquared($this) <= $this->attackDistance) {
                $this->attackEntity($target);
            }elseif(
                $target instanceof Vector3
                &&(($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2) <= 1
            ){
                $this->moveTime = 0;
            }
        }
        return true;
    }

    public function entityBaseTick($tickDiff = 1, $EnchantL = 0){
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $this->attackDelay += $tickDiff;
        if($this instanceof Enderman){
            if($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) $this->y, Math::floorFloat($this->z))) instanceof Water){
                $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
                $this->attack($ev->getFinalDamage(), $ev);
                $this->move(mt_rand(-20, 20), mt_rand(-20, 20), mt_rand(-20, 20));
            }
        }else{
            if(!$this->hasEffect(Effect::WATER_BREATHING) && $this->isInsideOfWater()){
                $hasUpdate = true;
                $airTicks = $this->getDataProperty(self::DATA_AIR) - $tickDiff;
                if($airTicks <= -20){
                    $airTicks = 0;
                    $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
                    $this->attack($ev->getFinalDamage(), $ev);
                }
                $this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, $airTicks);
            }else{
                $this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, 300);
            }
        }

        Timings::$timerEntityBaseTick->stopTiming();
        return $hasUpdate;
    }

}
