<?php
namespace revivalpmmp\pureentities\task\spawners\animal;


use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\flying\Bat;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class BatSpawner
 *
 * Spawn: Below layer 63 Light level of 3 or less in neighboring blocks
 *
 * Do not use this spawner, as Bats are not really implemented yet.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class BatSpawner extends BaseSpawner {

    public function spawn (Position $pos, Player $player) : bool {

        if ($this->spawnAllowedByProbability()) { // first check if spawn would be allowed, if not the other method calls make no sense at all
            $block = $pos->level->getBlock($pos); // because we get the air block, we need to substract 1 from the y position

            PureEntities::logOutput($this->getClassNameShort() . ": isNight: " . !$this->isDay($pos->getLevel()) . ", block is not transparent: " . !$block->isTransparent() .
                "[" . $block->getName() . "], spawnAllowedByEntityCount: " . $this->spawnAllowedByEntityCount($pos->getLevel()) .
                ", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos),
                PureEntities::DEBUG);

            if ($this->isSpawnAllowedByBlockLight($player, $pos, 3) and // check block light when enabled
                !$this->isDay($pos->getLevel()) and // only spawn at night ...
                $this->spawnAllowedByEntityCount($pos->getLevel()) and // respect count in level
                $this->checkPlayerDistance($player, $pos)) { // distance to player has to be at least a configurable amount of blocks (atm 8!)
                $this->spawnEntityToLevel($pos, $this->getEntityNetworkId(), $pos->getLevel(), "Animal");
                PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos)", PureEntities::NORM);
                return true;
            }
        } else {
            PureEntities::logOutput($this->getClassNameShort() . ": spawn not allowed because probability denies spawn", PureEntities::DEBUG);
        }

        return false;
    }

    protected function getEntityNetworkId () : int {
        return Bat::NETWORK_ID;
    }
    protected function getEntityName () : string {
        return "Bat";
    }


}