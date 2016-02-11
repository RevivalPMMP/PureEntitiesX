<?php

namespace milk\entitymanager\task;

use milk\entitymanager\EntityManager;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class EntitySpawnerTask extends Task{

    public function onRun($currentTicks){
        /** @var EntityManager $owner */
        $owner = Server::getInstance()->getPluginManager()->getPlugin("EntityManager");
        $rand = explode("/", $owner->getData("spawner.rand", "1/4"));
        foreach(EntityManager::$spawner as $key => $data){
            if(mt_rand(...$rand) > $rand[0]){
                continue;
            }

            if(count($data["mob-list"]) === 0){
                unset(EntityManager::$spawner[$key]);
                continue;
            }

            $radius = (int) $data["radius"];
            $pos = Position::fromObject(new Vector3(...($vec = explode(":", $key))), ($k = Server::getInstance()->getLevelByName((string) array_pop($vec))) == null ? Server::getInstance()->getDefaultLevel() : $k);
            $pos->y = $pos->getLevel()->getHighestBlockAt($pos->x += mt_rand(-$radius, $radius), $pos->z += mt_rand(-$radius, $radius));
            EntityManager::create($data["mob-list"][mt_rand(0, count($data["mob-list"]) - 1)], $pos);
        }
    }

}