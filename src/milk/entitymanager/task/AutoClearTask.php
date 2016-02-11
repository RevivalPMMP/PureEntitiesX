<?php

namespace milk\entitymanager\task;

use milk\entitymanager\EntityManager;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class AutoClearTask extends PluginTask{

    public function onRun($currentTick){
        /** @var EntityManager $owner */
        $owner = Server::getInstance()->getPluginManager()->getPlugin("EntityManager");
        $list = $owner->getData("autoclear.entities", ["Projectile", "DroppedItem"]);
        foreach(Server::getInstance()->getLevels() as $level){
            foreach($level->getEntities() as $entity){
                $reflect = new \ReflectionClass(get_class($entity));
                if(in_array($reflect->getShortName(), $list)){
                    $entity->close();
                }
            }
        }
    }

}