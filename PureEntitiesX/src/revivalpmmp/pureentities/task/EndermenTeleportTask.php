<?php
namespace revivalpmmp\pureentities\task;

use pocketmine\scheduler\PluginTask;
use revivalpmmp\pureentities\PureEntities;

/**
 * Class EndermenTeleportTask
 *
 * As the name says:
 *
 * @package revivalpmmp\pureentities\task
 */
class EndermenTeleportTask extends PluginTask {
	private $plugin;
	public function __construct(PureEntities $plugin) {
		parent::__construct($plugin);
		$this->plugin = $plugin;
	}

	public function onRun(int $currentTick){
		//
	}
}