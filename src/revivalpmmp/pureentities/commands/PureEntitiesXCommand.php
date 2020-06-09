<?php
declare(strict_types=1);

namespace revivalpmmp\pureentities\commands;


use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use revivalpmmp\pureentities\PureEntities;

abstract class PureEntitiesXCommand extends PluginCommand{

	public function __construct(string $name){
		parent::__construct($name, PureEntities::getInstance());
	}

	/**
	 * @return PureEntities
	 */
	public function getPlugin() : Plugin{
		return PureEntities::getInstance();
	}

	protected function sendUsage(CommandSender $sender) : void{
		$sender->sendMessage($this->usageMessage);
	}

}