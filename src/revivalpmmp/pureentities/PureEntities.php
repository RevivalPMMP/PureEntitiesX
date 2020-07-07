<?php
declare(strict_types=1);

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

namespace revivalpmmp\pureentities;

use LogLevel;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use revivalpmmp\pureentities\block\MonsterSpawnerPEX;
use revivalpmmp\pureentities\commands\RemoveEntitiesCommand;
use revivalpmmp\pureentities\commands\SummonCommand;
use revivalpmmp\pureentities\data\Color;
use revivalpmmp\pureentities\entity\animal\flying\Bat;
use revivalpmmp\pureentities\entity\animal\flying\Parrot;
use revivalpmmp\pureentities\entity\animal\swimming\Cod;
use revivalpmmp\pureentities\entity\animal\swimming\Dolphin;
use revivalpmmp\pureentities\entity\animal\swimming\Pufferfish;
use revivalpmmp\pureentities\entity\animal\swimming\Salmon;
use revivalpmmp\pureentities\entity\animal\swimming\Squid;
use revivalpmmp\pureentities\entity\animal\swimming\Tropicalfish;
use revivalpmmp\pureentities\entity\animal\walking\Chicken;
use revivalpmmp\pureentities\entity\animal\walking\Cow;
use revivalpmmp\pureentities\entity\animal\walking\Donkey;
use revivalpmmp\pureentities\entity\animal\walking\Horse;
use revivalpmmp\pureentities\entity\animal\walking\Llama;
use revivalpmmp\pureentities\entity\animal\walking\Mooshroom;
use revivalpmmp\pureentities\entity\animal\walking\Mule;
use revivalpmmp\pureentities\entity\animal\walking\Ocelot;
use revivalpmmp\pureentities\entity\animal\walking\Pig;
use revivalpmmp\pureentities\entity\animal\walking\Rabbit;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;
use revivalpmmp\pureentities\entity\animal\walking\SkeletonHorse;
use revivalpmmp\pureentities\entity\animal\walking\Villager;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\entity\monster\flying\Blaze;
use revivalpmmp\pureentities\entity\monster\flying\Ghast;
use revivalpmmp\pureentities\entity\monster\flying\Vex;
use revivalpmmp\pureentities\entity\monster\jumping\MagmaCube;
use revivalpmmp\pureentities\entity\monster\jumping\Slime;
use revivalpmmp\pureentities\entity\monster\walking\CaveSpider;
use revivalpmmp\pureentities\entity\monster\walking\Creeper;
use revivalpmmp\pureentities\entity\monster\walking\Enderman;
use revivalpmmp\pureentities\entity\monster\walking\Endermite;
use revivalpmmp\pureentities\entity\monster\walking\Evoker;
use revivalpmmp\pureentities\entity\monster\walking\Husk;
use revivalpmmp\pureentities\entity\monster\walking\IronGolem;
use revivalpmmp\pureentities\entity\monster\walking\PigZombie;
use revivalpmmp\pureentities\entity\monster\walking\PolarBear;
use revivalpmmp\pureentities\entity\monster\walking\Shulker;
use revivalpmmp\pureentities\entity\monster\walking\Silverfish;
use revivalpmmp\pureentities\entity\monster\walking\Skeleton;
use revivalpmmp\pureentities\entity\monster\walking\SnowGolem;
use revivalpmmp\pureentities\entity\monster\walking\Spider;
use revivalpmmp\pureentities\entity\monster\walking\Stray;
use revivalpmmp\pureentities\entity\monster\walking\Vindicator;
use revivalpmmp\pureentities\entity\monster\walking\Witch;
use revivalpmmp\pureentities\entity\monster\walking\WitherSkeleton;
use revivalpmmp\pureentities\entity\monster\walking\Wolf;
use revivalpmmp\pureentities\entity\monster\walking\Zombie;
use revivalpmmp\pureentities\entity\monster\walking\ZombiePigman;
use revivalpmmp\pureentities\entity\monster\walking\ZombieVillager;
use revivalpmmp\pureentities\entity\projectile\LargeFireball;
use revivalpmmp\pureentities\entity\projectile\SmallFireball;
use revivalpmmp\pureentities\event\CreatureSpawnEvent;
use revivalpmmp\pureentities\event\EventListener;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\task\AutoSpawnTask;
use revivalpmmp\pureentities\task\EndermanLookingTask;
use revivalpmmp\pureentities\task\InteractionTask;
use revivalpmmp\pureentities\tile\MobSpawner;
use revivalpmmp\pureentities\utils\MobEquipper;

class PureEntities extends PluginBase{

