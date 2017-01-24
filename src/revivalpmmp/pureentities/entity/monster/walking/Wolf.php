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

    private $angry = 0;

    public $width = 0.72;
    public $height = 0.9;

    const RED = 14;

    const NBT_KEY_COLLAR_COLOR  = "CollarColor"; // 0 -14 (14 - RED)
    const NBT_KEY_OWNER_UUID    = "OwnerUUID"; // string
    const NBT_KEY_SITTING       = "Sitting"; // 1 or 0 (true/false)

    public function getSpeed() : float{
        return 1.2;
    }

    public function initEntity(){
        parent::initEntity();

        if(isset($this->namedtag->Angry)){
            $this->angry = (int) $this->namedtag["Angry"];
        }

        $this->fireProof = false;
        $this->setDamage([0, 3, 4, 6]);
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->Angry = new IntTag("Angry", $this->angry);
    }

    public function getName(){
        return "Wolf";
    }

    public function isAngry() : bool{
        return $this->angry > 0;
    }

    public function setAngry(int $val){
        $this->angry = $val;
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
                if ($creature != null and $creature->getInventory() != null) { // sometimes, we get null on getInventory?! F**k
                    if ($creature->getInventory()->getItemInHand()->getId() === Item::BONE) {
                        if ($distance <= PluginConfiguration::getInstance()->getMaxInteractDistance()) { // we can feed a sheep! and it makes no difference if it's an adult or a baby ...
                            InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_TAME, $creature, $this);
                            return true;
                        }
                    } else {
                        InteractionHelper::displayButtonText("", $creature, $this);
                    }
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
            $this->setTamed($player);

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
    public function setTamed (Player $player) {
        $this->namedtag->CollarColor = new ByteTag(self::NBT_KEY_COLLAR_COLOR, self::RED); // set collar color
        $this->namedtag->OwnerUUID   = new StringTag(self::NBT_KEY_OWNER_UUID, $player->getUniqueId()); // set owner UUID
        $this->namedtag->Sitting     = new IntTag(self::NBT_KEY_SITTING, 1); // set sitting in NBT

        // set data properties
        $this->setDataProperty(self::DATA_COLOUR, self::DATA_TYPE_BYTE, self::RED);
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SITTING, true); // set sitting after tamed
    }

    public function isTamed () {

    }

}
