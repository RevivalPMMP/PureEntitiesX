<?php

/**
 * This class contains functionality for entities that are able to breed
 */
namespace revivalpmmp\pureentities\features;


use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\PureEntities;

class BreedingExtension {

    // ----------------------------------------
    // some useful constants
    // ----------------------------------------
    const DEFAULT_IN_LOVE_TICKS = 5000; // how long is the entity in LOVE mode by default
    const FEED_INCREASE_AGE = 600; // when an entity gets fed - how many ticks are reduced from the grow-up cycle (atm 10%)
    const SEARCH_FOR_PARTNER_DELAY = 100; // do a search for a partner every 300 ticks
    const IN_LOVE_EMIT_DELAY = 100; // emit every 100 ticks the in love animation when in love
    const AGE_TICK_DELAY = 100; // how often is the age updated ...
    const BREED_NOT_POSSIBLE_TICKS = 6000; // 5 minutes - how long can an entity not breed when breed was done

    // ----------------------------------------
    // well known NBT keys
    // ----------------------------------------
    const NBT_KEY_AGE           = "Age";            // Represents the age of the mob in ticks; when negative, the mob is a baby. When 0 or above, the mob is an adult. When above 0,
    // represents the number of ticks before this mob can breed again.
    const NBT_KEY_IN_LOVE       = "InLove";         // Number of ticks until the mob loses its breeding hearts and stops searching for a mate. 0 when not searching for a mate.
    const NBT_KEY_FORCED_AGE    = "ForcedAge";      // A value of age which will be assigned to this mob when it grows up. Incremented when a baby mob is fed.


    /**
     * This is the entity that owns this Breedable class (a reference to the Entity)
     * @var Entity
     */
    private $entity = null;

    /**
     * The partner search timer is used to not search each tick for a partner when in love
     * @var int
     */
    private $partnerSearchTimer = 0;
    /**
     * The inLoveTimer is used for displaying that "in love" (workaround) animation
     * @var int
     */
    private $inLoveTimer = 0;
    /**
     * The ageTickTimer is used for reducing / increasing age each x ticks (not each tick!)
     * @var int
     */
    private $ageTickTimer = 0;
    /**
     * The breed partner is set, when the entity found a partner
     * @var Entity
     */
    private $breedPartner = null;

    /**
     * Defines if the entity currently is breeding
     * @var bool
     */
    private $breeding = false;

    /**
     * Only for babies - the parent of the baby
     * @var Entity
     */
    private $parent = null;

    public function __construct(Entity $belongsTo) {
        $this->entity = $belongsTo;
    }

    /**
     * call this method each time, the entity's init method is called
     */
    public function init () {
        $this->setAge($this->getAge());
    }

    /**
     * Returns the breed partner as an entity instance or NULL if no breed partner set
     * TODO: add to NBT
     */
    public function getBreedPartner () {
        return $this->breedPartner;
    }

    /**
     * Sets the breed partner for the entity linked with this class
     * TODO: add to NBT
     * @param Entity $breedPartner
     */
    public function setBreedPartner ($breedPartner) {
        $this->breedPartner = $breedPartner;
        $this->entity->baseTarget = $breedPartner;
    }

    /**
     * Sets the entity currently breeding
     * TODO: add to NBT
     * @param bool $breeding
     */
    public function setBreeding (bool $breeding) {
        $this->breeding = $breeding;
    }

    /**
     * Check if the entity is currently breeding
     * TODO: add to NBT
     * @return bool
     */
    public function isBreeding () {
        return $this->breeding;
    }

    /**
     * Sets the parent when the entity is a baby
     * TODO: add to NBT
     * @param Entity $parent
     */
    public function setParent ($parent) {
        $this->parent = $parent;
    }

    public function getParent () {
        return $this->parent;
    }


    /**
     * Returns the age of the entity
     *
     * @return int
     */
    public function getAge () : int {
        if (!isset($this->entity->namedtag->Age)) {
            $this->entity->namedtag->Age = new IntTag(self::NBT_KEY_AGE, 0); // by default we have a adult entity
        }
        return $this->entity->namedtag[self::NBT_KEY_AGE];
    }

    /**
     * Sets the age of the entity. Setting this to a number lesser 0 means it's a baby and
     * it also defines how many ticks it takes to grow up to an adult (e.g.: -1000 means it takes 1000 ticks until the
     * entity is grown up - can be speed up with WEAT)
     *
     * @param int $age
     */
    public function setAge (int $age) {
        $this->entity->namedtag->Age = new IntTag(self::NBT_KEY_AGE, $age); // set baby (all under zero is baby) - this is only for testing!
        if ($age < 0) {
            $this->entity->setDataProperty(Entity::DATA_SCALE, Entity::DATA_TYPE_FLOAT, 0.5); // this is a workaround?!
        } else {
            $this->entity->setDataProperty(Entity::DATA_SCALE, Entity::DATA_TYPE_FLOAT, 1.0); // set as an adult entity
            // forget the parent and reset baseTarget immediately
            $this->setParent(null);
            $this->entity->baseTarget = null;
        }
    }