	/** @var  PureEntities $instance */
	private static $instance;

	/** @var string[] */
	private static $registeredClasses = [];

	/**
	 * Returns the plugin instance to get access to config e.g.
	 * @return PureEntities the current instance of the plugin main class
	 */
	public static function getInstance() : PureEntities{
		return self::$instance;
	}


	public function onLoad(){
		$temp = [
			Bat::class,
			Blaze::class,
			CaveSpider::class,
			Chicken::class,
			Cod::class,
			Cow::class,
			Creeper::class,
			Dolphin::class,
			Donkey::class,
			//ElderGuardian::class,
			//EnderCharge::class,
			//EnderDragon::class,
			Enderman::class,
			Endermite::class,
			Evoker::class,
			Ghast::class,
			//Guardian::class,
			Horse::class,
			Husk::class,
			IronGolem::class,
			Llama::class,
			LargeFireball::class,
			MagmaCube::class,
			Mooshroom::class,
			Mule::class,
			Ocelot::class,
			Parrot::class,
			Pig::class,
			PigZombie::class,
			PolarBear::class,
			Pufferfish::class,
			Rabbit::class,
			Salmon::class,
			Sheep::class,
			Shulker::class,
			Silverfish::class,
			Skeleton::class,
			SkeletonHorse::class,
			Slime::class,
			SmallFireball::class,
			SnowGolem::class,
			Spider::class,
			Squid::class,
			Stray::class,
			Tropicalfish::class,
			Vex::class,
			Villager::class,
			Vindicator::class,
			Witch::class,
			//Wither::class
			WitherSkeleton::class,
			Wolf::class,
			Zombie::class,
			ZombiePigman::class,
			ZombieVillager::class
		];

		foreach($temp as $class){
			self::$registeredClasses[strtolower($this->getShortClassName($class))] = $class;
		}

		foreach(self::$registeredClasses as $name){
			Entity::registerEntity($name);
			if(
				$name === IronGolem::class
				|| $name === LargeFireball::class
				|| $name === SmallFireball::class
				|| $name === SnowGolem::class
				|| $name === ZombieVillager::class
			){
				continue;
			}
			$item = Item::get(Item::SPAWN_EGG, $name::NETWORK_ID);
			if(!Item::isCreativeItem($item)){
				Item::addCreativeItem($item);
			}
		}

		Tile::registerTile(MobSpawner::class);
		BlockFactory::registerBlock(new MonsterSpawnerPEX(), true);

		$this->saveDefaultConfig();

		Color::init();

		self::$instance = $this;

		$this->getServer()->getLogger()->info("[PureEntitiesX] Originally written by milk0417. Currently maintained by RevivalPMMP for PMMP 'REDACTED'.");
	}

	public function onEnable(){
		new PluginConfiguration($this); // create plugin configuration
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		if(PluginConfiguration::getInstance()->getEnableSpawning()){
			$this->getScheduler()->scheduleRepeatingTask(new AutoSpawnTask($this), $this->getConfig()->getNested("spawn-task.trigger-ticks", 1000));
		}
		if(PluginConfiguration::getInstance()->getEnableLookingTasks()){
			$this->getScheduler()->scheduleRepeatingTask(new InteractionTask($this), $this->getConfig()->getNested("performance.check-interactive-ticks", 10));
			$this->getScheduler()->scheduleRepeatingTask(new EndermanLookingTask($this), $this->getConfig()->getNested("performance.check-enderman-looking", 10));
		}

		$this->getServer()->getCommandMap()->register("PureEntitiesX", new SummonCommand());
		$this->getServer()->getCommandMap()->register("PureEntitiesX", new RemoveEntitiesCommand());
	}

	/**
	 * @param int|string $type
	 * @param Position   $source
	 * @param array      $args
	 *
	 * @return Entity
	 */
	public static function create($type, Position $source, ...$args){

		$nbt = Entity::createBaseNBT($source->asVector3(), null, $source instanceof Location ? $source->yaw : 0, $source instanceof Location ? $source->pitch : 0);

		return Entity::createEntity($type, $source->getLevel(), $nbt, ...$args);
	}

