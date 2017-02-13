<?php
/**
 * Created by PhpStorm.
 * User: mige
 * Date: 11.02.17
 * Time: 12:48
 */

namespace revivalpmmp\pureentities\task;


use pocketmine\scheduler\PluginTask;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

/**
 * Class InteractionTask
 *
 * This is a helper task which is executed each x ticks. It checks if any player is looking at
 * an entity. If so and the player can interact with the entity - the method "showButton" is
 * called at the entity itself.
 *
 * @package revivalpmmp\pureentities\task
 */
class InteractionTask extends PluginTask {

    /**
     * @var PureEntities
     */
    private $plugin;

    public function __construct(PureEntities $plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    /**
     * Called when the task is executed
     *
     * @param int $currentTick
     */
    public function onRun($currentTick) {
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $entity = InteractionHelper::getEntityPlayerLookingAt($player, PluginConfiguration::getInstance()->getMaxInteractDistance(),
                PluginConfiguration::getInstance()->isInteractiveButtonCorrection());
            PureEntities::logOutput("InteractionTask: $player is looking at $entity", PureEntities::DEBUG);
            if ($entity !== null and $entity instanceof IntfCanInteract) { // player is looking at an entity he can interact with
                $entity->showButton($player);
            } else { // the player doesn't look at an entity
                InteractionHelper::displayButtonText("", $player);
            }
        }
    }


}