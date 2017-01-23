<?php

namespace revivalpmmp\pureentities;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\Player;

/**
 * This class is useful when it comes to interaction with entities.
 *
 * Class InteractionHelper
 * @package revivalpmmp\pureentities
 */
class InteractionHelper {

    private static $playerToEntityMapping = [];

    /**
     * Returns the entity for which a button text is displayed on a specific player
     *
     * @param Player $player
     * @return mixed (Creature or NULL)
     */
    public static function getEntityForButtonText (Player $player) {
        $entity = null;
        if (array_key_exists($player->getId(), self::$playerToEntityMapping)) {
            $entity = self::$playerToEntityMapping[$player->getId()];
        }
        PureEntities::logOutput("InteractionHelper: getEntityForButtonText returns $entity", PureEntities::DEBUG);
        return $entity;
    }

    /**
     * @param Player $player
     * @param Creature $entity
     */
    public static function setEntityForButtonText (Player $player, Creature $entity) {
        self::$playerToEntityMapping[$player->getId()] = $entity;
    }

    /**
     * @param Player $player
     */
    public static function resetEntityForButtonText (Player $player) {
        self::$playerToEntityMapping[$player->getId()] = null;
    }

    /**
     * Just a helper function (for better finding where a button text is displayed to player)
     *
     * @param string $text      the text to be displayed in the button (we should translate that!)
     * @param Player $player    the player to display the text
     * @param Creature $creature the creature that the button text is for
     */
    public static function displayButtonText (string $text, Player $player, Creature $creature) {
        if (strcmp($text, "") === 0) { // the button text should be reset - check if it has been set already in the same tick
            $entity = self::getEntityForButtonText($player);
            if ($entity == null || !self::isPlayerLookingAtEntity($player, $entity) || $entity->distanceSquared($player) > PluginConfiguration::getInstance()->getMaxInteractDistance()) {
                $player->setDataProperty(Entity::DATA_INTERACTIVE_TAG, Entity::DATA_TYPE_STRING, $text);
                self::resetEntityForButtonText($player);
                PureEntities::logOutput("displayButtonText: resetting button text for $player and $creature", PureEntities::DEBUG);
            } else {
                PureEntities::logOutput("displayButtonText: do not reset button text for $player and $creature cause it's set and still valid!", PureEntities::DEBUG);
            }
        } else {
            $player->setDataProperty(Entity::DATA_INTERACTIVE_TAG, Entity::DATA_TYPE_STRING, $text);
            self::setEntityForButtonText($player, $creature);
        }
    }


    /**
     * Returns the button text which is currently displayed to the player
     *
     * @param Player $player    the player to get the button text for
     * @return string           the button text, may be empty or NULL
     */
    public static function getButtonText (Player $player) : string {
        return $player->getDataProperty(Entity::DATA_INTERACTIVE_TAG);
    }

    private static function isPlayerLookingAtEntity (Player $player, Entity $entity) {
        foreach ($entity->getViewers() as $viewer) {
            if ($viewer->getId() == $player->getId()) {
                return true;
            }
        }
        return false;
    }

}