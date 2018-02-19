<?php
/**
 * PureEntitiesX: Mob AI Plugin for PMMP
 * Copyright (C) 2018 RevivalPMMP
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


class MobTypeMaps{
	const PASSIVE_DRY_MOBS = array(
		"bat",
		"chicken",
		"cow",
		"donkey",
		"horse",
		"husk",
		"llama",
		"mooshroom",
		"mule",
		"ocelot",
		"parrot",
		"pig",
		"rabbit",
		"sheep",
	);

	const PASSIVE_WET_MOBS = array(
		"squid"
	);

	const OVERWORLD_HOSTILE_MOBS = array(
		"cave_spider",
		"creeper",
		"enderman",
		"guardian",
		"husk",
		"polar_bear",
		"skeleton",
		"slime",
		"spider",
		"stray",
		"witch",
		"wolf",
		"zombie"
	);

	const NETHER_HOSTILE_MOBS = array(
		"blaze",
		"enderman",
		"ghast",
		"magma_cube",
		"skeleton",
		"wither_skeleton",
		"zombie_pigman"
	);
}