    /**
     * Returns true if the entity is a baby (age lesser 0)
     *
     * @return bool
     */
    public function isBaby(): bool {
        return $this->getAge() < 0; // this is a baby when the age is lesser 0 (0 is adult,
    }

    /**
     * completely resets the breed status for this entity
     */
    public function resetBreedStatus () {
        $this->entity->stayTime = 300; // wait 300 ticks until moving forward
        $this->setBreeding(false); // reset breeding status
        $this->setBreedPartner(null); // reset breed partner
        $this->setInLove(0); // reset in love ticker
        $this->entity->baseTarget = null; // search for a new target
        $this->setAge(self::BREED_NOT_POSSIBLE_TICKS); // 20 ticks / second (should be) - the entity cannot breed for 5 minutes
    }


    /**
     * This method is called when the entity has been fed. This makes the entity fall in love
     * for the given ticks. When an entity is in love, it searches for another partner of the same
     * species that is also in love to breed new baby entity.
     *
     * @param int $inLoveTicks
     */
    public function setInLove (int $inLoveTicks) {
        PureEntities::logOutput("Breedable(" . $this->entity . "): setInLOve ($inLoveTicks)", PureEntities::DEBUG);
        $this->entity->namedtag->InLove = new IntTag(self::NBT_KEY_IN_LOVE, $inLoveTicks); // by default we have a adult entity
        if ($this->getInLove() > 0) {
            $this->entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INLOVE, true); // set client "inlove"
        } else {
            $this->entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INLOVE, false); // send client not "in love" anymore
        }
    }

    /**
     * Returns the amount of ticks the entity is in love. <= 0 means it's not in love and
     * not actively searching for a partner.
     *
     * @return int
     */
    public function getInLove () : int {
        if (!isset($this->entity->namedtag->InLove)) {
            $this->entity->namedtag->InLove = new IntTag(self::NBT_KEY_IN_LOVE, 0);
        }
        return $this->entity->namedtag[self::NBT_KEY_IN_LOVE];
    }

    /**
     * This has to be called by checkTarget() or any other tick related method.
     *
     * @return bool
     */
    public function checkInLove () : bool {
        if ($this->getInLove() > 0) {

            // check if we are near our breeding partner. if so, set breed!
            if ($this->getBreedPartner() != null and
                $this->getBreedPartner()->getBreedingExtension()->getInLove() > 0 and
                $this->getBreedPartner()->distance($this->entity) <= 2 and
                !$this->isBreeding()) {
                $this->breed($this->getBreedPartner());
                return true;
            }


            // emit heart particles ...
            if ($this->inLoveTimer >= self::IN_LOVE_EMIT_DELAY) {
                foreach ($this->entity->getLevel()->getPlayers() as $player) { // don't know if this is the correct one :/
                    if ($player->distance($this->entity) <= 49) {
                        $pk = new EntityEventPacket();
                        $pk->eid = $this->entity->getId();
                        $pk->event = EntityEventPacket::TAME_SUCCESS; // i think this plays the "heart" animation
                        $player->dataPacket($pk);
                    }
                }
                $this->inLoveTimer = 0;
            } else {
                $this->inLoveTimer++;
            }

            // search for partner
            if ($this->partnerSearchTimer >= self::SEARCH_FOR_PARTNER_DELAY and
                $this->getBreedPartner() == null) {
                $validTarget = $this->findAnotherEntityInLove(49); // find another target within 20 blocks
                if ($validTarget != false) {
                    $this->setBreedPartner($validTarget); // now my target is my "in love" partner - this entity will move to the other entity
                    $validTarget->getBreedingExtension()->setBreedPartner($this->entity); // set the other one's breed partner to ourselves
                }
                $this->partnerSearchTimer = 0;
            } else {
                $this->partnerSearchTimer++; // we only search every 300 ticks if we find a partner
            }
            return true;
        }
        return false;
    }

    /**
     * Method that finds other entities of the same species in LOVE
     *
     * @param int $range the range (documentation says 8)
     * @return Entity | bool
     */
    private function findAnotherEntityInLove (int $range) {
        PureEntities::logOutput("Breedable(" . $this->entity . "): findAnotherEntityInLove -> entering", PureEntities::DEBUG);
        $entityFound = false;
        foreach ($this->entity->getLevel()->getEntities() as $otherEntity) {
            PureEntities::logOutput("Breedable(" . $this->entity . "): findAnotherEntityInLove, checking " .
                "[sameClass:" . (strcmp(get_class($otherEntity), get_class($this->entity)) == 0) . "] " .
                "[inLove:" . (($otherEntity instanceof Player) ? 0 : ($otherEntity->getBreedingExtension()->getInLove() > 0)) . "] " .
                "[idMatching:" . ($otherEntity->getId() != $this->entity->getId()) . "] " .
                "[breedPartner:" . (($otherEntity instanceof Player) ? "null" : ($otherEntity->getBreedingExtension()->getBreedPartner() == null)) . "]", PureEntities::DEBUG);
            if (strcmp(get_class($otherEntity), get_class($this->entity)) == 0 and // must be of the same species
                $otherEntity->distance($this->entity) <= $range and // must be in range
                $otherEntity->getBreedingExtension()->getInLove() > 0 and // must be in love
                $otherEntity->getId() != $this->entity->getId() and // should be another entity of the same type
                $otherEntity->getBreedingExtension()->getBreedPartner() == null) { // shouldn't have another breeding partner
                $entityFound = $otherEntity;
                PureEntities::logOutput("Breedable(" . $this->entity . "): findAnotherEntityInLove -> found $entityFound", PureEntities::DEBUG);
                break;
            }
        }
        return $entityFound;
    }

    /**
     * @param Entity $partner
     */
    private function breed (Entity $partner) {
        PureEntities::logOutput("Breedable(" . $this->entity . "): breed with partner $partner", PureEntities::DEBUG);
        // yeah we found ourselfes - now breed and reset target
        $this->resetBreedStatus();
        $partner->getBreedingExtension()->resetBreedStatus();
        // spawn a baby entity!
        PureEntities::getInstance()->scheduleCreatureSpawn($this->entity, $this->entity->getNetworkId(), $this->entity->getLevel(), "Animal", true, $this->entity);
    }

    /**
     * Method to increase the age for adult / baby entities
     */
    public function increaseAge () {
        if ($this->ageTickTimer >= self::AGE_TICK_DELAY) {
            PureEntities::logOutput("Breedable(" . $this->entity . "): ageTick [timer:" . $this->ageTickTimer . "]", PureEntities::DEBUG);
            if ($this->isBaby()) {
                $newAge = $this->getAge() + $this->ageTickTimer;
                if ($newAge >= 0) {
                    $newAge = self::BREED_NOT_POSSIBLE_TICKS; // cannot breed for 5 minutes ...
                }
                PureEntities::logOutput("Breedable(" . $this->entity . "): ageTick(): setting age of baby to $newAge", PureEntities::DEBUG);
                $this->setAge($newAge); // going to positive. when age reached 0 or more, it will be an adult ...
            } else if (!$this->isBaby() and $this->getAge() > 0) {
                $newAge = $this->getAge() - $this->ageTickTimer;
                if ($newAge < 0) {
                    $newAge = 0;
                }
                PureEntities::logOutput("Breedable(" . $this->entity . "): ageTick(): setting age of adult to $newAge", PureEntities::DEBUG);
                $this->setAge($newAge); // going from positive to null (because when age > 0 it cannot breed)
            }
            $this->ageTickTimer = 0;
        } else {
            $this->ageTickTimer ++;
        }
    }

    /**
     * Feed a sheep with (weat)
     * @param Player $player    the player that feeds this sheep ...
     * @return bool if feeding was successful true is returned
     */
    public function feed (Player $player) : bool {
        if ($this->getAge() > 0) {
            PureEntities::logOutput("Breedable(" . $this->entity . "): feed by player. But is not able to breed!", PureEntities::DEBUG);
            $pk = new EntityEventPacket();
            $pk->eid = $this->entity->getId();
            $pk->event = EntityEventPacket::TAME_FAIL; // this "plays" fail animation on entity
            $player->dataPacket($pk);
            return false;
        }
        PureEntities::logOutput("Breedable(" . $this->entity . "): fed by player $player", PureEntities::DEBUG);
        if ($this->isBaby()) { // when a baby gets fed with weat, it grows up a little faster
            $age = $this->getAge();
            $age += self::FEED_INCREASE_AGE;
            $this->setAge($age);
            PureEntities::logOutput("Breedable(" . $this->entity . "): fed a baby sheep. increase age by " . self::FEED_INCREASE_AGE, PureEntities::DEBUG);
        } else {
            // this makes the sheep fall in love - and search for a partner ...
            $this->setInLove(self::DEFAULT_IN_LOVE_TICKS);
            // checkTarget method recognizes the "inlove" and tries to find a partner
        }
        // reset player's button text
        $player->setDataProperty(Entity::DATA_INTERACTIVE_TAG, Entity::DATA_TYPE_STRING, "");
        return true;
    }

    /**
     * This method has to be called by the entity (see how it works in Sheep entity)
     */
    public function tick () {
        // we should also check for any blocks of interest for the entity
        $this->increaseAge();

        // for a baby force to set the baseTarget to the parent (if it's available)
        if ($this->isBaby() and
            $this->getParent() != null and
            $this->getParent()->isAlive() and
            !$this->getParent()->closed) {
            PureEntities::logOutput("Breedable(" . $this->entity . "): isBaby, setting parent to " . $this->getParent(), PureEntities::DEBUG);
            $this->entity->baseTarget = $this->getParent();
            if ($this->getParent()->distance($this->entity) <= 4) {
                $this->entity->stayTime = 100; // wait 100 ticks before moving after the parent ;)
            }
        }
    }


}