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


interface Data{

	// Entity Network IDs
	const NETWORK_IDS = array(
		"bat" => 19,
		"blaze" => 43,
		"cave_spider" => 40,
		"chicken" => 10,
		"cow" => 11,
		"creeper" => 33,
		"donkey" => 24,
		"elder_guardian" => 50,
		"ender_charge" => 79,
		"ender_dragon" => 53,
		"enderman" => 38,
		"endermite" => 55,
		"evoker" => 104,
		"ghast" => 41,
		"guardian" => 49,
		"horse" => 23,
		"husk" => 47,
		"iron_golem" => 20,
		"large_fireball" => 85,
		"llama" => 29,
		"magma_cube" => 42,
		"mooshroom" => 16,
		"mule" => 25,
		"ocelot" => 22,
		"parrot" => 30,
		"pig" => 12,
		"pig_zombie" => 36,
		"polar_bear" => 28,
		"rabbit" => 18,
		"sheep" => 13,
		"shulker" => 54,
		"silverfish" => 39,
		"skeleton" => 34,
		"skeleton_horse" => 26,
		"slime" => 37,
		"small_fireball" => 94,
		"snow_golem" => 21,
		"spider" => 35,
		"squid" => 17,
		"stray" => 46,
		"vex" => 105,
		"villager" => 15,
		"vindicator" => 57,
		"witch" => 45,
		"wither_skeleton" => 48,
		"wither" => 52,
		"wolf" => 14,
		"zombie" => 32,
		"zombie_pigman" => 36,
		"zombie_villager" => 44
	);


	// Entity Widths
	const WIDTHS = array(

		self::NETWORK_IDS["bat"] => 0.484,
		self::NETWORK_IDS["blaze"] => 1.25,
		self::NETWORK_IDS["cave_spider"] => 1.438,
		self::NETWORK_IDS["chicken"] => 1,
		self::NETWORK_IDS["cow"] => 1.5,
		self::NETWORK_IDS["creeper"] => 0.7,
		self::NETWORK_IDS["donkey"] => 1.2,
		self::NETWORK_IDS["elder_guardian"] => 1.9975,
		self::NETWORK_IDS["ender_charge"] => 1.0,
		self::NETWORK_IDS["ender_dragon"] => 2.5,
		self::NETWORK_IDS["enderman"] => 1.094,
		self::NETWORK_IDS["endermite"] => 0.4,
		self::NETWORK_IDS["evoker"] => 1.031,
		self::NETWORK_IDS["ghast"] => 4.5,
		self::NETWORK_IDS["guardian"] => 0,
		self::NETWORK_IDS["horse"] => 1.3,
		self::NETWORK_IDS["husk"] => 1.031,
		self::NETWORK_IDS["iron_golem"] => 2.688,
		self::NETWORK_IDS["large_fireball"] => 0.5,
		self::NETWORK_IDS["llama"] => 0.9,
		self::NETWORK_IDS["magma_cube"] => 1.2,
		self::NETWORK_IDS["mooshroom"] => 1.781,
		self::NETWORK_IDS["mule"] => 1.2,
		self::NETWORK_IDS["ocelot"] => 0.8,
		self::NETWORK_IDS["parrot"] => 0.5,
		self::NETWORK_IDS["pig"] => 1.5,
		self::NETWORK_IDS["pig_zombie"] => 1.125,
		self::NETWORK_IDS["polar_bear"] => 1.3,
		self::NETWORK_IDS["rabbit"] => 0.4,
		self::NETWORK_IDS["sheep"] => 0.9,
		self::NETWORK_IDS["shulker"] => 1.0,
		self::NETWORK_IDS["silverfish"] => 1.094,
		self::NETWORK_IDS["skeleton"] => 0.875,
		self::NETWORK_IDS["skeleton_horse"] => 1.3,
		self::NETWORK_IDS["slime"] => 1.2,
		self::NETWORK_IDS["small_fireball"] => 0.25,
		self::NETWORK_IDS["snow_golem"] => 1.281,
		self::NETWORK_IDS["stray"] => 0.875,
		self::NETWORK_IDS["spider"] => 2.062,
		self::NETWORK_IDS["squid"] => 0,
		self::NETWORK_IDS["vex"] => 0.4,
		self::NETWORK_IDS["villager"] => 0.938,
		self::NETWORK_IDS["vindicator"] => 0.6,
		self::NETWORK_IDS["witch"] => 0.6,
		self::NETWORK_IDS["wither"] => 0.9,
		self::NETWORK_IDS["wither_skeleton"] => 0.875,
		self::NETWORK_IDS["wolf"] => 1.2,
		self::NETWORK_IDS["zombie"] => 1.031,
		self::NETWORK_IDS["zombie_pigman"] => 2.0,
		self::NETWORK_IDS["zombie_villager"] => 1.031
	);

