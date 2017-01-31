<?php

namespace revivalpmmp\pureentities\entity\monster\walking;

use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

class Wolf extends WalkingMonster{
    const NETWORK_ID = 14;

    public $width = 0.72;
    public $height = 0.9;

    const RED = 14;

    const NBT_KEY_COLLAR_COLOR  = "CollarColor"; // 0 -14 (14 - RED)
    const NBT_KEY_OWNER_UUID    = "OwnerUUID"; // string
    const NBT_KEY_SITTING       = "Sitting"; // 1 or 0 (true/false)
    const NBT_KEY_ANGRY         = "Angry"; // 0 - not angry, > 0 angry

    public function getSpeed() : float{
        return 1.2;
    }

    public function initEntity(){
        parent::initEntity();

        $this->fireProof = false;
        $this->setDamage([0, 3, 4, 6]);

        $this->setAngry($this->isAngry());
        $this->setTamed($this->isTamed());
        $this->setSitting($this->isSitting());
        if ($this->isTamed()) {
            $player = $this->getOwner();
            if ($player !== null) {
                $this->setOwner($player);
            } else {
                PureEntities::logOutput("Wolf($this): is tamed but player not online. Cannot set tamed owner. Will be set when player logs in ..", PureEntities::NORM);
            }
        }
    }

    public function getName(){
        return "Wolf";
    }

    public function isAngry() : bool{
        if (!isset($this->namedtag->Angry)) {
            $this->namedtag->Angry = new IntTag(self::NBT_KEY_ANGRY, 0); // set not angry
        }
        return $this->namedtag[self::NBT_KEY_ANGRY] > 0;
    }

    public function setAngry(int $val){
        $this->namedtag->Angry = new IntTag(self::NBT_KEY_ANGRY, $val);
    }

    public function attack($damage, EntityDamageEvent $source){
        parent::attack($damage, $source);

        if(!$source->isCancelled()){
            $this->setAngry(1000);
        }
    }

    public function targetOption(Creature $creature, float $distance) : bool {
        if ($this->isAngry()) { // cannot be tamed
            return parent::targetOption($creature, $distance);
        } else { // not angry - can be tamed
            if ($creature instanceof Player) {
                if ($creature != null and $creature->getInventory() != null and $distance <= PluginConfiguration::getInstance()->getMaxInteractDistance()) { // sometimes, we get null on getInventory?! F**k
                    $itemInHand = $creature->getInventory()->getItemInHand()->getId();
                    if ($itemInHand === Item::BONE and !$this->isTamed()) { // when a wolf is already tamed, we don't display "tame" button
                        InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_TAME, $creature, $this);
                        return true;
                    } else if ($this->isTamed()) { // we can make the wolf sit (but only when it's tamed!)
                        InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_SIT, $creature, $this);
                    } else {
                        InteractionHelper::displayButtonText("", $creature, $this);
                    }
                } else {
                    InteractionHelper::displayButtonText("", $creature, $this);
                }
            }
        }
        return false;
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.6){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        return [];
    }

    public function getMaxHealth() {
        return 8; // but only for wild ones, tamed ones: 20
    }


    // -----------------------------------------------------------------------------------------------
    // TAMING functionality
    // -----------------------------------------------------------------------------------------------
    /**
     * Call this method when a player tries to tame an entity
     *
     * @param Player $player
     * @return bool
     */
    public function tame (Player $player) : bool {
        $tameSuccess = mt_rand(0, 2) === 0; // 1/3 chance of taiming succeeds
        $itemInHand = $player->getInventory()->getItemInHand();
        if ($itemInHand != null) {
            $player->getInventory()->getItemInHand()->setCount($itemInHand->getCount() - 1);
        }
        if ($tameSuccess) {
            $pk = new EntityEventPacket();
            $pk->eid = $this->getId();
            $pk->event = EntityEventPacket::TAME_SUCCESS; // this "plays" success animation on entity
            $player->dataPacket($pk);

            // set the properties accordingly
            $this->setTamed(true);
            $this->setOwner($player);

        } else {
            $pk = new EntityEventPacket();
            $pk->eid = $this->getId();
            $pk->event = EntityEventPacket::TAME_FAIL; // this "plays" fail animation on entity
            $player->dataPacket($pk);
            // reduce bones in hand ...
        }
        return $tameSuccess;
    }

    /**
     * Sets this entity tamed and belonging to the player
     *
     * @param bool $tamed
     */
    public function setTamed (bool $tamed) {
        if ($tamed) {
            $this->namedtag->CollarColor = new ByteTag(self::NBT_KEY_COLLAR_COLOR, self::RED); // set collar color
            $this->setDataProperty(self::DATA_COLOUR, self::DATA_TYPE_BYTE, self::RED); // collar color RED (because it's tamed!)
            $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_TAMED, true); // set tamed
        }
    }

    /**
     * Only returns true when this entity is tamed and owned by a player (who is not necessary online!)
     *
     * @return bool
     */
    public function isTamed () : bool {
        return isset($this->namedtag->OwnerUUID);
    }

    /**
     * Returns the owner of this entity. When isTamed is true and this method returns NULL the player is offline!
     *
     * @return null|Player
     */
    public function getOwner () {
        /** @var Player $player */
        $player = null;
        if (isset($this->namedtag->OwnerUUID)) {
            foreach ($this->getLevel()->getPlayers() as $levelPlayer) {
                if (strcmp($levelPlayer->getUniqueId()->toString(), $this->namedtag->OwnerUUID) == 0) {
                    $player = $levelPlayer;
                    break;
                }
            }
        }
        return $player;
    }

    /**
     * Sets the owner of the wolf
     *
     * @param Player $player
     */
    public function setOwner (Player $player) {
        $this->namedtag->OwnerUUID = new StringTag(self::NBT_KEY_OWNER_UUID, $player->getUniqueId()->toString()); // set owner UUID
        $this->setDataProperty(self::DATA_OWNER_EID, self::DATA_TYPE_LONG, $player->getId()); // set owner entity id
        $this->baseTarget = $player;
    }

    /**
     * Set the wolf sitting or not
     * @param bool $sit
     */
    public function setSitting (bool $sit) {
        $this->namedtag->Sitting = new IntTag(self::NBT_KEY_SITTING, $sit ? 1 : 0);
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SITTING, $sit);
    }

    /**
     * Returns if the wolf is sitting or not
     *
     * @return bool
     */
    public function isSitting () : bool {
        if (!isset($this->namedtag->Sitting)) {
            $this->namedtag->Sitting = new ByteTag(self::NBT_KEY_SITTING, 0); // set not sitting (by default)
        }
        return $this->namedtag[self::NBT_KEY_SITTING] === 1;
    }

    /**
     * We've to override this!
     *
     * @return bool
     */
    public function isFriendly() : bool{
        return !$this->isAngry();
    }

}
