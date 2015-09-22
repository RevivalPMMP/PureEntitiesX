<?php

namespace milk\entitymanager\entity;

class Squid extends WaterAnimal{
	const NETWORK_ID = 17;

	public $width = 0.95;
	public $length = 0.95;
	public $height = 0.95;

	protected $speed = 0.5;

	public function initEntity(){
		if(isset($this->namedtag->Health)){
			$this->setHealth((int) $this->namedtag["Health"]);
		}else{
			$this->setHealth($this->getMaxHealth());
		}
		parent::initEntity();
		$this->created = true;
	}

	public function getName(){
		return "Squid";
	}

	public function getDrops(){
		return [];
	}

}
