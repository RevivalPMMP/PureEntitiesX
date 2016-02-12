<?php

namespace milk\entitymanager;

use milk\entitymanager\entity\animal\Animal;
use milk\entitymanager\entity\animal\walking\Chicken;
use milk\entitymanager\entity\animal\walking\Cow;
use milk\entitymanager\entity\animal\walking\Mooshroom;
use milk\entitymanager\entity\animal\walking\Ocelot;
use milk\entitymanager\entity\animal\walking\Pig;
use milk\entitymanager\entity\animal\walking\Rabbit;
use milk\entitymanager\entity\animal\walking\Sheep;
use milk\entitymanager\entity\BaseEntity;
use milk\entitymanager\entity\monster\flying\Blaze;
use milk\entitymanager\entity\monster\flying\Ghast;
use milk\entitymanager\entity\monster\Monster;
use milk\entitymanager\entity\monster\walking\CaveSpider;
use milk\entitymanager\entity\monster\walking\Creeper;
use milk\entitymanager\entity\monster\walking\Enderman;
use milk\entitymanager\entity\monster\walking\IronGolem;
use milk\entitymanager\entity\monster\walking\PigZombie;
use milk\entitymanager\entity\monster\walking\Silverfish;
use milk\entitymanager\entity\monster\walking\Skeleton;
use milk\entitymanager\entity\monster\walking\SnowGolem;
use milk\entitymanager\entity\monster\walking\Spider;
use milk\entitymanager\entity\monster\walking\Wolf;
use milk\entitymanager\entity\monster\walking\Zombie;
use milk\entitymanager\entity\monster\walking\ZombieVillager;
use milk\entitymanager\entity\projectile\FireBall;
use milk\entitymanager\task\AutoClearTask;
use milk\entitymanager\task\AutoSpawnTask;
use milk\entitymanager\task\EntitySpawnerTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\entity\Item as ItemEntity;
use pocketmine\block\Block;

class EntityManager extends PluginBase implements Listener{

    public static $data;
    public static $drops;
    public static $spawner;

    public function __construct(){
        $classes = [
            Blaze::class,
            CaveSpider::class,
            Chicken::class,
            Cow::class,
            Creeper::class,
            Enderman::class,
            Ghast::class,
            IronGolem::class,
            //MagmaCube::class,
            Mooshroom::class,
            Ocelot::class,
            Pig::class,
            PigZombie::class,
            Rabbit::class,
            Sheep::class,
            Silverfish::class,
            Skeleton::class,
            //Slime::class,
            SnowGolem::class,
            Spider::class,
            Wolf::class,
            Zombie::class,
            ZombieVillager::class,
            FireBall::class
        ];
        foreach($classes as $name){
            Entity::registerEntity($name);
            if(
                $name == IronGolem::class
                || $name == FireBall::class
                || $name == SnowGolem::class
                || $name == ZombieVillager::class
            ){
                continue;
            }
            $item = Item::get(Item::SPAWN_EGG, $name::NETWORK_ID);
            if(!Item::isCreativeItem($item)){
                Item::addCreativeItem($item);
            }
        }
    }

