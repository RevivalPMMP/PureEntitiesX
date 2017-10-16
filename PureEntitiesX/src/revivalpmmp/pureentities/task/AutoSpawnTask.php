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


namespace revivalpmmp\pureentities\task;

use pocketmine\level\Position;
use pocketmine\scheduler\PluginTask;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\task\spawners\animal\ChickenSpawner;
use revivalpmmp\pureentities\task\spawners\animal\CowSpawner;
use revivalpmmp\pureentities\task\spawners\animal\ParrotSpawner;
use revivalpmmp\pureentities\task\spawners\monster\BlazeSpawner;
use revivalpmmp\pureentities\task\spawners\monster\CaveSpiderSpawner;
use revivalpmmp\pureentities\task\spawners\monster\CreeperSpawner;
use revivalpmmp\pureentities\task\spawners\animal\HorseSpawner;
use revivalpmmp\pureentities\task\spawners\animal\OcelotSpawner;
use revivalpmmp\pureentities\task\spawners\animal\PigSpawner;
use revivalpmmp\pureentities\task\spawners\animal\RabbitSpawner;
use revivalpmmp\pureentities\task\spawners\animal\SheepSpawner;
use revivalpmmp\pureentities\task\spawners\monster\EndermanSpawner;
use revivalpmmp\pureentities\task\spawners\monster\GhastSpawner;
use revivalpmmp\pureentities\task\spawners\monster\IronGolemSpawner;
use revivalpmmp\pureentities\task\spawners\monster\MagmaCubeSpawner;
use revivalpmmp\pureentities\task\spawners\monster\PigZombieSpawner;
use revivalpmmp\pureentities\task\spawners\monster\SkeletonSpawner;
use revivalpmmp\pureentities\task\spawners\monster\SlimeSpawner;
use revivalpmmp\pureentities\task\spawners\monster\SpiderSpawner;
use revivalpmmp\pureentities\task\spawners\monster\WolfSpawner;
use revivalpmmp\pureentities\task\spawners\monster\ZombieSpawner;

class AutoSpawnTask extends PluginTask {

    private $plugin;

    /** @var array $spawnerClasses */
    private $spawnerClasses = [];
    /** @var array $spawnerWorlds */
    private $spawnerWorlds = [];

    public function __construct(PureEntities $plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->spawnerWorlds = PluginConfiguration::getInstance()->getEnabledWorlds();
        $this->prepareSpawnerClasses();
    }

    public function onRun(int $currentTick) {
        PureEntities::logOutput("AutoSpawnTask: onRun ($currentTick)", PureEntities::DEBUG);

        foreach ($this->plugin->getServer()->getLevels() as $level) {
            if (count($this->spawnerWorlds) > 0 and !in_array($level->getName(), $this->spawnerWorlds)){
                continue;
            }
            if (count($level->getPlayers()) > 0) {
                foreach ($level->getPlayers() as $player) {
                    foreach ($this->spawnerClasses as $spawnerClass) {
                        $locationValid = false;
                        $pass = 1;
                        while (!$locationValid) {

                            // Random method used to get 8 block difference from player to entity spawn)
                            $x = $player->x + (random_int(8, 20) * (random_int(0, 1) === 0 ? 1 : -1));
                            $z = $player->z + (random_int(8, 20) * (random_int(0, 1) === 0 ? 1 : -1));
                            $y = $player->y;

                            // search up- and downwards the current player's y-coordinate to find a valid block!
                            $correctedPosition = PureEntities::getSuitableHeightPosition($x, $y, $z, $level);
                            if ($correctedPosition !== null) {
                                $pos = new Position($correctedPosition->x, $correctedPosition->y - 1, $correctedPosition->z, $level);
                                $spawnerClass->spawn($pos, $player);
                                $locationValid = true;
                            } else {
                                PureEntities::logOutput("AutoSpawnTask: suitable spawn coordinate not found [search.x:$x] [search.y:$y] [search.z:$z] [pass:$pass]", PureEntities::WARN);
                                $pass++;
                            }
                        }
                    }
                }
            }
        }
    }

    private function prepareSpawnerClasses() {
        // $this->spawnerClasses[] = new BatSpawner();
        $this->spawnerClasses[] = new ChickenSpawner();
        $this->spawnerClasses[] = new CowSpawner();
        $this->spawnerClasses[] = new HorseSpawner();
        $this->spawnerClasses[] = new OcelotSpawner();
        // $this->spawnerClasses[] = new ParrotSpawner();
        $this->spawnerClasses[] = new PigSpawner();
        $this->spawnerClasses[] = new RabbitSpawner();
        $this->spawnerClasses[] = new SheepSpawner();

        // monster spawners ...
        $this->spawnerClasses[] = new BlazeSpawner();
        $this->spawnerClasses[] = new CaveSpiderSpawner();
        $this->spawnerClasses[] = new CreeperSpawner();
        $this->spawnerClasses[] = new EndermanSpawner();
        $this->spawnerClasses[] = new GhastSpawner();
        $this->spawnerClasses[] = new IronGolemSpawner();
        $this->spawnerClasses[] = new MagmaCubeSpawner();
        $this->spawnerClasses[] = new PigZombieSpawner();
        $this->spawnerClasses[] = new SkeletonSpawner();
        $this->spawnerClasses[] = new SlimeSpawner();
        $this->spawnerClasses[] = new SpiderSpawner();
        $this->spawnerClasses[] = new WolfSpawner();
        $this->spawnerClasses[] = new ZombieSpawner();

    }

}