<?php

namespace milk\entitymanager\task;

use milk\entitymanager\EntityManager;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class SpawnEntityTask extends PluginTask{

    public function onRun($currentTicks){
        /** @var EntityManager $owner */
        $owner = $this->owner;
        if(count(EntityManager::getEntities()) >= $owner->getData("entity.maximum")) return;
        $rand = explode("/", $owner->getData("spawn.rand"));
        foreach(EntityManager::$spawn as $key => $data){
            if(mt_rand(...$rand) > $rand[0]) continue;
            if(count($data["mob-list"]) === 0){
                unset(EntityManager::$spawn[$key]);
                continue;
            }
            $radius = (int) $data["radius"];
            $pos = Position::fromObject(new Vector3(...($vec = explode(":", $key))), ($k = Server::getInstance()->getLevelByName((string) array_pop($vec))) == null ? Server::getInstance()->getDefaultLevel() : $k);
            $pos->y = $pos->getLevel()->getHighestBlockAt($pos->x += mt_rand(-$radius, $radius), $pos->z += mt_rand(-$radius, $radius));
            EntityManager::createEntity($data["mob-list"][mt_rand(0, count($data["mob-list"]) - 1)], $pos);
        }
        if(!$owner->getData("autospawn.turn-on")) return;
        foreach($this->owner->getServer()->getOnlinePlayers() as $player){
            if(mt_rand(...$rand) > $rand[0]) continue;
            $radius = (int) $owner->getData("autospawn.radius");
            $pos = $player->getPosition();
            $pos->y = $player->level->getHighestBlockAt($pos->x += mt_rand(-$radius, $radius), $pos->z += mt_rand(-$radius, $radius));
            $ent = [
                ["Cow", "Pig", "Sheep", "Chicken", null, null],
                ["Zombie", "Creeper", "Skeleton", "Spider", "PigZombie", "Enderman"]
            ];
            EntityManager::createEntity($ent[mt_rand(0, 1)][mt_rand(0, 5)], $pos);
        }
    }

}