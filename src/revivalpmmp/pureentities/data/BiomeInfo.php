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


use pocketmine\level\biome\Biome;

class BiomeInfo{

	const ALLOWED_ENTITIES_BY_BIOME = [
		16 => [],                            // Beaches
		Biome::BIRCH_FOREST => [],
		28 => [],                            // Birch Forest Hills
		26 => [],                            // Cold Beach
		24 => [Data::NETWORK_IDS["elder_guardian"], Data::NETWORK_IDS["guardian"], Data::NETWORK_IDS["squid"],],                            // Deep Ocean
		Biome::DESERT => [Data::NETWORK_IDS["husk"], Data::NETWORK_IDS["rabbit"]],
		17 => [Data::NETWORK_IDS["husk"], Data::NETWORK_IDS["rabbit"]],                            // Desert Hills
		34 => [],                            // Extreme Hills with Trees or Extreme Hills +
		Biome::FOREST => [Data::NETWORK_IDS["wolf"]],
		18 => [Data::NETWORK_IDS["wolf"]],                            // Forest Hills
		10 => [],                            // Frozen Ocean
		11 => [],                            // Frozen River
		Biome::HELL => [Data::NETWORK_IDS["blaze"], Data::NETWORK_IDS["ghast"], Data::NETWORK_IDS["magma_cube"], Data::NETWORK_IDS["wither_skeleton"], Data::NETWORK_IDS["zombie_pigman"]],
		Biome::ICE_PLAINS => [Data::NETWORK_IDS["rabbit"], Data::NETWORK_IDS["polar_bear"]],            // Ice Flats
		13 => [Data::NETWORK_IDS["rabbit"], Data::NETWORK_IDS["polar_bear"]],                            // Ice Mountains
		21 => [Data::NETWORK_IDS["ocelot"], Data::NETWORK_IDS["parrot"]],                            // Jungle
		23 => [Data::NETWORK_IDS["ocelot"], Data::NETWORK_IDS["parrot"]],                            // Jungle Edge
		22 => [Data::NETWORK_IDS["ocelot"], Data::NETWORK_IDS["parrot"]],                            // Jungle Hills
		37 => [],                            // Mesa
		38 => [],                            // Mesa Rock or Mesa Plateau F
		39 => [],                            // Mesa Clear Rock or Mesa Plateau
		Biome::MOUNTAINS => [Data::NETWORK_IDS["llama"]],            // Extreme Hills
		14 => [Data::NETWORK_IDS["mooshroom"]],                            // Mushroom Island
		15 => [Data::NETWORK_IDS["mooshroom"]],                            // Mushroom Island Shore
		Biome::OCEAN => [Data::NETWORK_IDS["squid"]],
		Biome::PLAINS => [Data::NETWORK_IDS["chicken"], Data::NETWORK_IDS["cow"], Data::NETWORK_IDS["pig"], Data::NETWORK_IDS["rabbit"], Data::NETWORK_IDS["sheep"]],
		32 => [],                            // Redwood Taiga or Mega Taiga
		33 => [],                            // Redwood Taiga Hills or Mega Taiga Hills
		Biome::RIVER => [],
		29 => [],                            // Roofed Forest
		35 => [],                            // Savanna
		36 => [],                            // Savanna Rock or Savanna Plateau
		9 => [],                            // Sky or The End
		Biome::SMALL_MOUNTAINS => [Data::NETWORK_IDS["cow"], Data::NETWORK_IDS["pig"], Data::NETWORK_IDS["sheep"], Data::NETWORK_IDS["wolf"]],        // Smaller Extreme Hills or Extreme Hills Edge
		25 => [],                            // Stone Beach
		Biome::SWAMP => [Data::NETWORK_IDS["slime"]],
		Biome::TAIGA => [Data::NETWORK_IDS["rabbit"], Data::NETWORK_IDS["wolf"]],
		30 => [],                            // Taiga Cold
		31 => [],                            // Taiga Cold Hills
		19 => [],                            // Taiga Hills
	];

	const OVERWORLD_BIOME_EXEMPT = [
		Data::NETWORK_IDS["creeper"],
		Data::NETWORK_IDS["skeleton"],
		Data::NETWORK_IDS["spider"],
		Data::NETWORK_IDS["zombie"]
	];
}