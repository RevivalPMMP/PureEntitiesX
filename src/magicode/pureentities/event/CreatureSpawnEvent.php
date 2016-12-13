<?php

namespace magicode\pureentities\event;

use pocketmine\event\plugin\PluginEvent;
use magicode\pureentities\PureEntities;
use pocketmine\event\Cancellable;
use pocketmine\level\Position;
use pocketmine\level\Level;

class CreatureSpawnEvent extends PluginEvent implements Cancellable {
    public static $handlerList = null;
    
    private $pos;
    private $entityid;
    private $level;
    private $type;
    
    /**
     * @param PureEntities $plugin
     * @param Position $pos
     * @param int $entityid
     * @param Level $level
     * @param type $type
     */
    public function __construct(PureEntities $plugin, Position $pos, int $entityid, Level $level, string $type) {
        parent::__construct($plugin);
        $this->pos = $pos;
        $this->entityid = $entityid;
        $this->level = $level;
        $this->type = $type;
    }
    
    /**
     * Returns the position the entity is about to be spawned at.
     * @return Position
     */
    public function getPosition() {
        return $this->pos;
    }
    
    /**
     * Returns the Network ID from the entity about to be spawned.
     * @return int
     */
    public function getEntityId(): int {
        return $this->entityid;
    }
    
    /**
     * Returns the level the entity is about to spawn in.
     * @return Level
     */
    public function getLevel() {
        return $this->level;
    }
    
    /**
     * Returns the type of the entity about to be spawned. (Animal/Monster)
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }
}
