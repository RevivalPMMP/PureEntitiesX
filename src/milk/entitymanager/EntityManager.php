<?php

namespace milk\entitymanager;

use milk\entitymanager\entity\Animal;
use milk\entitymanager\entity\Chicken;
use milk\entitymanager\entity\Cow;
use milk\entitymanager\entity\Pig;
use milk\entitymanager\entity\Sheep;
use milk\entitymanager\entity\BaseEntity;
use milk\entitymanager\entity\Creeper;
use milk\entitymanager\entity\Enderman;
use milk\entitymanager\entity\Monster;
use milk\entitymanager\entity\PigZombie;
use milk\entitymanager\entity\Skeleton;
use milk\entitymanager\entity\Spider;
use milk\entitymanager\entity\Zombie;
use milk\entitymanager\task\SpawnEntityTask;
use milk\entitymanager\task\UpdateEntityTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\entity\Item as ItemEntity;

class EntityManager extends PluginBase implements Listener{

    public $path;

    public static $data;
    public static $drops;
    public static $spawnerData;

    /** @var BaseEntity[] */
    private static $entities = [];
    private static $knownEntities = [];

    public function __construct(){
        $classes = [
            Cow::class,
            Pig::class,
            Sheep::class,
            Chicken::class,

            Zombie::class,
            Creeper::class,
            Skeleton::class,
            Spider::class,
            PigZombie::class,
            Enderman::class
        ];
        foreach($classes as $name) self::registerEntity($name);
    }

