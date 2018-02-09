<?php

/**
 * PureEntitiesX: Mob AI Plugin for PMMP
 * Copyright (C)  2018 RevivalPMMP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace revivalpmmp\pureentities;


/**
 * Class PluginConfiguration
 *
 * For general plugin configuration (values are read here and can be obtained by calling appropriate methods)
 *
 * @package revivalpmmp\pureentities
 */
class PluginConfiguration{

	/** @var  PureEntities $instance */
	private static $instance;

	private $maxInteractDistance = 4; // this is standard (may be overridden by config!)
	private $maxFindPartnerDistance = 49; // this is standard (may be overridden by config!)
	private $blockOfInterestTicks = 300; // defines the ticks should pass by before a block of interest check is done
	private $checkTargetSkipTicks = 10; // defines how many ticks should be processed until the checkTarget method is called
	private $interactiveButtonCorrection = false; // defines for interactive button search if correction should be used or not
	private $useBlockLightForSpawn = false; // determines if the spawner classes should use block light instead of time on server
	private $useSkyLightForSpawn = false; // determines if the spawner classes should use skylight instead of time on server
	private $emitLoveParticlesConstantly = false; // determines if love particles are emitted constantly for entities that are in love mode
	private $tamedTeleportBlocks = 12; // minimum distance to player when tamed entities start to teleport to their owner
	private $tamedPlayerMaxDistance = 10; // default: until the player is not x blocks away the tamed entities are walking around aimlessly
	private $xpEnabled = false; // default is false!
	private $pickupLootTicks = 10; // default: 10
	private $logEnabled = false; // enable or disable file logging
	private $enableSpawning = true; // enable spawning of entities
	private $enabledWorlds = []; // worlds where spawning of entities is allowed
	private $enableAsyncTasks = true; // enable async tasks for setting owner of tamed and setting mob equipment
	private $enableLookingTasks = true; // enable looking tasks (like shear, tame etc. pp) and enderman looking task
	private $enableNBT = true; // enable load/store of NBT
	private $enablePanic = true; // enable or disable panic mode for entities
	private $panicTicks = 100; // how long is an entity in panic mode?
	private $maxAge = 72000; // 1 hour (if 20 ticks = 1 second)

	// idle settings
	private $idleChance = 20;
	private $idleMin = 100;
	private $idleMax = 500;
	private $idleTimeBetween = 60;

	/**
	 * Returns the plugin instance to get access to config e.g.
	 * @return PluginConfiguration the current instance of the plugin main class
	 */
	public static function getInstance() : PluginConfiguration{
		return self::$instance;
	}

	public function __construct(PureEntities $pluginBase){

		$this->enabledWorlds = $pluginBase->getConfig()->get("enabledworlds", []);
		$this->enableSpawning = $pluginBase->getConfig()->getNested("tasks.spawn", true);
		$this->enableAsyncTasks = $pluginBase->getConfig()->getNested("tasks.async", true);
		$this->enableLookingTasks = $pluginBase->getConfig()->getNested("tasks.looking", true);

		// read some more config which we need internally (read once, give access to them via this class!)
		$this->maxFindPartnerDistance = $pluginBase->getConfig()->getNested("distances.find-partner", 49);
		$this->maxInteractDistance = $pluginBase->getConfig()->getNested("distances.interact", 4);
		$this->tamedTeleportBlocks = $pluginBase->getConfig()->getNested("distances.tamed-teleport", 12);
		$this->tamedPlayerMaxDistance = $pluginBase->getConfig()->getNested("distances.tamed-follow-player", 10);

		$this->blockOfInterestTicks = $pluginBase->getConfig()->getNested("ticks.block-interest", 300);

		$this->checkTargetSkipTicks = $pluginBase->getConfig()->getNested("performance.check-target-tick-skip", 1); // default: do not skip ticks asking checkTarget method
		$this->interactiveButtonCorrection = $pluginBase->getConfig()->getNested("performance.check-interactive-correction", false); // default: do not check other blocks for the entity for button display
		$this->pickupLootTicks = $pluginBase->getConfig()->getNested("performance.check-pickup-loot", 10); // default: check every 10 ticks for picking up loot
		$this->logEnabled = $pluginBase->getConfig()->getNested("performance.enable-logging", false); // default: false - do not use logging
		$this->enableNBT = $pluginBase->getConfig()->getNested("performance.enable-nbt-storage", true); // default: enable storage and loading of NBT

		$this->useBlockLightForSpawn = $pluginBase->getConfig()->getNested("spawn-task.use-block-light", false); // default: do not use block light
		$this->useSkyLightForSpawn = $pluginBase->getConfig()->getNested("spawn-task.use-sky-light", false); // default: do not use block light

		$this->emitLoveParticlesConstantly = $pluginBase->getConfig()->getNested("breeding.emit-love-particles-constantly", false); // default: do not emit love particles constantly

		$this->xpEnabled = $pluginBase->getConfig()->getNested("xp.enabled", false); // default: xp system not enabled

		$this->idleChance = $pluginBase->getConfig()->getNested("idle.chance", 20); // default: 20 percent idle chance
		$this->idleMin = $pluginBase->getConfig()->getNested("idle.min-idle", 100); // default: 100 ticks
		$this->idleMax = $pluginBase->getConfig()->getNested("idle.max-idle", 500); // default: 500 ticks
		$this->idleTimeBetween = $pluginBase->getConfig()->getNested("idle.time-between-idle", 60); // default: 60 seconds

		$this->enablePanic = $pluginBase->getConfig()->getNested("panic.enabled", true); // default: enabled
		$this->panicTicks = $pluginBase->getConfig()->getNested("panic.ticks", 100); // default: 100 ticks

		$this->maxAge = $pluginBase->getConfig()->getNested("despawn.after-ticks", 72000); // default: 72000 ticks

		// print effective configuration!
		$pluginBase->getServer()->getLogger()->notice("[PureEntitiesX] Configuration loaded:" .
			" [enableNBT:" . $this->enableNBT . "]" .
			" [enableSpawn:" . $this->enableSpawning . "] [enableAsyncTasks:" . $this->enableAsyncTasks . "] [enableLookingTasks:" . $this->enableLookingTasks . "]" .
			" [loggingEnabled:" . $this->logEnabled . "] [findPartnerDistance:" . $this->maxFindPartnerDistance . "] [interactDistance:" . $this->maxInteractDistance . "]" .
			" [teleportTamedDistance:" . $this->tamedTeleportBlocks . "] [tamedFollowDistance:" . $this->tamedPlayerMaxDistance . "]" .
			" [blockOfInterestTicks:" . $this->blockOfInterestTicks . "] [checkTargetSkipTicks:" . $this->checkTargetSkipTicks . "] [pickupLootTicks:" . $this->pickupLootTicks . "]" .
			" [interactiveButtonCorrection:" . $this->interactiveButtonCorrection . "] [useBlockLight:" . $this->useBlockLightForSpawn . "] [useSkyLight:" . $this->useSkyLightForSpawn . "]" .
			" [emitLoveParticles:" . $this->emitLoveParticlesConstantly . "] [xpEnabled:" . $this->xpEnabled . "]" .
			" [idleChance:" . $this->idleChance . "] [idleMin:" . $this->idleMin . "] [idleMax:" . $this->idleMax . "] [idleTimeBetween:" . $this->idleTimeBetween . "secs]" .
			" [panicEnabled:" . $this->enablePanic . "] [panicTicks:" . $this->panicTicks . "] [entityMaxAge:" . $this->maxAge . "]");

		self::$instance = $this;
	}

