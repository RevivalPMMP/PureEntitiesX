<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Ageable;

class Squid extends FlyEntity implements Ageable{
	//TODO: This is not implemented yet
	const NETWORK_ID = 17;

	public $width = 0.95;
	public $length = 0.95;
	public $height = 0.95;

	public function initEntity(){
		$this->setMaxHealth(5);
		parent::initEntity();
	}

	public function getName(){
		return "Squid";
	}

}
