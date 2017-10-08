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

namespace revivalpmmp\pureentities\entity\animal\flying;


use pocketmine\entity\Creature;
use revivalpmmp\pureentities\entity\animal\FlyingAnimal;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\Player;
use revivalpmmp\pureentities\components\BreedingComponent;
use pocketmine\nbt\tag\IntTag;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfCanPanic;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\traits\Tameable;

class Parrot extends FlyingAnimal implements IntfTameable, IntfCanInteract {
    use Tameable;

    const NETWORK_ID = Data::PARROT;
    private $birdType; // 0 = red, 1 = blue, 2 = green, 3 = cyan, 4 = silver
    public $width = 0.5;
    public $height = 0.9;
    public $speed = 1.0;

    public function initEntity()
    {
        parent::initEntity();
        $this->fireProof = false;
        $this->tameFoods = array(
            Item::SEEDS,
            Item::BEETROOT_SEEDS,
            Item::MELON_SEEDS,
            Item::PUMPKIN_SEEDS,
            Item::WHEAT_SEEDS
        );
        $this->setBirdType(mt_rand(0,5));

        if ($this->isTamed()) {
            $this->mapOwner();
            if ($this->owner === null) {
                PureEntities::logOutput("$this: is tamed but player not online. Cannot set tamed owner. Will be set when player logs in ..", PureEntities::NORM);
            }
        }
    }

    public function loadFromNBT() {
        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            if (isset($this->namedtag->OwnerName)) {
                $this->ownerName = $this->namedtag[Data::NBT_SERVER_KEY_OWNER_NAME];
                $this->setTamed(true);
            }
            if ($this->ownerName !== null) {
                foreach ($this->getLevel()->getPlayers() as $levelPlayer) {
                    if (strcasecmp($levelPlayer->getName(), $this->namedtag->OwnerName) == 0) {
                        $this->owner = $levelPlayer;
                        break;
                    }
                }
            }
            if (isset($this->namedtag->Variant)) {
                $this->setBirdType($this->namedtag[Data::NBT_KEY_BIRDTYPE]);
            }
            if (isset($this->namedtag->Sitting)) {
                $this->setSitting($this->namedtag[Data::NBT_KEY_SITTING] === 1);

                // Until an appropriate NBT key can be attached to this, if the entity is sitting when loaded,
                // commandedToSit will be set to true so that it doesn't teleport to it's owner by accident.
                $this->setCommandedToSit($this->isSitting());
            }
        }
    }

    public function saveNBT()
    {
        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            parent::saveNBT();
            $this->namedtag->Variant = new ByteTag(Data::NBT_KEY_BIRDTYPE, $this->birdType);
            $this->namedtag->Sitting = new IntTag(Data::NBT_KEY_SITTING, $this->sitting ? 1 : 0);
            if ($this->getOwnerName() !== null) {
                $this->namedtag->OwnerName = new StringTag(Data::NBT_SERVER_KEY_OWNER_NAME, $this->getOwnerName()); // only for our own (server side)
            }
            if ($this->owner !== null) {
                $this->namedtag->OwnerUUID = new StringTag(Data::NBT_KEY_OWNER_UUID, $this->owner->getUniqueId()->toString()); // set owner UUID
            }
        }

    }

    public function getSpeed(): float {
        return $this->speed;
    }

    public function getName(): string {
        return "Parrot";
    }

    public function targetOption(Creature $creature, float $distance): bool {
        return false;
    }

    public function getDrops(): array {
        return [];
    }

    public function getMaxHealth(): int {
        return 6;
    }

    public function setBirdType(int $type) {
        $this->birdType = $type;
        $this->setDataProperty(self::DATA_VARIANT, self::DATA_TYPE_INT, $type);
    }
    private function getBirdType() {
        return $this->birdType;

    }
}