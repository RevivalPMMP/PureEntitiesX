<?php
/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2017 RevivalPMMP

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>. */

namespace revivalpmmp\pureentities\utils;

use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use revivalpmmp\pureentities\PureEntities;


class XpCalculator {
    /**
     * This function handles updating player XP levels and progress when collecting an Xp Orb.
     *
     * @param Player $player
     * @param int $xpGained
     */
    public static function updatePlayerXp(Player $player, int $xpGained) {
        $playerXpLevel = $player->getXpLevel();
        $playerXpProgress = $player->getXpProgress();

        $playerXpLevelGap = XpCalculator::getXpLevelGap($player->getXpLevel());
        $playerCurrentXp = ($playerXpProgress * $playerXpLevelGap) + $xpGained;
        $updatePlayer = true;

        while ($updatePlayer) {

            // Update internal progress counter with new Xp information
            $playerXpProgress = $playerCurrentXp / $playerXpLevelGap;

            if ($playerXpProgress >= 1) {
                $playerXpLevel++;
                $playerCurrentXp = $playerCurrentXp - $playerXpLevelGap;
                $playerXpLevelGap = XpCalculator::getXpLevelGap($player->getXpLevel());
            } else {
                $player->setXpLevel($playerXpLevel);
                $player->namedtag->XpLevel = new IntTag("XpLevel", $playerXpLevel);
                $player->setXpProgress($playerXpProgress);
                $player->namedtag->XpP = new FloatTag("XpP", $playerXpProgress);
                $updatePlayer = false;
            }
        }
    }

    /**
     * This function calculates and returns the total amount of Xp required to reach the level passed in.
     * Level values passed into this function should be positive integers or zero.
     *
     * @param int $level
     * @return int
     */
    public static function calculateLevelXp(int $level = 1) : int {

        if ($level == 0) {
            return 0;
        } elseif ($level >= 1 and $level <= 16) {
            return (($level ** 2) + (6 * $level));
        } elseif ($level >= 17 and $level <= 31) {
            return (2.5 * ($level ** 2) - (40.5 * $level) + 360);
        } elseif ($level >= 32) {
            return (4.5 * ($level ** 2) - (162.5 * $level) + 2220);
        } else {
            PureEntities::logOutput("XpCalculator: calculateLevelXp received invalid level $level", PureEntities::DEBUG);
            return -1;
        }
    }

    /**
     * This is the amount of Xp required to reach the next level from the player's
     * current level without considering the player's total Xp.
     *
     * eg.  If the player is currently on Xp Level 7, then they had to have a minimum
     * of 91 Xp to reach that level.  To reach Xp Level 8, they need a total Xp of 112.
     * The difference from what is needed to reach Xp Level 8 and what is needed
     * to reach Xp Level 7 is 112 - 91 = 21.
     * So this would return 21.
     *
     * @param int $currentLevel
     * @return int
     *
     */
    public static function getXpLevelGap(int $currentLevel) : int {
        return (XpCalculator::calculateLevelXp($currentLevel + 1) - XpCalculator::calculateLevelXp($currentLevel));
    }
}