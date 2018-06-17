<?php

/**
 * PureEntitiesX: Mob AI Plugin for PMMP
 * Copyright (C)  2018 RevivalPMMP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace revivalpmmp\pureentities;

use pocketmine\entity\Entity;
use pocketmine\math\VoxelRayTrace;
use pocketmine\Player;
use revivalpmmp\pureentities\utils\PeTimings;

/**
 * This class is useful when it comes to interaction with entities.
 *
 * Class InteractionHelper
 * @package revivalpmmp\pureentities
 */
class InteractionHelper{

	/**
	 * Just a helper function (for better finding where a button text is displayed to player)
	 *
	 * @param string $text the text to be displayed in the button (we should translate that!)
	 * @param Player $player the player to display the text
	 */
	public static function displayButtonText(string $text, Player $player){
		$player->getDataPropertyManager()->setPropertyValue(Entity::DATA_INTERACTIVE_TAG, Entity::DATA_TYPE_STRING, $text);
	}


	/**
	 * Returns the button text which is currently displayed to the player
	 *
	 * @param Player $player the player to get the button text for
	 * @return ?string           the button text, may be empty or NULL
	 */
	public static function getButtonText(Player $player) : ?string{
		return $player->getDataPropertyManager()->getPropertyValue(Entity::DATA_INTERACTIVE_TAG, Entity::DATA_TYPE_STRING);
	}

	/**
	 * Returns the Entity the player is looking at currently
	 *
	 * @param Player $player the player to check
	 * @param int    $maxDistance the maximum distance to check for entities
	 * @param bool   $useCorrection define if correction should be used or not (if so, the matching will increase but is more unprecise and it will consume more performance)
	 * @return mixed|null|Entity    either NULL if no entity is found or an instance of the entity
	 */
	public static function getEntityPlayerLookingAt(Player $player, int $maxDistance, bool $useCorrection = false){
		PeTimings::startTiming("getEntityPlayerLookingAt [distance:$maxDistance]");
		/**
		 * @var Entity
		 */
		$entity = null;

		// just a fix because player MAY not be fully initialized
		if($player->temporalVector !== null){
			$nearbyEntities = $player->getLevel()->getNearbyEntities($player->boundingBox->expandedCopy($maxDistance, $maxDistance, $maxDistance), $player);

			// get all blocks in looking direction until the max interact distance is reached (it's possible that startblock isn't found!)
			try{
				foreach(VoxelRayTrace::inDirection($player->add(0, $player->eyeHeight, 0), $player->getDirectionVector(), $maxDistance) as $vector3){

					$block = $player->level->getBlockAt($vector3->x, $vector3->y, $vector3->z);
					$entity = self::getEntityAtPosition($nearbyEntities, $block->x, $block->y, $block->z, $useCorrection);
					if($entity !== null){
						break;
					}
				}
			}catch(\InvalidStateException $e){
				// nothing to log here!
			}
		}

		PeTimings::stopTiming("getEntityPlayerLookingAt [distance:$maxDistance]", true);

		return $entity;
	}

	/**
	 * Returns the entity at the given position from the array of nearby entities
	 *
	 * @param array $nearbyEntities an array of entity which are close to the player
	 * @param int   $x the x coordinate to search for any of the given entities coordinates to match
	 * @param int   $y the y coordinate to search for any of the given entities coordinates to match
	 * @param int   $z the z coordinate to search for any of the given entities coordinates to match
	 * @param bool  $useCorrection set this to true if the matching should be extended by -1 / +1 (in x, y, z directions)
	 * @return mixed|null|Entity        NULL when none of the given entities matched or the first entity matching found
	 */
	private static function getEntityAtPosition(array $nearbyEntities, int $x, int $y, int $z, bool $useCorrection){
		foreach($nearbyEntities as $nearbyEntity){
			if($nearbyEntity->getFloorX() == $x and $nearbyEntity->getFloorY() == $y and $nearbyEntity->getFloorZ() == $z){
				return $nearbyEntity;
			}else if($useCorrection){ // when using correction, we search not only in front also 1 block up/down/left/right etc. pp
				return self::getCorrectedEntity($nearbyEntity, $x, $y, $z);
			}
		}
		return null;
	}

	/**
	 * Searches around the given x, y, z coordinates (-1/+1) for the given entity coordinates to match.
	 *
	 * @param Entity $entity the entity to check coordinates with
	 * @param int    $x the starting x position
	 * @param int    $y the starting y position
	 * @param int    $z the starting z position
	 * @return null|Entity      NULL when entity position doesn't match, an instance of entity if it matches
	 */
	private static function getCorrectedEntity(Entity $entity, int $x, int $y, int $z){
		$entityX = $entity->getFloorX();
		$entityY = $entity->getFloorY();
		$entityZ = $entity->getFloorZ();

		for($searchX = ($x - 1); $searchX <= ($x + 1); $searchX++){
			for($searchY = ($y - 1); $searchY <= ($y + 1); $searchY++){
				for($searchZ = ($z - 1); $searchZ <= ($z + 1); $searchZ++){
					if($entityX == $searchX and $entityY == $searchY and $entityZ == $searchZ){
						return $entity;
					}
				}
			}
		}
		return null;
	}
}