	// Entity Heights
	const HEIGHTS = array(
		self::NETWORK_IDS["bat"] => 0.5,
		self::NETWORK_IDS["blaze"] => 1.5,
		self::NETWORK_IDS["cave_spider"] => 0.547,
		self::NETWORK_IDS["chicken"] => 0.8,
		self::NETWORK_IDS["cow"] => 1.2,
		self::NETWORK_IDS["creeper"] => 1.7,
		self::NETWORK_IDS["donkey"] => 1.562,
		self::NETWORK_IDS["elder_guardian"] => 1.9975,
		self::NETWORK_IDS["ender_charge"] => 1.0,
		self::NETWORK_IDS["ender_dragon"] => 1.0,
		self::NETWORK_IDS["enderman"] => 2.875,
		self::NETWORK_IDS["endermite"] => 0.3,
		self::NETWORK_IDS["evoker"] => 2.125,
		self::NETWORK_IDS["ghast"] => 4.5,
		self::NETWORK_IDS["guardian"] => 0,
		self::NETWORK_IDS["horse"] => 1.5,
		self::NETWORK_IDS["husk"] => 2.0,
		self::NETWORK_IDS["iron_golem"] => 1.625,
		self::NETWORK_IDS["large_fireball"] => 0.5,
		self::NETWORK_IDS["llama"] => 1.87,
		self::NETWORK_IDS["magma_cube"] => 1.2,
		self::NETWORK_IDS["mooshroom"] => 1.875,
		self::NETWORK_IDS["mule"] => 1.562,
		self::NETWORK_IDS["ocelot"] => 0.8,
		self::NETWORK_IDS["parrot"] => 0.9,
		self::NETWORK_IDS["pig"] => 1.0,
		self::NETWORK_IDS["pig_zombie"] => 2.03,
		self::NETWORK_IDS["polar_bear"] => 1.4,
		self::NETWORK_IDS["rabbit"] => 0.5,
		self::NETWORK_IDS["sheep"] => 1.3,
		self::NETWORK_IDS["shulker"] => 1.0,
		self::NETWORK_IDS["silverfish"] => 0.438,
		self::NETWORK_IDS["skeleton"] => 2.0,
		self::NETWORK_IDS["skeleton_horse"] => 1.5,
		self::NETWORK_IDS["slime"] => 1.2,
		self::NETWORK_IDS["small_fireball"] => 0.25,
		self::NETWORK_IDS["snow_golem"] => 1.875,
		self::NETWORK_IDS["stray"] => 2.0,
		self::NETWORK_IDS["spider"] => 0.781,
		self::NETWORK_IDS["squid"] => 0.0,
		self::NETWORK_IDS["vex"] => 0.8,
		self::NETWORK_IDS["villager"] => 2.0,
		self::NETWORK_IDS["vindicator"] => 1.95,
		self::NETWORK_IDS["witch"] => 1.95,
		self::NETWORK_IDS["wither"] => 3.5,
		self::NETWORK_IDS["wither_skeleton"] => 2.0,
		self::NETWORK_IDS["wolf"] => 0.969,
		self::NETWORK_IDS["zombie"] => 2.01,
		self::NETWORK_IDS["zombie_pigman"] => 2.0,
		self::NETWORK_IDS["zombie_villager"] => 2.125
	);

	// Contains biomes that each entity is allowed to
	// spawn into automatically.
	const ALLOWED_BIOMES_BY_ENTITY_NAME = array(
		"bat" => array(),
		"blaze" => 43,
		"cave_spider" => 40,
		"chicken" => 10,
		"cow" => 11,
		"creeper" => 33,
		"donkey" => 24,
		"elder_guardian" => 50,
		"enderman" => 38,
		"fire_ball" => 85,
		"ghast" => 41,
		"guardian" => 49,
		"horse" => 23,
		"husk" => 47,
		"iron_golem" => 20,
		"magma_cube" => 42,
		"mooshroom" => 16,
		"mule" => 25,
		"ocelot" => 22,
		"parrot" => 30,
		"pig" => 12,
		"pig_zombie" => 36,
		"rabbit" => 18,
		"sheep" => 13,
		"silverfish" => 39,
		"skeleton" => 34,
		"slime" => 37,
		"snow_golem" => 21,
		"stray" => 46,
		"spider" => 35,
		"squid" => 17,
		"villager" => 15,
		"wither_skeleton" => 48,
		"wolf" => 14,
		"zombie" => 32,
		"zombie_pigman" => 36,
		"zombie_villager" => 44
	);

}