    public function onEnable(){
        $this->saveDefaultConfig();
        if($this->getConfig()->exists("spawn")){
            $this->saveResource("config.yml", true);
            $this->reloadConfig();
            $this->getServer()->getLogger()->info(TextFormat::GOLD . "[EntityManager]Your config has been updated. Please check \"config.yml\" file and restart the server.");
        }
        self::$data = $this->getConfig()->getAll();

        $path = $this->getDataFolder();
        self::$drops = (new Config($path . "drops.yml", Config::YAML))->getAll();
        self::$spawner = (new Config($path . "spawner.yml", Config::YAML))->getAll();

        /*self::$drops = [
            Zombie::NETWORK_ID => [
                #[Item id, Item meta, Count]
                #example: [Item::FEATHER, 0, "1,10"]
            ],
            Creeper::NETWORK_ID => [

            ],
        ];*/

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[EntityManager]Plugin has been enabled");

        if($this->getData("spawner.turn-on", true)){
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new EntitySpawnerTask($this), $this->getData("spawner.tick", 100));
        }
        if($this->getData("autospawn.turn-on", true)){
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoSpawnTask($this), $this->getData("autospawn.tick", 100));
        }
        if($this->getData("autoclear.turn-on", true)){
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoClearTask($this), $this->getData("autoclear.tick", $this->getData("autoclear.tick", 6000)));
        }
    }

    public function onDisable(){
        $path = $this->getDataFolder();
        $conf = new Config($path . "spawner.yml", Config::YAML);
        $conf->setAll(self::$spawner);
        $conf->save();

        $conf2 = new Config($path . "drops.yml", Config::YAML);
        $conf2->setAll(self::$drops);
        $conf2->save();
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[EntityManager]Plugin has been disable");
    }

    public static function clear(array $type = [BaseEntity::class], Level $level = null){
        $level = $level === null ? Server::getInstance()->getDefaultLevel() : $level;
        foreach($level->getEntities() as $id => $ent){
            foreach($type as $t){
                if(is_a($ent, $t, true)){
                    $ent->close();
                    continue;
                }
            }
        }
    }

    public function getData(string $key, $defaultValue){
        $vars = explode(".", $key);
        $base = array_shift($vars);
        if(!isset(self::$data[$base])){
            return $defaultValue;
        }

        $base = self::$data[$base];
        while(count($vars) > 0){
            $baseKey = array_shift($vars);
            if(!is_array($base) or !isset($base[$baseKey])){
                return $defaultValue;
            }
            $base = $base[$baseKey];
        }
        return $base;
    }

    public static function create($type, Position $source, ...$args) : Entity{
        $chunk = $source->getLevel()->getChunk($source->x >> 4, $source->z >> 4, true);
        if(!$chunk->isGenerated()){
            $chunk->setGenerated();
        }
        if(!$chunk->isPopulated()){
            $chunk->setPopulated();
        }

        $nbt = new CompoundTag("", [
            "Pos" => new ListTag("Pos", [
                new DoubleTag("", $source->x),
                new DoubleTag("", $source->y),
                new DoubleTag("", $source->z)
            ]),
            "Motion" => new ListTag("Motion", [
                new DoubleTag("", 0),
                new DoubleTag("", 0),
                new DoubleTag("", 0)
            ]),
            "Rotation" => new ListTag("Rotation", [
                new FloatTag("", $source instanceof Location ? $source->yaw : 0),
                new FloatTag("", $source instanceof Location ? $source->pitch : 0)
            ]),
        ]);

        return Entity::createEntity($type, $chunk, $nbt, ...$args);
    }

    public function PlayerInteractEvent(PlayerInteractEvent $ev){
        if($ev->getFace() == 255 || $ev->getAction() != PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;
        $item = $ev->getItem();
        $player = $ev->getPlayer();
        $pos = $ev->getBlock()->getSide($ev->getFace());

        if($item->getId() === Item::SPAWN_EGG){
            $entity = self::create($item->getDamage(), $pos);
            if($entity != null){
                $entity->spawnToAll();
            }

            if($player->isSurvival()){
                $item->count--;
                $player->getInventory()->setItemInHand($item);
            }
            $ev->setCancelled();
        }elseif($item->getId() === Item::MONSTER_SPAWNER){
            self::$spawner["{$pos->x}:{$pos->y}:{$pos->z}:{$pos->level->getFolderName()}"] = [
                "radius" => 5,
                "mob-list" => [
                    "Cow", "Pig", "Sheep", "Chicken",
                    "Zombie", "Creeper", "Skeleton", "Spider", "PigZombie", "Enderman"
                ],
            ];
        }
    }

    public function BlockBreakEvent(BlockBreakEvent $ev){
        $pos = $ev->getBlock();
        if($ev->isCancelled() || $pos->getId() != Item::MONSTER_SPAWNER){
            return;
        }

        if(isset(self::$spawner["{$pos->x}:{$pos->y}:{$pos->z}"])){
            unset(self::$spawner["{$pos->x}:{$pos->y}:{$pos->z}"]);
        }elseif(isset(self::$spawner["{$pos->x}:{$pos->y}:{$pos->z}:{$pos->getLevel()->getFolderName()}"])){
            unset(self::$spawner["{$pos->x}:{$pos->y}:{$pos->z}:{$pos->getLevel()->getFolderName()}"]);
        }

        if(
            ($ev->getBlock()->getId() == Block::STONE or $ev->getBlock()->getId() == Block::STONE_BRICK or $ev->getBlock()->getId() == Block::STONE_WALL or $ev->getBlock()->getId() == Block::STONE_BRICK_STAIRS)
            && ($ev->getBlock()->getLightLevel() < 12 and mt_rand(1,3) < 2)
        ){
            $entity = self::create("Silverfish", $pos);
            if($entity != null){
                $entity->spawnToAll();
            }
        }
    }

    public function ExplosionPrimeEvent(ExplosionPrimeEvent $ev){
        $ev->setCancelled(!$this->getData("entity.explode", false));
    }

    public function EntityDeathEvent(EntityDeathEvent $ev){
        $entity = $ev->getEntity();
        if(!$entity instanceof BaseEntity or !isset(self::$drops[$entity::NETWORK_ID])){
            return;
        }

        $drops = [];
        foreach(self::$drops[$entity::NETWORK_ID] as $key => $data){
            if(!isset($data[0]) || !isset($data[1]) || !isset($data[2])){
                unset(self::$drops[$entity::NETWORK_ID][$key]);
                continue;
            }

            $count = explode(",", $data[2]);
            $item = Item::get($data[0], $data[1]);
            $item->setCount(max(mt_rand(...$count), 0));
            $drops[] = $item;
        }
        $ev->setDrops($drops);
    }

    public function onCommand(CommandSender $i, Command $cmd, $label, array $sub){
        $output = "[EntityManager]";
        switch(array_shift($sub)){
            case "remove":
                if(!$i->hasPermission("entitymanager.command.remove")){
                    $i->sendMessage(TextFormat::RED . "You do not have permission to use this command");
                    return true;
                }

                if(isset($sub[0])){
                    $level = $this->getServer()->getLevelByName($sub[0]);
                }else{
                    $level = $i instanceof Player ? $i->getLevel() : null;
                }

                self::clear([BaseEntity::class, Projectile::class, ItemEntity::class], $level);
                $output .= "All spawned entities were removed";
                break;
            case "check":
                if(!$i->hasPermission("entitymanager.command.check")){
                    $i->sendMessage(TextFormat::RED . "You do not have permission to use this command");
                    return true;
                }

                $mob = 0;
                $animal = 0;
                $item = 0;
                $projectile = 0;
                $other = 0;
                if(isset($sub[0])){
                    $level = $this->getServer()->getLevelByName($sub[0]);
                }else{
                    $level = $i instanceof Player ? $i->getLevel() : $this->getServer()->getDefaultLevel();
                }

                foreach($level->getEntities() as $id => $ent) {
                    if($ent instanceof Monster){
                        $mob++;
                    }elseif($ent instanceof Animal){
                        $animal++;
                    }elseif($ent instanceof ItemEntity){
                        $item++;
                    }elseif($ent instanceof Projectile){
                        $projectile++;
                    }elseif(!$ent instanceof Player){
                        $other++;
                    }
                }

                $output = "--- All entities in Level \"{$level->getName()}\" ---\n";
                $output .= TextFormat::YELLOW . "Monster: $mob\n";
                $output .= TextFormat::YELLOW . "Animal: $animal\n";
                $output .= TextFormat::YELLOW . "Items: $item\n";
                $output .= TextFormat::YELLOW . "Projectiles: $projectile\n";
                $output .= TextFormat::YELLOW . "Others: $other\n";
                break;
            case "create":
                if(!$i->hasPermission("entitymanager.command.create")){
                    $i->sendMessage(TextFormat::RED . "You do not have permission to use this command");
                    return true;
                }

                if(!isset($sub[0]) or (!is_numeric($sub[0]) and gettype($sub[0]) !== "string")){
                    $output .= "Entity's name is incorrect";
                    break;
                }

                $pos = null;
                if(count($sub) >= 4){
                    $level = $this->getServer()->getDefaultLevel();
                    if(isset($sub[4]) && ($k = $this->getServer()->getLevelByName($sub[4]))){
                        $level = $k;
                    }elseif($i instanceof Player){
                        $level = $i->getLevel();
                    }
                    $pos = new Position($sub[1], $sub[2], $sub[3], $level);
                }elseif($i instanceof Player){
                    $pos = $i->getPosition();
                }

                if($pos == null){
                    $output .= "usage: /$label create <id/name> (x) (y) (z) (level)";
                    break;
                }

                $entity = self::create($sub[0], $pos);
                if($entity == null){
                    $output .= "An error occurred while summoning entity";
                    break;
                }
                $entity->spawnToAll();
                break;
            default:
                $output .= "usage: /$label <remove/check/create>";
                break;
        }
        $i->sendMessage($output);
        return true;
    }

}