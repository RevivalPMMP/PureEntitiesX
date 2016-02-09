<?php

namespace milk\entitymanager\task;

use milk\entitymanager\EntityManager;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class AutoSpawnTask extends Task{

    public function onRun($currentTick){
        /** @var EntityManager $owner */
        $owner = Server::getInstance()->getPluginManager()->getPlugin("EntityManager");
        $rand = explode("/", $owner->getData("autospawn.rand", "1/4"));
        foreach(Server::getInstance()->getOnlinePlayers() as $player){
            if(mt_rand(...$rand) > $rand[0]){
                continue;
            }

            if(mt_rand(0, 1)){
                $ent = $owner->getData("autospawn.entities.animal", []);
            }else{
                $ent = $owner->getData("autospawn.entities.monster", []);
            }

            if(count($ent) < 1){
                return;
            }

            $radius = (int) $owner->getData("autospawn.radius", 25);
            $pos = $player->getPosition();
            $pos->y = $player->level->getHighestBlockAt($pos->x += mt_rand(-$radius, $radius), $pos->z += mt_rand(-$radius, $radius)) + 2;

            $entity = EntityManager::create($ent[mt_rand(0, count($ent) - 1)], $pos);
            if($entity != null){
                $entity->spawnToAll();
            }
        }
    }
}