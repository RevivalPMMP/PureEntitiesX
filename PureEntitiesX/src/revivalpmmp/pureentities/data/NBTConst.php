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

namespace revivalpmmp\pureentities\data;

interface NBTConst {

    // Keys

    const NBT_KEY_ANGRY = "Angry"; // 0 - not angry, > 0 angry
    const NBT_KEY_BIRDTYPE = "Variant";
    const NBT_KEY_CATTYPE = "Variant";
    const NBT_KEY_COLLAR_COLOR = "PEXCollarColor"; // 0 -14 (14 - RED)
    const NBT_KEY_COLOR = "Color";
    const NBT_KEY_OWNER_UUID = "OwnerUUID"; // string
    const NBT_KEY_SHEARED = "Sheared";
    const NBT_KEY_SITTING = "Sitting"; // 1 or 0 (true/false)

    // this is our own tag - only for server side ...
    const NBT_SERVER_KEY_OWNER_NAME = "OwnerName";
}