	/**
	 * @param Position    $pos
	 * @param int         $entityid
	 * @param Level       $level
	 * @param string      $type
	 * @param bool        $baby
	 * @param Entity|null $parentEntity
	 * @param Player|null $owner
	 * @return null|Entity
	 */
	public function scheduleCreatureSpawn(Position $pos, int $entityid, Level $level, string $type, bool $baby = false, Entity $parentEntity = null, Player $owner = null){
		$this->getServer()->getPluginManager()->callEvent($event = new CreatureSpawnEvent($this, $pos, $entityid, $level, $type));

		if($event->isCancelled()){
			return null;
		}else{
			$entity = self::create($entityid, $pos);
			if($entity !== null){
				if($entity instanceof IntfCanBreed and $baby and $entity->getBreedingComponent() !== false){
					$entity->getBreedingComponent()->setAge(-6000); // in 5 minutes it will be a an adult (atm only sheep)
					if($parentEntity !== null){
						$entity->getBreedingComponent()->setParent($parentEntity);
					}
				}
				// new: a baby's parent (like a wolf) may belong to a player - if so, the baby is also owned by the player!
				if($owner !== null && $entity instanceof IntfTameable){
					$entity->setTamed(true);
					$entity->setOwner($owner);
				}
				self::logOutput("PureEntities: scheduleCreatureSpawn [type:$entity] [baby:$baby]");
				$entity->spawnToAll();

				// additionally: mob equipment
				if($entity instanceof BaseEntity){
					MobEquipper::equipMob($entity);
				}

				return $entity;
			}
		}
	}

	/**
	 * Logs an output to the plugin's logfile ...
	 * @param string $message the output to be appended
	 * @param string $type the type of output to log
	 */
	public static function logOutput(string $message, string $type = LogLevel::DEBUG) : void{
		$logger = self::getInstance()->getLogger();
		switch($type){
			case LogLevel::EMERGENCY:
				$logger->emergency($message);
				break;
			case LogLevel::ALERT:
				$logger->alert($message);
				break;
			case LogLevel::CRITICAL:
				$logger->critical($message);
				break;
			case LogLevel::ERROR:
				$logger->error($message);
				break;
			case LogLevel::WARNING:
				$logger->warning($message);
				break;
			case LogLevel::NOTICE:
				$logger->notice($message);
				break;
			case LogLevel::INFO:
				$logger->info($message);
				break;
			case LogLevel::DEBUG:
			default:
				$logger->debug($message);
				break;
		}
	}

	/**
	 * Returns a suitable Y-position for spawning an entity, starting from the given coordinates.
	 *
	 *
	 *
	 * @param float|int $x the x position to start search
	 * @param float|int $y the y position to start search
	 * @param float|int $z the z position to start searching
	 * @param Level     $level Level the level object to search in
	 * @return null|Position               NULL if no valid position was found or the final AIR spawn position
	 */
	public static function getSuitableHeightPosition($x, $y, $z, Level $level){
		$startingY = $y;
		$previousBlockIsAir = $level->getBlockIdAt($x, $y + 1, $z) === BlockIds::AIR;
		$cycleIncomplete = true;
		do{
			$id = $level->getBlockIdAt($x, $y, $z);
			if($id === BlockIds::AIR){
				$previousBlockIsAir = true;
			}elseif($previousBlockIsAir){
				return new Position($x, $y, $z, $level);
			}else{
				$previousBlockIsAir = false;
			}
			if(--$y < 1){
				$y = $level->getHighestBlockAt($x, $z) - 1;
				$previousBlockIsAir = $level->getBlockIdAt($x, $y + 1, $z) === BlockIds::AIR;
			}
			if($y === $startingY){
				$cycleIncomplete = false;
			}
		}while($cycleIncomplete);
		self::logOutput("No valid spawn position found ");
		return null;
	}

	/**
	 * Returns the "short" name of a class without namespace ...
	 *
	 * @param string $longClassName
	 * @return string
	 */
	private function getShortClassName(string $longClassName) : string{
		$short = "";
		$longClassName = strtok($longClassName, "\\");
		while($longClassName !== false){
			$short = $longClassName;
			$longClassName = strtok("\\");
		}
		return $short;
	}

	/**
	 * @return array
	 */
	public static function getRegisteredClasses() : array{
		return self::$registeredClasses;
	}

	public function getRegisteredClassNameFromShortName(string $shortName) : ?string{
		return self::$registeredClasses[strtolower($shortName)] ?? null;
	}

	public static function getPositionNearPlayer(Player $player, int $minimumDistanceToPlayer = 8, int $maximumDistanceToPlayer = 40) : Position{
		// Random method used to get 8 block difference from player to entity spawn)
		$x = $player->x + (random_int($minimumDistanceToPlayer, $maximumDistanceToPlayer) * (random_int(0, 1) === 0 ? 1 : -1));
		$z = $player->z + (random_int($minimumDistanceToPlayer, $maximumDistanceToPlayer) * (random_int(0, 1) === 0 ? 1 : -1));

		return new Position($x, $player->y, $z, $player->getLevel());
	}
}