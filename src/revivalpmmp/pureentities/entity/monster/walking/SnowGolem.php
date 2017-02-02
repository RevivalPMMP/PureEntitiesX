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

use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\entity\ProjectileSource;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\Player;
use pocketmine\entity\Creature;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\data\Data;

class SnowGolem extends WalkingMonster implements ProjectileSource {
    const NETWORK_ID = Data::SNOW_GOLEM;

    public $width = 0.6;
    public $height = 1.8;

    const NBT_KEY_PUMPKIN       = "Pumpkin"; // 1 or 0 (true/false) - hat on or off ;)

    public function initEntity(){
        parent::initEntity();

        $this->setFriendly(true);
        $this->setSheared($this->isSheared()); // set data from NBT
    }

    public function getName(){
        return "SnowGolem";
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if ($creature instanceof Player) {
            if ($creature != null and $creature->getInventory() != null) { // sometimes, we get null on getInventory?! F**k
                if ($creature->getInventory()->getItemInHand()->getId() === Item::SHEARS && $distance <= PluginConfiguration::getInstance()->getMaxInteractDistance() && !$this->isSheared())  {
                    InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_SHEAR, $creature, $this);
                } else {
                    InteractionHelper::displayButtonText("", $creature, $this);
                }
            }
        } else {
            return !($creature instanceof Player) && $creature->isAlive() && $distance <= 60;
        }
        return false;
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 23  && mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 55){
            $this->attackDelay = 0;

            $f = 1.2;
            $yaw = $this->yaw + mt_rand(-220, 220) / 10;
            $pitch = $this->pitch + mt_rand(-120, 120) / 10;
            $nbt = new CompoundTag("", [
                "Pos" => new ListTag("Pos", [
                    new DoubleTag("", $this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5)),
                    new DoubleTag("", $this->y + 1),
                    new DoubleTag("", $this->z +(cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5))
                ]),
                "Motion" => new ListTag("Motion", [
                    new DoubleTag("", -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI)),
                    new DoubleTag("", -sin($pitch / 180 * M_PI)),
                    new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI))
                ]),
                "Rotation" => new ListTag("Rotation", [
                    new FloatTag("", $yaw),
                    new FloatTag("", $pitch)
                ]),
            ]);

            /** @var Projectile $snowball */
            $snowball = Entity::createEntity("Snowball", $this->chunk, $nbt, $this);
            $snowball->setMotion($snowball->getMotion()->multiply($f));

            $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($snowball));
            if($launch->isCancelled()){
                $snowball->kill();
            }else{
                $snowball->spawnToAll();
                $this->level->addSound(new LaunchSound($this), $this->getViewers());
            }
        }
    }

    public function getDrops(){
        return [Item::get(Item::SNOWBALL, 0, mt_rand(0, 15))];
    }

    public function getMaxHealth() {
        return 4;
    }

    // ------------------------------------------------------------------------------------------------------------------------
    // functions for snowgolem
    // ------------------------------------------------------------------------------------------------------------------------

    /**
     * Sets the snowgolem sheared. This means, he looses his pumpkin and shows face
     */
    public function setSheared (bool $sheared) {
        $this->namedtag->Pumpkin = new IntTag(self::NBT_KEY_PUMPKIN, $sheared ? 0 : 1); // set pumpkin in NBT
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SHEARED, $sheared); // set pumpkin on/off (sheared?)
    }

    public function isSheared () : bool {
        if(!isset($this->namedtag->Pumpkin)){
            $this->namedtag->Pumpkin = new IntTag(self::NBT_KEY_PUMPKIN, 1); // default: has pumpkin on his head (1 - pumpkin on head, 0 - pumpkin off!)
        }
        return $this->namedtag[self::NBT_KEY_PUMPKIN] === 0;
    }

}
