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

	public static $maxInteractDistance = 4; // this is standard (may be overridden by config!)
	public static $maxFindPartnerDistance = 49; // this is standard (may be overridden by config!)
	public static $blockOfInterestTicks = 300; // defines the ticks should pass by before a block of interest check is done
	public static $checkTargetSkipTicks = 10; // defines how many ticks should be processed until the checkTarget method is called
	public static $interactiveButtonCorrection = false; // defines for interactive button search if correction should be used or not
	public static $useBlockLightForSpawn = false; // determines if the spawner classes should use block light instead of time on server
	public static $useSkyLightForSpawn = false; // determines if the spawner classes should use skylight instead of time on server
	public static $emitLoveParticlesConstantly = false; // determines if love particles are emitted constantly for entities that are in love mode
	public static $tamedTeleportBlocks = 12; // minimum distance to player when tamed entities start to teleport to their owner
	public static $tamedPlayerMaxDistance = 10; // default: until the player is not x blocks away the tamed entities are walking around aimlessly
	public static $xpEnabled = false; // default is false!
	public static $pickupLootTicks = 10; // default: 10
	public static $enableSpawning = true; // enable spawning of entities
	public static $enabledWorlds = []; // worlds where spawning of entities is allowed
	public static $enableAsyncTasks = true; // enable async tasks for setting owner of tamed and setting mob equipment
	public static $enableLookingTasks = true; // enable looking tasks (like shear, tame etc. pp) and enderman looking task
	public static $enableNBT = true; // enable load/store of NBT
	public static $enablePanic = true; // enable or disable panic mode for entities
	public static $panicTicks = 100; // how long is an entity in panic mode?
	public static $maxAge = 72000; // 1 hour (if 20 ticks = 1 second)

	// idle settings
	public static $idleChance = 20;
	public static $idleMin = 100;
	public static $idleMax = 500;
	public static $idleTimeBetween = 60;

	public function __construct(PureEntities $pluginBase){

		self::$enabledWorlds = (array)$pluginBase->getConfig()->get("enabledworlds", []);
		self::$enableSpawning = (bool)$pluginBase->getConfig()->getNested("tasks.spawn", true);
		self::$enableAsyncTasks = (bool)$pluginBase->getConfig()->getNested("tasks.async", true);
		self::$enableLookingTasks = (bool)$pluginBase->getConfig()->getNested("tasks.looking", true);

		// read some more config which we need internally (read once, give access to them via this class!)
		self::$maxFindPartnerDistance = (int)$pluginBase->getConfig()->getNested("distances.find-partner", 49);
		self::$maxInteractDistance = (int)$pluginBase->getConfig()->getNested("distances.interact", 4);
		self::$tamedTeleportBlocks = (int)$pluginBase->getConfig()->getNested("distances.tamed-teleport", 12);
		self::$tamedPlayerMaxDistance = (int)$pluginBase->getConfig()->getNested("distances.tamed-follow-player", 10);

		self::$blockOfInterestTicks = (int)$pluginBase->getConfig()->getNested("ticks.block-interest", 300);

		self::$checkTargetSkipTicks = (int)$pluginBase->getConfig()->getNested("performance.check-target-tick-skip", 1); // default: do not skip ticks asking checkTarget method
		self::$interactiveButtonCorrection = (bool)$pluginBase->getConfig()->getNested("performance.check-interactive-correction", false); // default: do not check other blocks for the entity for button display
		self::$pickupLootTicks = (int)$pluginBase->getConfig()->getNested("performance.check-pickup-loot", 10); // default: check every 10 ticks for picking up loot
		self::$enableNBT = (bool)$pluginBase->getConfig()->getNested("performance.enable-nbt-storage", true); // default: enable storage and loading of NBT

		self::$useBlockLightForSpawn = (bool)$pluginBase->getConfig()->getNested("spawn-task.use-block-light", false); // default: do not use block light
		self::$useSkyLightForSpawn = (bool)$pluginBase->getConfig()->getNested("spawn-task.use-sky-light", false); // default: do not use block light

		self::$emitLoveParticlesConstantly = (bool)$pluginBase->getConfig()->getNested("breeding.emit-love-particles-constantly", false); // default: do not emit love particles constantly

		self::$xpEnabled = (bool)$pluginBase->getConfig()->getNested("xp.enabled", false); // default: xp system not enabled

		self::$idleChance = (int)$pluginBase->getConfig()->getNested("idle.chance", 20); // default: 20 percent idle chance
		self::$idleMin = (int)$pluginBase->getConfig()->getNested("idle.min-idle", 100); // default: 100 ticks
		self::$idleMax = (int)$pluginBase->getConfig()->getNested("idle.max-idle", 500); // default: 500 ticks
		self::$idleTimeBetween = (int)$pluginBase->getConfig()->getNested("idle.time-between-idle", 60); // default: 60 seconds

		self::$enablePanic = (bool)$pluginBase->getConfig()->getNested("panic.enabled", true); // default: enabled
		self::$panicTicks = (int)$pluginBase->getConfig()->getNested("panic.ticks", 100); // default: 100 ticks

		self::$maxAge = (int)$pluginBase->getConfig()->getNested("despawn.after-ticks", 72000); // default: 72000 ticks

		// print effective configuration!
		$pluginBase->getServer()->getLogger()->notice("[PureEntitiesX] Configuration loaded:" .
			" [enableNBT:" . self::$enableNBT . "]" .
			" [enableSpawn:" . self::$enableSpawning . "] [enableAsyncTasks:" . self::$enableAsyncTasks . "] [enableLookingTasks:" . self::$enableLookingTasks . "]" .
			" [findPartnerDistance:" . self::$maxFindPartnerDistance . "] [interactDistance:" . self::$maxInteractDistance . "]" .
			" [teleportTamedDistance:" . self::$tamedTeleportBlocks . "] [tamedFollowDistance:" . self::$tamedPlayerMaxDistance . "]" .
			" [blockOfInterestTicks:" . self::$blockOfInterestTicks . "] [checkTargetSkipTicks:" . self::$checkTargetSkipTicks . "] [pickupLootTicks:" . self::$pickupLootTicks . "]" .
			" [interactiveButtonCorrection:" . self::$interactiveButtonCorrection . "] [useBlockLight:" . self::$useBlockLightForSpawn . "] [useSkyLight:" . self::$useSkyLightForSpawn . "]" .
			" [emitLoveParticles:" . self::$emitLoveParticlesConstantly . "] [xpEnabled:" . self::$xpEnabled . "]" .
			" [idleChance:" . self::$idleChance . "] [idleMin:" . self::$idleMin . "] [idleMax:" . self::$idleMax . "] [idleTimeBetween:" . self::$idleTimeBetween . "secs]" .
			" [panicEnabled:" . self::$enablePanic . "] [panicTicks:" . self::$panicTicks . "] [entityMaxAge:" . self::$maxAge . "]");

	}
}