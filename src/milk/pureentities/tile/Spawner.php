<?php

namespace milk\pureentities\tile;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Spawnable;

class Spawner extends Spawnable{
    //TODO: This isn't implemeted yet

    public $entityId = -1;

    public function getSpawnCompound(){
        return new CompoundTag("", [
            new StringTag("id", "MobSpawner"),
            new IntTag("EntityId", $this->entityId)
        ]);
    }
}