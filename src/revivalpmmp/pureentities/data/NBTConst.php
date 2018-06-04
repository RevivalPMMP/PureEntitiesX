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

namespace revivalpmmp\pureentities\data;

interface NBTConst{

	// Invalid Check Keys
	const NBT_INVALID_BYTE = 126;
	const NBT_INVALID_INT = -2147483648;
	const NBT_INVALID_FLOAT = "";
	const NBT_INVALID_LONG = -9223372036854775808;
	const NBT_INVALID_SHORT = -2147483648;
	const NBT_INVALID_STRING = "4Y#X9XAM#bbR7eLz";


	// Standard Keys
	const NBT_KEY_AGE = "Age";
	const NBT_KEY_AGE_IN_TICKS = "AgeInTicks";
	const NBT_KEY_ANGRY = "Angry"; // 0 - not angry, > 0 angry
	const NBT_KEY_BIRDTYPE = "Variant";
	const NBT_KEY_BOMBTIME = "BombTime";
	const NBT_KEY_CATTYPE = "Variant";
	const NBT_KEY_COLLAR_COLOR = "PEXCollarColor"; // 0 -14 (14 - RED)
	const NBT_KEY_COLOR = "Color";
	const NBT_KEY_CUBE_SIZE = "CubeSize";
	const NBT_KEY_FORCED_AGE = "ForcedAge";     // A value of age which will be assigned to this mob when it grows up. Incremented when a baby mob is fed.
	const NBT_KEY_IN_LOVE = "InLove";           // Number of ticks until the mob loses its breeding hearts and stops searching for a mate. 0 when not searching for a mate.
	const NBT_KEY_MOVEMENT = "Movement";
	const NBT_KEY_OWNER_EID = "OwnerEID";       // Necessary for Wolf Collar Color
	const NBT_KEY_OWNER_UUID = "OwnerUUID"; // string
	const NBT_KEY_POWERED = "Powered";
	const NBT_KEY_PUMPKIN = "Pumpkin"; // 1 or 0 (true/false) - hat on or off ;)
	const NBT_KEY_SHEARED = "Sheared";
	const NBT_KEY_SITTING = "Sitting"; // 1 or 0 (true/false)
	const NBT_KEY_WALL_CHECK = "WallCheck";


	const NBT_KEY_IDLE_SETTINGS = "IdleSettings"; // compound tag
	const NBT_KEY_IDLING = "Idling"; // IntTag
	const NBT_KEY_IDLING_COUNTER = "IdlingCounter"; // IntTag
	const NBT_KEY_MAX_IDLING_COUNTER = "MaxIdlingCounter"; // IntTag
	const NBT_KEY_IDLING_TICK_COUNTER = "IdlingTickCounter"; // IntTag
	const NBT_KEY_MAX_IDLING_TICK_COUNTER = "MaxIdlingTickCounter"; // IntTag
	const NBT_KEY_LAST_IDLE_STATUS = "LastIdleStatus"; // IntTag

	const NBT_KEY_HAND_ITEMS = "HandItems";            // ListTag
	const NBT_KEY_ARMOR_ITEMS = "ArmorItems";        // ListTag
	const NBT_KEY_ARMOR_COUNT = "Count";            // IntTag
	const NBT_KEY_ARMOR_DAMAGE = "Damage";            // IntTag
	const NBT_KEY_ARMOR_ID = "id";                    // IntTag

	const NBT_KEY_SPAWNER_IS_MOVABLE = "isMovable";                            // ByteTag
	const NBT_KEY_SPAWNER_DELAY = "Delay";                                    // ShortTag
	const NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES = "MaxNearbyEntities";        // ShortTag
	const NBT_KEY_SPAWNER_MAX_SPAWN_DELAY = "MaxSpawnDelay";                // ShortTag
	const NBT_KEY_SPAWNER_MIN_SPAWN_DELAY = "MinSawnDelay";                    // ShortTag
	const NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE = "RequiredPlayerRange";    // ShortTag
	const NBT_KEY_SPAWNER_SPAWN_COUNT = "SpawnCount";                        // ShortTag
	const NBT_KEY_SPAWNER_SPAWN_RANGE = "SpawnRange";                        // ShortTag
	const NBT_KEY_SPAWNER_ENTITY_ID = "EntityId";                            // IntTag
	const NBT_KEY_SPAWNER_DISPLAY_ENTITY_HEIGHT = "DisplayEntityHeight";    // FloatTag
	const NBT_KEY_SPAWNER_DISPLAY_ENTITY_SCALE = "DisplayEntityScale";        // FloatTag
	const NBT_KEY_SPAWNER_DISPLAY_ENTITY_WIDTH = "DisplayEntityWidth";        // FloatTag
	const NBT_KEY_SPAWNER_SPAWN_DATA = "SpawnData";                            // ShortTag

	// this is our own tag - only for server side ...
	const NBT_SERVER_KEY_OWNER_NAME = "OwnerName";
}