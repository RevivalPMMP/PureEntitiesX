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


interface Data {

    // Network IDs
    /* Individual Network ID Constants will soon be phased out
     * and replaced with the NETWORK_IDS array.
     */
    const CHICKEN = 10;
    const COW = 11;
    const PIG = 12;
    const SHEEP = 13;
    const VILLAGER = 15;
    const MOOSHROOM = 16;
    const RABBIT = 18;
    const OCELOT = 22;
    const HORSE = 23;
    const DONKEY = 24;
    const ZOMBIE = 32;
    const CREEPER = 33;
    const SKELETON = 34;
    const SPIDER = 35;
    const ZOMBIE_PIGMAN = 36;
    const ENDERMAN = 38;
    const ZOMBIE_VILLAGER = 44;
    const WITHER_SKELETON = 48;
    const WOLF = 14;
    const SQUID = 17;
    const IRON_GOLEM = 20;
    const SNOW_GOLEM = 21;
    const MULE = 25;
    const STRAY = 46;
    const BAT = 19;
    const PIG_ZOMBIE = 36;
    const SILVERFISH = 39;
    const CAVE_SPIDER = 40;
    const GHAST = 41;
    const BLAZE = 43;
    const MAGMA_CUBE = 42;
    const HUSK = 47;
    const GUARDIAN = 49;
    const ELDER_GUARDIAN = 50;
    const SLIME = 37;
    const FIRE_BALL = 85;
    const PARROT = 30;

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


    // Entity Widths
    const WIDTHS = array (

        self::NETWORK_IDS["bat"] => 0.484,
        self::NETWORK_IDS["blaze"] => 1.25,
        self::NETWORK_IDS["cave_spider"] => 1.438,
        self::NETWORK_IDS["chicken"] => 1,
        self::NETWORK_IDS["cow"] => 1.5,
        self::NETWORK_IDS["creeper"] => 0.7,
        self::NETWORK_IDS["donkey"] => 1.2,
        self::NETWORK_IDS["elder_guardian"] => 0,
        self::NETWORK_IDS["enderman"] => 1.094,
        self::NETWORK_IDS["fire_ball"] => 0.5,
        self::NETWORK_IDS["ghast"] => 4.5,
        self::NETWORK_IDS["guardian"] => 0,
        self::NETWORK_IDS["horse"] => 1.3,
        self::NETWORK_IDS["husk"] => 1.031,
        self::NETWORK_IDS["iron_golem"] => 2.688,
        self::NETWORK_IDS["magma_cube"] => 1.2,
        self::NETWORK_IDS["mooshroom"] => 1.781,
        self::NETWORK_IDS["mule"] => 1.2,
        self::NETWORK_IDS["ocelot"] => 0.8,
        self::NETWORK_IDS["parrot"] => 0.5,
        self::NETWORK_IDS["pig"] => 1.5,
        self::NETWORK_IDS["pig_zombie"] => 1.125,
        self::NETWORK_IDS["rabbit"] => 0.5,
        self::NETWORK_IDS["sheep"] => 1.2,
        self::NETWORK_IDS["silverfish"] => 1.094,
        self::NETWORK_IDS["skeleton"] =>0.875,
        self::NETWORK_IDS["slime"] => 1.2,
        self::NETWORK_IDS["snow_golem"] => 1.281,
        self::NETWORK_IDS["stray"] => 0.875,
        self::NETWORK_IDS["spider"] => 2.062,
        self::NETWORK_IDS["squid"] => 0,
        self::NETWORK_IDS["villager"] => 0.938,
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
        self::NETWORK_IDS["elder_guardian"] => 0,
        self::NETWORK_IDS["enderman"] => 2.875,
        self::NETWORK_IDS["fire_ball"] => 0.5,
        self::NETWORK_IDS["ghast"] => 4.5,
        self::NETWORK_IDS["guardian"] => 0,
        self::NETWORK_IDS["horse"] => 1.5,
        self::NETWORK_IDS["husk"] => 2.0,
        self::NETWORK_IDS["iron_golem"] => 1.625,
        self::NETWORK_IDS["magma_cube"] => 1.2,
        self::NETWORK_IDS["mooshroom"] => 1.875,
        self::NETWORK_IDS["mule"] => 1.562,
        self::NETWORK_IDS["ocelot"] => 0.8,
        self::NETWORK_IDS["parrot"] => 0.9,
        self::NETWORK_IDS["pig"] => 1.0,
        self::NETWORK_IDS["pig_zombie"] => 2.03,
        self::NETWORK_IDS["rabbit"] => 0.5,
        self::NETWORK_IDS["sheep"] => 0.6,
        self::NETWORK_IDS["silverfish"] => 0.438,
        self::NETWORK_IDS["skeleton"] =>2.0,
        self::NETWORK_IDS["slime"] => 1.2,
        self::NETWORK_IDS["snow_golem"] => 1.875,
        self::NETWORK_IDS["stray"] => 2.0,
        self::NETWORK_IDS["spider"] => 0.781,
        self::NETWORK_IDS["squid"] => 0.0,
        self::NETWORK_IDS["villager"] => 2.0,
        self::NETWORK_IDS["wither_skeleton"] => 2.0,
        self::NETWORK_IDS["wolf"] => 0.969,
        self::NETWORK_IDS["zombie"] => 2.01,
        self::NETWORK_IDS["zombie_pigman"] => 2.0,
        self::NETWORK_IDS["zombie_villager"] => 2.125
    );

    // Contains biomes that each entity is allowed to
    // spawn into automatically.
    const ALLOWED_BIOMES_BY_ENTITY_NAME = array(
        "bat" => array(

        ),
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
