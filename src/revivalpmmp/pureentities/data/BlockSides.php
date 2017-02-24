<?php

namespace revivalpmmp\pureentities\data;

use pocketmine\block\Block;

class BlockSides {

    private static $initialized = false;

    /**
     * @var array
     */
    private static $sides;


    private static function init () {
        self::$sides[] = Block::SIDE_SOUTH;
        self::$sides[] = Block::SIDE_WEST;
        self::$sides[] = Block::SIDE_NORTH;
        self::$sides[] = Block::SIDE_EAST;
        self::$initialized = true;
    }

    /**
     * Returns sides mapping to direction.
     *
     * @return array
     */
    public static function getSides () {
        if (!self::$initialized) {
            self::init();
        }
        return self::$sides;
    }

}