    public function onEnable(){
        $path = $this->getServer()->getDataPath() . "plugins/EntityManager/";
        if(!is_dir($path)) mkdir($path);

        $getData = function($ar, $key, $default){
            $vars = explode(".", $key);
            $base = array_shift($vars);
            if(!isset($ar[$base])) return $default;
            $base = $ar[$base];
            while(count($vars) > 0){
                $baseKey = array_shift($vars);
                if(!is_array($base) or !isset($base[$baseKey])) return $default;
                $base = $base[$baseKey];
            }
            return $base;
        };

        $data = [];
        if(file_exists($path . "config.yml")){
            $data = yaml_parse($this->yaml($path . "config.yml"));
        }
        self::$data = [
            "entity" => [
                "maximum" => $getData($data, "entity.maximum", 50),
                "explode" => $getData($data, "entity.explode", true),
            ],
            "spawn" => [
                "rand" => $getData($data, "spawn.rand", "1/5"),
                "tick" => $getData($data, "spawn.tick", 150),
            ],
            "autospawn" => [
                "turn-on" => $getData($data, "autospawn.turn-on", $getData($data, "spawn.auto", true)),
                "radius" => $getData($data, "autospawn.radius", $getData($data, "spawn.radius", 25)),
            ]
        ];
        file_put_contents($path . "config.yml", yaml_emit(self::$data, YAML_UTF8_ENCODING));

        if(file_exists($path. "SpawnerData.yml")){
            self::$spawnerData = yaml_parse($this->yaml($path . "SpawnerData.yml"));
            unlink($path. "SpawnerData.yml");
        }elseif(file_exists($path. "spawner.yml")){
            self::$spawnerData = yaml_parse($this->yaml($path . "spawner.yml"));
        }else{
            self::$spawnerData = [];
            file_put_contents($path . "spawner.yml", yaml_emit([], YAML_UTF8_ENCODING));
        }

        if(file_exists($path. "drops.yml")){
            self::$drops = yaml_parse($this->yaml($path . "drops.yml"));
        }else{
            self::$drops = [
                Zombie::NETWORK_ID => [
                    #[Item id, Item meta, Count, Percentage]
                    #example: [Item::FEATHER, 0, "1,10", "1/1"]
                ],
                Creeper::NETWORK_ID => [

                ],
            ];
            file_put_contents($path . "drops.yml", yaml_emit([], YAML_UTF8_ENCODING));
        }

        foreach(self::$knownEntities as $id => $name){
            if(!is_numeric($id)) continue;
            $item = Item::get(Item::SPAWN_EGG, $id);
            if(!Item::isCreativeItem($item)) Item::addCreativeItem($item);
        }

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[EntityManager]Plugin has been enabled");
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new UpdateEntityTask($this), 1);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new SpawnEntityTask($this), $this->getData("spawn.tick"));
    }

    public function onDisable(){
        file_put_contents($this->getServer()->getDataPath() . "plugins/EntityManager/spawner.yml", yaml_emit(self::$spawnerData, YAML_UTF8_ENCODING));
    }

    public function yaml($file){
        return preg_replace("#^([ ]*)([a-zA-Z_]{1}[^\:]*)\:#m", "$1\"$2\":", file_get_contents($file));
    }

    /**
     * @param Level $level
     *
     * @return BaseEntity[]
     */
    public static function getEntities(Level $level = null){
        $entities = self::$entities;
        if($level != null){
            foreach($entities as $id => $entity){
                if($entity->getLevel() !== $level) unset($entities[$id]);
            }
        }
        return $entities;
    }

    /**
     * @param string[] $type
     * @param Level $level
     *
     * @return bool
     */
    public static function clearEntity($type = [BaseEntity::class], Level $level = null){
        if(!is_array($type)) return false;
        $level = $level === null ? Server::getInstance()->getDefaultLevel() : $level;
        foreach($level->getEntities() as $id => $ent){
            foreach($type as $t){
                if(is_a(get_class($ent), $t, true)){
                    $ent->close();
                    continue;
                }
            }
        }
        return true;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getData($key){
        $vars = explode(".", $key);
        $base = array_shift($vars);
        if(!isset(self::$data[$base])) return false;
        $base = self::$data[$base];
        while(count($vars) > 0){
            $baseKey = array_shift($vars);
            if(!is_array($base) or !isset($base[$baseKey])) return false;
            $base = $base[$baseKey];
        }
        return $base;
    }

    /**
     * @param int|string $type
     * @param Position $source
     * @param mixed ...$args
     *
     * @return BaseEntity|Entity
     */
    public static function createEntity($type, Position $source, ...$args){
        $chunk = $source->getLevel()->getChunk($source->x >> 4, $source->z >> 4, true);
        if($chunk == null) return null;
        if(!$chunk->isLoaded()) $chunk->load();
        if(!$chunk->isGenerated()) $chunk->setGenerated();
        if(!$chunk->isPopulated()) $chunk->setPopulated();
        $nbt = new Compound("", [
            "Pos" => new Enum("Pos", [
                new Double("", $source->x),
                new Double("", $source->y),
                new Double("", $source->z)
            ]),
            "Motion" => new Enum("Motion", [
                new Double("", 0),
                new Double("", 0),
                new Double("", 0)
            ]),
            "Rotation" => new Enum("Rotation", [
                new Float("", $source instanceof Location ? $source->yaw : 0),
                new Float("", $source instanceof Location ? $source->pitch : 0)
            ]),
        ]);
        if(isset(self::$knownEntities[$type])){
            $class = self::$knownEntities[$type];
            /** @var BaseEntity $entity */
            $entity =  new $class($chunk, $nbt, ...$args);
            if($entity != null && $entity->isCreated()) $entity->spawnToAll();
            return $entity;
        }else{
            $entity = Entity::createEntity($type, $chunk, $nbt, ...$args);
            if($entity != null) $entity->spawnToAll();
            return $entity;
        }
    }

    public static function registerEntity($name){
        $class = new \ReflectionClass($name);
        if(is_a($name, BaseEntity::class, true) and !$class->isAbstract()){
            Entity::registerEntity($name, true);
            if($name::NETWORK_ID !== -1){
                self::$knownEntities[$name::NETWORK_ID] = $name;
            }
            self::$knownEntities[$class->getShortName()] = $name;
        }
    }

    public function EntitySpawnEvent(EntitySpawnEvent $ev){
        $entity = $ev->getEntity();
        if(is_a($entity, BaseEntity::class, true) && !$entity->closed) self::$entities[$entity->getId()] = $entity;
    }

    public function EntityDespawnEvent(EntityDespawnEvent $ev){
        $entity = $ev->getEntity();
        if($entity instanceof BaseEntity) unset(self::$entities[$entity->getId()]);
    }

    public function PlayerInteractEvent(PlayerInteractEvent $ev){
        if($ev->getFace() == 255 || $ev->getAction() != PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;
        $item = $ev->getItem();
        $player = $ev->getPlayer();
        $pos = $ev->getBlock()->getSide($ev->getFace());

        if($item->getId() === Item::SPAWN_EGG){
            if(self::createEntity($item->getDamage(), $pos) != null && $player->isSurvival()){
                $item->count--;
                $player->getInventory()->setItemInHand($item);
            }
            $ev->setCancelled();
        }elseif($item->getId() === Item::MONSTER_SPAWNER){
            self::$spawnerData["{$pos->x}:{$pos->y}:{$pos->z}:{$pos->level->getFolderName()}"] = [
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
        if($ev->isCancelled() || $pos->getId() != Item::MONSTER_SPAWNER) return;
        if(isset(self::$spawnerData["{$pos->x}:{$pos->y}:{$pos->z}"])){
            unset(self::$spawnerData["{$pos->x}:{$pos->y}:{$pos->z}"]);
        }elseif(isset(self::$spawnerData["{$pos->x}:{$pos->y}:{$pos->z}:{$pos->getLevel()->getFolderName()}"])){
            unset(self::$spawnerData["{$pos->x}:{$pos->y}:{$pos->z}:{$pos->getLevel()->getFolderName()}"]);
        }
    }

    public function ExplosionPrimeEvent(ExplosionPrimeEvent $ev){
        $ev->setCancelled(!$this->getData("entity.explode"));
    }

    public function EntityDeathEvent(EntityDeathEvent $ev){
        $entity = $ev->getEntity();
        if(!$entity instanceof BaseEntity or !isset(self::$drops[$entity::NETWORK_ID])) return;
        $drops = [];
        foreach(self::$drops[$entity::NETWORK_ID] as $key => $data){
            if(!isset($data[0]) || !isset($data[1]) || !isset($data[2])){
                unset(self::$drops[$entity::NETWORK_ID][$key]);
                continue;
            }
            $count = explode(",", $data[2]);
            if(min(...$count) !== $count[0]){
                unset(self::$drops[$entity::NETWORK_ID][$key]);
                continue;
            }
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
                self::clearEntity([BaseEntity::class, Projectile::class, ItemEntity::class], $level);
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

                if(!isset($pos) || self::createEntity($sub[0], $pos) == null){
                    $output .= "usage: /$label create <id/name> (x) (y) (z) (level)";
                }
                break;
            default:
                $output .= "usage: /$label <remove/check/create>";
                break;
        }
        $i->sendMessage($output);
        return true;
    }

}