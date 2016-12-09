<?php

namespace magicode\pureentities\entity;

abstract class JumpingEntity extends BaseEntity{

    /*
     * For slimes and Magma Cubes ONLY
     * Not to be confused for normal entity jumping
     */
    
    protected function checkTarget(){
        //TODO
    }

    public function updateMove($tickDiff){
        // TODO
        return null;
    }
}