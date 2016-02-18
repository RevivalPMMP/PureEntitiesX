<?php

namespace milk\pureentities;

use milk\pureentities\entity\animal\walking\Chicken;
use milk\pureentities\entity\animal\walking\Cow;
use milk\pureentities\entity\animal\walking\Mooshroom;
use milk\pureentities\entity\animal\walking\Ocelot;
use milk\pureentities\entity\animal\walking\Pig;
use milk\pureentities\entity\animal\walking\Rabbit;
use milk\pureentities\entity\animal\walking\Sheep;
use milk\pureentities\entity\monster\flying\Blaze;
use milk\pureentities\entity\monster\flying\Ghast;
use milk\pureentities\entity\monster\walking\CaveSpider;
use milk\pureentities\entity\monster\walking\Creeper;
use milk\pureentities\entity\monster\walking\Enderman;
use milk\pureentities\entity\monster\walking\IronGolem;
use milk\pureentities\entity\monster\walking\PigZombie;
use milk\pureentities\entity\monster\walking\Silverfish;
use milk\pureentities\entity\monster\walking\Skeleton;
use milk\pureentities\entity\monster\walking\SnowGolem;
use milk\pureentities\entity\monster\walking\Spider;
use milk\pureentities\entity\monster\walking\Wolf;
use milk\pureentities\entity\monster\walking\Zombie;
use milk\pureentities\entity\monster\walking\ZombieVillager;
use milk\pureentities\entity\projectile\FireBall;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\block\Block;

class PureEntities extends PluginBase implements Listener{

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
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntities]Plugin has been enabled");
    }

    public function onDisable(){
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntities]Plugin has been disable");
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
        $block = $ev->getBlock();

        if($item->getId() === Item::SPAWN_EGG && $block->getId() == Item::MONSTER_SPAWNER){
            $ev->setCancelled();

            //TODO: This isn't implemeted yet
            /*$nbt = new CompoundTag("", [
                new StringTag("id", Tile::MOB_SPAWNER),
                new IntTag("EntityId", $item->getId()),
                new IntTag("x", $block->x),
                new IntTag("y", $block->y),
                new IntTag("z", $block->z),
            ]);
            new Spawner($block->getLevel()->getChunk((int) $block->x >> 4, (int) $block->z >> 4), $nbt);*/
        }
    }

    public function BlockPlaceEvent(BlockPlaceEvent $ev){

    }

    public function BlockBreakEvent(BlockBreakEvent $ev){
        if($ev->isCancelled()){
            return;
        }

        $block = $ev->getBlock();
        if(
            (
                $block->getId() == Block::STONE
                or $block->getId() == Block::STONE_WALL
                or $block->getId() == Block::STONE_BRICK
                or $block->getId() == Block::STONE_BRICK_STAIRS
            ) && ($block->getLightLevel() < 12 and mt_rand(1,3) < 2)
        ){
            $entity = self::create("Silverfish", $block);
            if($entity != null){
                $entity->spawnToAll();
            }
        }
    }

}