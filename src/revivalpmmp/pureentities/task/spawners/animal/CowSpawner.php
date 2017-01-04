<?php
namespace revivalpmmp\pureentities\task\spawners\animal;


use pocketmine\block\Solid;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\walking\Cow;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class CowSpawner
 *
 * Spawn: Opaque blocks with at least two blocks of space above them.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class CowSpawner extends BaseSpawner {

    public function spawn (Position $pos, Player $player) : bool {

        if ($this->spawnAllowedByProbability()) { // first check if spawn would be allowed, if not the other method calls make no sense at all
            $block = $pos->level->getBlock($pos); // because we get the air block, we need to substract 1 from the y position

            PureEntities::logOutput($this->getClassNameShort() . ": isDay: " . $this->isDay($pos->getLevel()) . ", block is instanceof Solid" . $block instanceof Solid .
                "[" . $block->getName() . "], spawnAllowedByEntityCount: " . $this->spawnAllowedByEntityCount($pos->getLevel()) .
                ", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos) . ", enoughAirAbovePos: " . $this->isEnoughAirAbovePosition($pos),
                PureEntities::DEBUG);

            if ($this->isDay($pos->getLevel()) and // only spawn at daylight ...
                $block instanceof Solid and // and only on solid blocks (no matter, which solid)
                $this->spawnAllowedByEntityCount($pos->getLevel()) and // respect count in level
                $this->checkPlayerDistance($player, $pos) and // distance to player has to be at least a configurable amount of blocks (atm 8!)
                $this->isEnoughAirAbovePosition($pos)) { // check if there is at least 2 blocks of air above the spawn position
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
        return Cow::NETWORK_ID;
    }
    protected function getEntityName () : string {
        return "Cow";
    }

    // ---- cow spawner specific methods ----


    /**
     * Checks if there is at least 2 blocks of AIR above the position to spawn the cow to
     *
     * @param Position $pos
     * @return bool
     */
    private function isEnoughAirAbovePosition (Position $pos) : bool {
        $airFound = 0;
        $addY = 1;
        $newPosition = null;
        while ($airFound < 2) {
            $id = $pos->level->getBlockIdAt($pos->x, $pos->y + $addY, $pos->z);
            if ($id == 0) { // this is an air block ...
                $addY ++; // we found one - increment counter
                $airFound ++; // we found an air block. all we need is at least 2 blocks of air ;)
            } else {
                break;
            }
        }
        return $airFound >= 2;
    }


}