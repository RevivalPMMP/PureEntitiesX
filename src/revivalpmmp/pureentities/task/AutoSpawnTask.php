<?php

namespace revivalpmmp\pureentities\task;

use pocketmine\level\Position;
use pocketmine\scheduler\PluginTask;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\animal\BatSpawner;
use revivalpmmp\pureentities\task\spawners\animal\ChickenSpawner;
use revivalpmmp\pureentities\task\spawners\animal\CowSpawner;
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

    public function __construct(PureEntities $plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->prepareSpawnerClasses();
    }

    public function onRun($currentTick){
        PureEntities::logOutput("AutoSpawnTask: onRun ($currentTick)",PureEntities::DEBUG);

        foreach($this->plugin->getServer()->getLevels() as $level) {
            if (count($level->getPlayers()) > 0) {
                foreach ($level->getPlayers() as $player) {
                    foreach ($this->spawnerClasses as $spawnerClass) {

                        $x = $player->x + mt_rand(-20, 20);
                        $z = $player->z + mt_rand(-20, 20);
                        $y = $level->getHighestBlockAt($x, $z);

                        $correctedPosition = PureEntities::getFirstAirAbovePosition($x, $y, $z, $level); // returns the AIR block found upwards (it seems, highest block is not working :()
                        $pos = new Position($correctedPosition->x, $correctedPosition->y - 1, $correctedPosition->z, $level);
                        $spawnerClass->spawn($pos, $player);
                    }
                }
            }
        }
    }

    private function prepareSpawnerClasses () {
        // $this->spawnerClasses[] = new BatSpawner();
        $this->spawnerClasses[] = new ChickenSpawner();
        $this->spawnerClasses[] = new CowSpawner();
        $this->spawnerClasses[] = new HorseSpawner();
        $this->spawnerClasses[] = new OcelotSpawner();
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