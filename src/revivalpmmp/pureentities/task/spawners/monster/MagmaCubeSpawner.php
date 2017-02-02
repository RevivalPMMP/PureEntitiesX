<?php
namespace revivalpmmp\pureentities\task\spawners\monster;


use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\jumping\MagmaCube;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class MagmaCubeSpawner
 *
 * Spawn: Magma cubes spawn rarely everywhere in the Nether at all light levels, but their spawn rate is higher in nether fortresses.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class MagmaCubeSpawner extends BaseSpawner {

    public function spawn (Position $pos, Player $player) : bool {
        if ($this->spawnAllowedByProbability()) { // first check if spawn would be allowed, if not the other method calls make no sense at all
            $block = $pos->level->getBlock($pos); // because we get the air block, we need to substract 1 from the y position
            $biomeId = $pos->level->getBiomeId($pos->x, $pos->z);

            PureEntities::logOutput($this->getClassNameShort() .
                ": isHell: " . ($biomeId == Biome::HELL) .
                ", block is solid: " . $block->isSolid() . "[" . $block->getName() .
                "], spawnAllowedByEntityCount: " . $this->spawnAllowedByEntityCount($pos->getLevel()) .
                ", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos),
                PureEntities::DEBUG);
            if ($biomeId == Biome::HELL and // is this nether? hmmmm ...
                $this->spawnAllowedByEntityCount($pos->getLevel()) and // respect entity count in level
                $block->isSolid() and // block must be solid
                $this->checkPlayerDistance($player, $pos)) { // respect distance to player which has to be at least 8 blocks
                $this->spawnEntityToLevel($pos, $this->getEntityNetworkId(), $pos->getLevel(), "Monster");
                PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos)", PureEntities::NORM);
                return true;
            }
        } else {
            PureEntities::logOutput($this->getClassNameShort() . ": spawn not allowed because probability denies spawn", PureEntities::DEBUG);
        }

        return false;
    }

    protected function getEntityNetworkId () : int {
        return MagmaCube::NETWORK_ID;
    }
    protected function getEntityName () : string {
        return "MagmaCube";
    }


}