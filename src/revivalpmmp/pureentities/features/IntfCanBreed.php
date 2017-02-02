<?php

namespace revivalpmmp\pureentities\features;

/**
 * Interface IntfCanBreed
 * @package revivalpmmp\pureentities\features
 */
interface IntfCanBreed extends IntfFeedable {

    /**
     * Has to return the Breedable class initiated within entity
     *
     * @return mixed
     */
    public function getBreedingExtension ();

    /**
     * Has to return the network id for the associated entity
     *
     * @return mixed
     */
    public function getNetworkId();

}