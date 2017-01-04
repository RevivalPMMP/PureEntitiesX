<?php
namespace revivalpmmp\pureentities\task\spawners\monster;


use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\walking\Spider;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class SpiderSpawner
 *
 * Spawn: Spiders spawn in the Overworld in 3×3×2 space on solid blocks (centered on the middle block) at a
 * light level of 7 or less, in groups of 4. The top blocks can be transparent, but not solid.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class SpiderSpawner extends BaseSpawner {

    public function spawn (Position $pos, Player $player) : bool {
        if ($this->spawnAllowedByProbability()) { // first check if spawn would be allowed, if not the other method calls make no sense at all
            $block = $pos->level->getBlock($pos); // because we get the air block, we need to substract 1 from the y position

            PureEntities::logOutput($this->getClassNameShort() .
                ": isNight: " . !$this->isDay($pos->level) .
                ", isSolidBlock: " . $block->isSolid() . " [" . $block->getName() . "]" .
                ", spawnAllowedByEntityCount: " . $this->spawnAllowedBySpiderCount($pos->getLevel(), 4) .
                ", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos),
                PureEntities::DEBUG);

            if (!$this->isDay($pos->level) and // spawn only at night
                $block->isSolid() and // block must be solid
                $this->spawnAllowedBySpiderCount($pos->getLevel(), 4) and // respect count in level
                $this->checkPlayerDistance($player, $pos)) { // distance to player has to be at least a configurable amount of blocks (atm 8!)
                for ($i = 0 ; $i < 4; $i++) {
                    $this->spawnEntityToLevel($pos, $this->getEntityNetworkId(), $pos->getLevel(), "Monster");
                    PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos)", PureEntities::NORM);
                }
                return true;
            }
        } else {
            PureEntities::logOutput($this->getClassNameShort() . ": spawn not allowed because probability denies spawn", PureEntities::DEBUG);
        }

        return false;
    }

    protected function getEntityNetworkId () : int {
        return Spider::NETWORK_ID;
    }
    protected function getEntityName () : string {
        return "Spider";
    }

    // ---- spider spawner specific -----

    /**
     * Special method because we spawn herds of rabbits (at least 2 of them)
     *
     * @param Level $level
     * @param int $herdSize
     * @return bool
     */
    protected function spawnAllowedBySpiderCount (Level $level, int $herdSize) : bool {
        if ($this->maxSpawn <= 0) {
            return false;
        }
        $count = 0;
        foreach ($level->getEntities() as $entity) { // check all entities in given level
            if ($entity->isAlive() and !$entity->closed and $entity::NETWORK_ID == $this->getEntityNetworkId()) { // count only alive, not closed and desired entities
                $count ++;
            }
        }

        PureEntities::logOutput($this->getClassNameShort() . ": got count of  $count entities living for " . $this->getEntityName(), PureEntities::DEBUG);

        if (($count + $herdSize) < $this->maxSpawn) {
            return true;
        }
        return false;
    }

}