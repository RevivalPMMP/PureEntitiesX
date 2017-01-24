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
    private $interactStayTime = 50; // ticks the entity stands still when in interaction with player
    private $blockOfInterestTicks = 300; // defines the ticks should pass by before a block of interest check is done

    /**
     * Returns the plugin instance to get access to config e.g.
     * @return PluginConfiguration the current instance of the plugin main class
     */
    public static function getInstance() : PluginConfiguration {
        return PluginConfiguration::$instance;
    }

    public function __construct() {
        $pluginBase = PureEntities::getInstance();

        // read some more config which we need internally (read once, give access to them via this class!)
        $this->maxFindPartnerDistance = $pluginBase->getConfig()->getNested("distances.find-partner", 49);
        $this->maxInteractDistance    = $pluginBase->getConfig()->getNested("distances.interact", 4);
        $this->interactStayTime       = $pluginBase->getConfig()->getNested("interaction.stay-time", 20); // ~ 2 seconds default?
        $this->blockOfInterestTicks   = $pluginBase->getConfig()->getNested("ticks.block-interest", 300);
        // print effective configuration!
        $pluginBase->getServer()->getLogger()->notice("Distances configured: [findPartner:" . $this->maxFindPartnerDistance . "] [interact:" . $this->maxInteractDistance . "]" .
            "[interactStayTime:" . $this->interactStayTime . "] [blockOfInterestTicks:" . $this->blockOfInterestTicks . "]");

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
     * Returns the configured interact stay time of an entity
     * @return int
     */
    public function getInteractStayTime () : int {
        return $this->interactStayTime;
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


}