	/**
	 * Returns the configured maximum distance for interaction with entities
	 * @return int
	 */
	public function getMaxInteractDistance() : int{
		return $this->maxInteractDistance;
	}

	/**
	 * Returns the configured maximum distance for finding a partner (max search distance!)
	 * @return int
	 */
	public function getMaxFindPartnerDistance() : int{
		return $this->maxFindPartnerDistance;
	}

	/**
	 * How many ticks should pass by before another check for block of interest is made
	 *
	 * @return int
	 */
	public function getBlockOfInterestTicks() : int{
		return $this->blockOfInterestTicks;
	}

	/**
	 * How many ticks should be skipped until checkTarget is called
	 *
	 * @return int|mixed
	 */
	public function getCheckTargetSkipTicks(){
		return $this->checkTargetSkipTicks;
	}

	/**
	 * @return bool|mixed
	 */
	public function isInteractiveButtonCorrection(){
		return $this->interactiveButtonCorrection;
	}

	/**
	 * @return bool|mixed
	 */
	public function getUseBlockLightForSpawn(){
		return $this->useBlockLightForSpawn;
	}

	/**
	 * @return bool|mixed
	 */
	public function getUseSkyLightForSpawn(){
		return $this->useSkyLightForSpawn;
	}

	/**
	 * @return bool|mixed
	 */
	public function getEmitLoveParticlesConstantly(){
		return $this->emitLoveParticlesConstantly;
	}

	/**
	 * @return int|mixed
	 */
	public function getTamedTeleportBlocks(){
		return $this->tamedTeleportBlocks;
	}

	/**
	 * @return bool|mixed
	 */
	public function getXpEnabled(){
		return $this->xpEnabled;
	}

	/**
	 * @return int|mixed
	 */
	public function getPickupLootTicks(){
		return $this->pickupLootTicks;
	}

	/**
	 * @return int|mixed
	 */
	public function getTamedPlayerMaxDistance(){
		return $this->tamedPlayerMaxDistance;
	}

	/**
	 * @return int|mixed
	 */
	public function getIdleChance(){
		return $this->idleChance;
	}

	/**
	 * @return int
	 */
	public function getIdleMin() : int{
		return $this->idleMin;
	}

	/**
	 * @return int
	 */
	public function getIdleMax() : int{
		return $this->idleMax;
	}

	/**
	 * @return int
	 */
	public function getIdleTimeBetween() : int{
		return $this->idleTimeBetween;
	}

	/**
	 * @return bool|mixed
	 */
	public function getLogEnabled(){
		return $this->logEnabled;
	}

	/**
	 * @return bool|mixed
	 */
	public function getEnableSpawning(){
		return $this->enableSpawning;
	}

	/**
	 * @return array
	 */
	public function getEnabledWorlds(){
		return $this->enabledWorlds;
	}

	/**
	 * @return bool|mixed
	 */
	public function getEnableAsyncTasks(){
		return $this->enableAsyncTasks;
	}

	/**
	 * @return bool|mixed
	 */
	public function getEnableLookingTasks(){
		return $this->enableLookingTasks;
	}

	/**
	 * @return bool|mixed
	 */
	public function getEnableNBT(){
		return $this->enableNBT;
	}

	/**
	 * @return bool|mixed
	 */
	public function getEnablePanic(){
		return $this->enablePanic;
	}

	/**
	 * @return int|mixed
	 */
	public function getPanicTicks(){
		return $this->panicTicks;
	}

	/**
	 * @return int|mixed
	 */
	public function getMaxAge(){
		return $this->maxAge;
	}
}