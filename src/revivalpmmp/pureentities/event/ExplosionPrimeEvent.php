<?php

namespace revivalpmmp\pureentities\event;

use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;

/**
 * Called when a entity decides to explode
 */
class ExplosionPrimeEvent extends EntityEvent implements Cancellable{
	public static $handlerList = null;

	protected $force;
	private $blockBreaking;

	/**
	 * @param Entity $entity
	 * @param float  $force
	 */
	public function __construct(Entity $entity, $force){
		$this->entity = $entity;
		$this->force = $force;
		$this->blockBreaking = true;
	}

	/**
	 * @return float
	 */
	public function getForce(){
		return $this->force;
	}

	public function setForce($force){
		$this->force = (float) $force;
	}

	/**
	 * @return bool
	 */
	public function isBlockBreaking(){
		return $this->blockBreaking;
	}

	/**
	 * @param bool $affectsBlocks
	 */
	public function setBlockBreaking($affectsBlocks){
		$this->blockBreaking = (bool) $affectsBlocks;
	}

}
