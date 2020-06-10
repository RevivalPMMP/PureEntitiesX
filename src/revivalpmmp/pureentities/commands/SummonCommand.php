<?php
declare(strict_types=1);

namespace revivalpmmp\pureentities\commands;


use pocketmine\command\CommandSender;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\PureEntities;

class SummonCommand extends PureEntitiesXCommand{

	public function __construct(){
		parent::__construct("summon");
		$this->setPermission("pureentities.command.pesummon");
		$this->setDescription("Summons a mob of the requested type.");
		$this->setUsage("summon <type> [x] [y] [z] [world]");
		$this->setAliases(["pesummon"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return;
		}
		if(!$this->validateArgs($sender, $args)){
			return;
		}
		$className = PureEntities::getInstance()->getRegisteredClassNameFromShortName($args[0]);
		$level = Server::getInstance()->getLevelByName($args[4]);
		$pos = new Position((float) $args[1], (float) $args[2], (float) $args[3], $level);
		$mob = PureEntities::getInstance()->scheduleCreatureSpawn($pos, $className::NETWORK_ID, $level, "");
		if($mob instanceof BaseEntity){
			$sender->sendMessage("Successfully spawned " . $mob->getName() . "!");
		}
	}

	private function validateArgs(CommandSender $sender, array &$args) : bool{
		$valid = true;
		if(!isset($args[0])){
			$this->sendUsage($sender);
			return false;
		}
		if(!$sender instanceof Player and count($args) !== 5){
			$sender->sendMessage(TextFormat::RED . "When using this command from the console, the coordinates and world must be provided");
			$sender->sendMessage(TextFormat::GOLD . $this->usageMessage);
			return false;
		}
		if(PureEntities::getInstance()->getRegisteredClassNameFromShortName($args[0]) === null){
			$sender->sendMessage(TextFormat::GOLD . $args[0] . TextFormat::RED . " is not a valid mob type.");
			$valid = false;
		}
		if(isset($args[1])){
			if(!is_numeric($args[1]) or !isset($args[2]) or !is_numeric($args[2]) or !isset($args[3]) or !is_numeric($args[3])){
				$sender->sendMessage(TextFormat::RED . "The provided coordinates appear to be invalid.");
				$valid = false;
			}
		}else{
			/** @var $sender Player */
			$args[1] = $sender->x;
			$args[2] = $sender->y;
			$args[3] = $sender->z;
		}
		if(isset($args[4])){
			Server::getInstance()->loadLevel($args[4]);
			if(!Server::getInstance()->getLevelByName($args[4]) instanceof Level){
				$sender->sendMessage(TextFormat::GOLD . $args[4] . TextFormat::RED . " is not a valid world.");
				$valid = false;
			}
		}else{
			/** @var $sender Player */
			$args[4] = $sender->getLevel()->getName();
		}

		if(!$valid){
			$this->sendUsage($sender);
		}
		return $valid;
	}
}