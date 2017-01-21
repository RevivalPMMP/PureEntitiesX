<?php

namespace revivalpmmp\pureentities\entity\animal;

use pocketmine\block\Block;
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
use revivalpmmp\pureentities\PureEntities;

abstract class WalkingAnimal extends WalkingEntity {

    // for eating grass etc. pp
    protected $blockInterestTime   = 0;
    const     BLOCK_INTEREST_TICKS = 300;


    public function getSpeed() : float{
        return 0.7;
    }

    public function entityBaseTick($tickDiff = 1){
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if(!$this->hasEffect(Effect::WATER_BREATHING) && $this->isInsideOfWater()){
            $hasUpdate = true;
            $airTicks = $this->getDataProperty(self::DATA_AIR) - $tickDiff;
            if($airTicks <= -20){
                $airTicks = 0;
                $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
                $this->attack($ev->getFinalDamage(), $ev);
            }
            $this->setDataProperty(Entity::DATA_AIR, Entity::DATA_TYPE_SHORT, $airTicks);
        }else{
            $this->setDataProperty(Entity::DATA_AIR, Entity::DATA_TYPE_SHORT, 300);
        }

        Timings::$timerEntityBaseTick->stopTiming();
        return $hasUpdate;
    }

    public function onUpdate($currentTick){
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
        if($target instanceof Player){
            if($this->distance($target) <= 2){
                $this->pitch = 22;
                $this->x = $this->lastX;
                $this->y = $this->lastY;
                $this->z = $this->lastZ;
            }
        }elseif(
            $target instanceof Vector3
            && $this->distance($target) <= 1
        ){
            $this->moveTime = 0;
        }
        return true;
    }

    public function checkTarget() {
        // breeding implementation (as only walking entities can breed atm)
        if ($this instanceof IntfCanBreed) {
            // we should also check for any blocks of interest for the entity
            $this->getBreedingExtension()->checkInLove();
            // tick the breedable class embedded
            $this->getBreedingExtension()->tick();
        }

        return parent::checkTarget();
    }

    /**
     * Does the check for interesting blocks and sets the baseTarget if an interesting block is found
     */
    protected function checkBlockOfInterest () {
        // no creature is the target, so we can check if there's any interesting block for the entity
        if ($this->blockInterestTime > 0) { // we take a look at interesting blocks only each 300 ticks!
            $this->blockInterestTime --;
        } else { // it's time to check for any interesting block around ...
            if ($this->baseTarget instanceof Block) { // check if we have a block target and the target is not closed. if so, we have our target!
                return;
            }
            $this->blockInterestTime = self::BLOCK_INTEREST_TICKS;
            $block = $this->isAnyBlockOfInterest($this->getBlocksFlatAround(4)); // check only 4 blocks - to spare computing time?!
            if ($block != false) {
                // we found our target let's move to it!
                $this->baseTarget = $block;
            }
        }
    }

    /**
     * Checks if this entity is following a player
     *
     * @param Creature $creature    the possible player
     * @return bool
     */
    protected function isFollowingPlayer (Creature $creature) : bool {
        return $this->baseTarget != null and $this->baseTarget instanceof Player and $this->baseTarget->getId() === $creature->getId();
    }


    /**
     * Returns all blocks around in a flat way - meaning, there is no search in y axis, only what the entity provides
     * with it's y property.
     *
     * @param int $range    the range in blocks
     * @return array an array of Block
     */
    protected function getBlocksFlatAround (int $range) {
        if ($this instanceof BaseEntity) {
            $blocksAround = [];

            $minX = $this->x - $range;
            $maxX = $this->x + $range;
            $minZ = $this->z - $range;
            $maxZ = $this->z + $range;
            $temporalVector = new Vector3($this->x, $this->y, $this->z);

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
     * Implement this for entities who have interest in blocks
     * @param Block $block  the block that has been reached
     */
    protected function blockOfInterestReached ($block) {
        // nothing important here. look e.g. Sheep.class
    }

    /**
     * @param Creature $creature
     * @param float $distance
     * @return bool
     */
    public function targetOption(Creature $creature, float $distance) : bool {
        $targetOption = false;
        if ($this instanceof IntfCanBreed || $this instanceof IntfFeedable) {
            if ($creature instanceof Player) { // a player requests the target option
                if ($creature != null and $creature->getInventory() != null) { // sometimes, we get null on getInventory?! F**k
                    $feedableItems = $this->getFeedableItems();
                    if (in_array($creature->getInventory()->getItemInHand()->getId(), $feedableItems)) {
                        if ($distance <= $this->maxInteractDistance) { // we can feed a sheep! and it makes no difference if it's an adult or a baby ...
                            PureEntities::displayButtonText(PureEntities::BUTTON_TEXT_FEED, $creature);
                        }
                        // check if the sheep is able to follow - but only on a distance of 6 blocks
                        $targetOption = $creature->spawned && $creature->isAlive() && !$creature->closed && $distance <= 6;
                        // sheeps only follow when <= 5 blocks away. otherwise, forget the player as target!
                        if (!$targetOption and $this->isFollowingPlayer($creature) and !$this->getBreedingExtension()->isBaby()) {
                            $this->baseTarget = $this->getBreedingExtension()->getBreedPartner(); // reset base target to breed partner (or NULL, if there's none)
                        }
                        PureEntities::logOutput("WalkingEntity: targetOption is $targetOption and distance is $distance", PureEntities::DEBUG);
                    } else if ($this->checkDisplayInteractiveButton($creature, $distance)) {
                        $this->stayTime = $this->interactStayTime; // let the entity wait for a couple of ticks (it's easier for targetting!)
                    } else {
                        PureEntities::displayButtonText("", $creature);
                        // reset base target when it was player before (follow by holding wheat)
                        if ($this->isFollowingPlayer($creature)) { // we've to reset follow when there's nothing interesting in hand
                            // reset base target!
                            $this->baseTarget = $this->getBreedingExtension()->getBreedPartner(); // reset base target to breed partner (or NULL, if there's none)
                        }
                    }
                }
            }
        }
        return $targetOption;
    }

    /**
     * needs to be implemented by specific entity (e.g. sheep, that can be sheared)
     *
     * @param Creature $creature
     * @param float $distance
     * @return bool
     */
    public function checkDisplayInteractiveButton (Creature $creature, float $distance) : bool {
        return false;
    }

    /**
     * @param int $tickDiff
     *
     * @return null|Vector3
     */
    public function updateMove($tickDiff){
        if ($this->baseTarget instanceof Block) {
            // check if we reached our destination. if so, set stay time and call method to signalize that
            // we reached our block target
            $distance = sqrt(pow($this->x - $this->baseTarget->x, 2) + pow($this->z - $this->baseTarget->z, 2));
            if ($distance <= 1.5) { // let's check if that is ok (1 block away ...)
                $this->blockOfInterestReached($this->baseTarget);
            }
        }
        return parent::updateMove($tickDiff);
    }


}
