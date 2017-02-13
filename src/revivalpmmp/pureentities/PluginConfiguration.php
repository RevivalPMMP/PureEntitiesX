<?php

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

    /**
     * Returns the plugin instance to get access to config e.g.
     * @return PluginConfiguration the current instance of the plugin main class
     */
    public static function getInstance() : PluginConfiguration {
        return self::$instance;
    }

    public function __construct(PureEntities $pluginBase) {

        // read some more config which we need internally (read once, give access to them via this class!)
        $this->maxFindPartnerDistance       = $pluginBase->getConfig()->getNested("distances.find-partner", 49);
        $this->maxInteractDistance          = $pluginBase->getConfig()->getNested("distances.interact", 4);
        $this->blockOfInterestTicks         = $pluginBase->getConfig()->getNested("ticks.block-interest", 300);

        $this->checkTargetSkipTicks         = $pluginBase->getConfig()->getNested("performance.check-target-tick-skip", 1); // default: do not skip ticks asking checkTarget method
        $this->interactiveButtonCorrection  = $pluginBase->getConfig()->getNested("performance.check-interactive-correction", false); // default: do not check other blocks for the entity for button display
        // print effective configuration!
        $pluginBase->getServer()->getLogger()->notice("Distances configured: [findPartner:" . $this->maxFindPartnerDistance . "] [interact:" . $this->maxInteractDistance . "]" .
            " [blockOfInterestTicks:" . $this->blockOfInterestTicks . "] [checkTargetSkipTicks:" . $this->checkTargetSkipTicks . "]".
            " [interactiveButtonCorrection:" . $this->interactiveButtonCorrection . "]");

        self::$instance = $this;
    }

    /**
     * Returns the configured maximum distance for interaction with entities
     * @return int
     */
    public function getMaxInteractDistance () : int {
        return $this->maxInteractDistance;
    }

    /**
     * Returns the configured maximum distance for finding a partner (max search distance!)
     * @return int
     */
    public function getMaxFindPartnerDistance () : int {
        return $this->maxFindPartnerDistance;
    }

    /**
     * How many ticks should pass by before another check for block of interest is made
     *
     * @return int
     */
    public function getBlockOfInterestTicks () : int {
        return $this->blockOfInterestTicks;
    }

    /**
     * How many ticks should be skipped until checkTarget is called
     *
     * @return int|mixed
     */
    public function getCheckTargetSkipTicks()
    {
        return $this->checkTargetSkipTicks;
    }

    /**
     * @return bool|mixed
     */
    public function isInteractiveButtonCorrection() {
        return $this->interactiveButtonCorrection;
    }



}