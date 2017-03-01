<?php

/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2017 RevivalPMMP

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>. */

namespace revivalpmmp\pureentities;



/**
 * Class PluginConfiguration
 *
 * For general plugin configuration (values are read here and can be obtained by calling appropriate methods)
 *
 * @package revivalpmmp\pureentities
 */
class PluginConfiguration {

    /** @var  PureEntities $instance */
    private static $instance;

    private $maxInteractDistance = 4; // this is standard (may be overridden by config!)
    private $maxFindPartnerDistance = 49; // this is standard (may be overridden by config!)
    private $blockOfInterestTicks = 300; // defines the ticks should pass by before a block of interest check is done
    private $checkTargetSkipTicks = 10; // defines how many ticks should be processed until the checkTarget method is called
    private $interactiveButtonCorrection = false; // defines for interactive button search if correction should be used or not
    private $useBlockLightForSpawn = false; // determines if the spawner classes should use block light instead of time on server
    private $useSkyLightForSpawn = false; // determines if the spawner classes should use skylight instead of time on server
    private $emitLoveParticlesCostantly = false; // determines if love particles are emitted constantly for entities that are in love mode
    private $tamedTeleportBlocks = 12; // minimum distance to player when tamed entities start to teleport to their ownes
    private $xpEnabled = false; // default is false!

    /**
     * Returns the plugin instance to get access to config e.g.
     * @return PluginConfiguration the current instance of the plugin main class
     */
    public static function getInstance(): PluginConfiguration {
        return self::$instance;
    }

    public function __construct(PureEntities $pluginBase) {

        // read some more config which we need internally (read once, give access to them via this class!)
        $this->maxFindPartnerDistance = $pluginBase->getConfig()->getNested("distances.find-partner", 49);
        $this->maxInteractDistance = $pluginBase->getConfig()->getNested("distances.interact", 4);
        $this->tamedTeleportBlocks = $pluginBase->getConfig()->getNested("distances.tamed-teleport", 12);

        $this->blockOfInterestTicks = $pluginBase->getConfig()->getNested("ticks.block-interest", 300);

        $this->checkTargetSkipTicks = $pluginBase->getConfig()->getNested("performance.check-target-tick-skip", 1); // default: do not skip ticks asking checkTarget method
        $this->interactiveButtonCorrection = $pluginBase->getConfig()->getNested("performance.check-interactive-correction", false); // default: do not check other blocks for the entity for button display

        $this->useBlockLightForSpawn = $pluginBase->getConfig()->getNested("spawn-task.use-block-light", false); // default: do not use block light
        $this->useSkyLightForSpawn = $pluginBase->getConfig()->getNested("spawn-task.use-sky-light", false); // default: do not use block light

        $this->emitLoveParticlesCostantly = $pluginBase->getConfig()->getNested("breeding.emit-love-particles-constantly", false); // default: do not emit love particles constantly

        $this->xpEnabled = $pluginBase->getConfig()->getNested("xp.enabled", false); // default: xp system not enabled

        // print effective configuration!
        $pluginBase->getServer()->getLogger()->notice("[PureEntitiesX] Configuration loaded:" .
            " [findPartnerDistance:" . $this->maxFindPartnerDistance . "] [interactDistance:" . $this->maxInteractDistance . "]" .
            " [teleportTamedDistance:" . $this->tamedTeleportBlocks . "]" .
            " [blockOfInterestTicks:" . $this->blockOfInterestTicks . "] [checkTargetSkipTicks:" . $this->checkTargetSkipTicks . "]" .
            " [interactiveButtonCorrection:" . $this->interactiveButtonCorrection . "] [useBlockLight:" . $this->useBlockLightForSpawn . "] [useSkyLight:" . $this->useSkyLightForSpawn . "]" .
            " [emitLoveParticles:" . $this->emitLoveParticlesCostantly . "] [xpEnabled:" . $this->xpEnabled . "]");

        self::$instance = $this;
    }

    /**
     * Returns the configured maximum distance for interaction with entities
     * @return int
     */
    public function getMaxInteractDistance(): int {
        return $this->maxInteractDistance;
    }

    /**
     * Returns the configured maximum distance for finding a partner (max search distance!)
     * @return int
     */
    public function getMaxFindPartnerDistance(): int {
        return $this->maxFindPartnerDistance;
    }

    /**
     * How many ticks should pass by before another check for block of interest is made
     *
     * @return int
     */
    public function getBlockOfInterestTicks(): int {
        return $this->blockOfInterestTicks;
    }

    /**
     * How many ticks should be skipped until checkTarget is called
     *
     * @return int|mixed
     */
    public function getCheckTargetSkipTicks() {
        return $this->checkTargetSkipTicks;
    }

    /**
     * @return bool|mixed
     */
    public function isInteractiveButtonCorrection() {
        return $this->interactiveButtonCorrection;
    }

    /**
     * @return bool|mixed
     */
    public function getUseBlockLightForSpawn() {
        return $this->useBlockLightForSpawn;
    }

    /**
     * @return bool|mixed
     */
    public function getUseSkyLightForSpawn() {
        return $this->useSkyLightForSpawn;
    }

    /**
     * @return bool|mixed
     */
    public function getEmitLoveParticlesCostantly() {
        return $this->emitLoveParticlesCostantly;
    }

    /**
     * @return int|mixed
     */
    public function getTamedTeleportBlocks() {
        return $this->tamedTeleportBlocks;
    }

    /**
     * @return bool|mixed
     */
    public function getXpEnabled() {
        return $this->xpEnabled;
    }



}