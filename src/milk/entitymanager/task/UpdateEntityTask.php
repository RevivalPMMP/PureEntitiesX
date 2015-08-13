<?php

namespace milk\entitymanager\task;

use milk\entitymanager\EntityManager;
use pocketmine\scheduler\PluginTask;

class UpdateEntityTask extends PluginTask{

    public function onRun($currentTicks){
        foreach(EntityManager::getEntities() as $entity){
            if($entity->isCreated()) $entity->updateTick();
        }
    }

}