<?php
/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2016 RevivalPMMP

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

namespace magicode\pureentities;

use magicode\pureentities\event\CreatureSpawnEvent;
use magicode\pureentities\entity\animal\swimming\Squid;
use magicode\pureentities\entity\monster\swimming\Guardian;
use magicode\pureentities\entity\monster\swimming\ElderGuardian;
use magicode\pureentities\entity\monster\jumping\MagmaCube;
use magicode\pureentities\entity\monster\jumping\Slime;
use magicode\pureentities\entity\animal\walking\Villager;
use magicode\pureentities\entity\animal\walking\Horse;
use magicode\pureentities\entity\animal\walking\Mule;
use magicode\pureentities\entity\animal\walking\Donkey;
use magicode\pureentities\entity\animal\walking\Chicken;
use magicode\pureentities\entity\animal\walking\Cow;
use magicode\pureentities\entity\animal\walking\Mooshroom;
use magicode\pureentities\entity\animal\walking\Ocelot;
use magicode\pureentities\entity\animal\walking\Pig;
use magicode\pureentities\entity\animal\walking\Rabbit;
use magicode\pureentities\entity\animal\walking\Sheep;
use magicode\pureentities\entity\monster\flying\Blaze;
use magicode\pureentities\entity\monster\flying\Ghast;
use magicode\pureentities\entity\monster\walking\CaveSpider;
use magicode\pureentities\entity\monster\walking\Creeper;
use magicode\pureentities\entity\monster\walking\Enderman;
use magicode\pureentities\entity\monster\walking\IronGolem;
use magicode\pureentities\entity\monster\walking\PigZombie;
use magicode\pureentities\entity\monster\walking\Silverfish;
use magicode\pureentities\entity\monster\walking\Skeleton;
use magicode\pureentities\entity\monster\walking\SnowGolem;
use magicode\pureentities\entity\monster\walking\Spider;
use magicode\pureentities\entity\monster\walking\Wolf;
use magicode\pureentities\entity\monster\walking\Zombie;
use magicode\pureentities\entity\monster\walking\ZombieVillager;
use magicode\pureentities\entity\monster\walking\Husk;
use magicode\pureentities\entity\monster\walking\Stray;
use magicode\pureentities\entity\projectile\FireBall;
use magicode\pureentities\tile\Spawner;
use magicode\pureentities\task\AutoSpawnMonsterTask;
use magicode\pureentities\task\AutoSpawnAnimalTask;
use magicode\pureentities\task\AutoDespawnTask;
use pocketmine\block\Air;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

class PureEntities extends PluginBase implements Listener{

    public function onLoad(){
        $classes = [
            Stray::class,
            Husk::class,
            Horse::class,
            Donkey::class,
            Mule::class,
            //ElderGuardian::class,
            //Guardian::class,
            //Squid::class,
            Villager::class,
            Blaze::class,
            CaveSpider::class,
            Chicken::class,
            Cow::class,
            Creeper::class,
            Enderman::class,
            Ghast::class,
            IronGolem::class,
            MagmaCube::class,
            Mooshroom::class,
            Ocelot::class,
            Pig::class,
            PigZombie::class,
            Rabbit::class,
            Sheep::class,
            Silverfish::class,
            Skeleton::class,
            Slime::class,
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

        Tile::registerTile(Spawner::class);
        
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] You're Running PureEntitiesX 1.1");
        
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] The Original Code for this Plugin was Written by milk0417. It is now being maintained by RevivalPMMP for PMMP 'Unleashed'.");
    }

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] Plugin has been enabled");
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] You're running PureEntitiesX Dev!");
        
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoSpawnMonsterTask($this), 100);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoSpawnAnimalTask($this), 100);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoDespawnTask($this), 20);
    }

    public function onDisable(){
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] Plugin has been disabled");
    }

    /**
     * @param type $type
     * @param Position $source
     * @param type $args
     * 
     * @return type
     */
    public static function create($type, Position $source, ...$args){
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
        if($ev->getFace() == 255 || $ev->getAction() != PlayerInteractEvent::RIGHT_CLICK_BLOCK){
            return;
        }

        $item = $ev->getItem();
        $block = $ev->getBlock();
        if($item->getId() === Item::SPAWN_EGG && $block->getId() == Item::MONSTER_SPAWNER){
            $ev->setCancelled();

            $tile = $block->level->getTile($block);
            if($tile != null && $tile instanceof Spawner){
                $tile->setSpawnEntityType($item->getDamage());
            }else{
                if($tile != null){
                    $tile->close();
                }
                $nbt = new CompoundTag("", [
                    new StringTag("id", Tile::MOB_SPAWNER),
                    new IntTag("EntityId", $item->getId()),
                    new IntTag("x", $block->x),
                    new IntTag("y", $block->y),
                    new IntTag("z", $block->z),
                ]);
                new Spawner($block->getLevel()->getChunk((int) $block->x >> 4, (int) $block->z >> 4), $nbt);
            }
        }
    }

    public function BlockPlaceEvent(BlockPlaceEvent $ev){
        if($ev->isCancelled()){
            return;
        }

        $block = $ev->getBlock();
        if($block->getId() == Item::JACK_O_LANTERN || $block->getId() == Item::PUMPKIN){
            if(
                $block->getSide(Vector3::SIDE_DOWN)->getId() == Item::SNOW_BLOCK
                && $block->getSide(Vector3::SIDE_DOWN, 2)->getId() == Item::SNOW_BLOCK
            ){
                for($y = 1; $y < 3; $y++){
                    $block->getLevel()->setBlock($block->add(0, -$y, 0), new Air());
                }
                $entity = PureEntities::create("SnowGolem", Position::fromObject($block->add(0.5, -2, 0.5), $block->level));
                if($entity != null){
                    $entity->spawnToAll();
                }
                $ev->setCancelled();
            }elseif(
                $block->getSide(Vector3::SIDE_DOWN)->getId() == Item::IRON_BLOCK
                && $block->getSide(Vector3::SIDE_DOWN, 2)->getId() == Item::IRON_BLOCK
            ){
                $first = $block->getSide(Vector3::SIDE_EAST);
                $second = $block->getSide(Vector3::SIDE_EAST);
                if(
                    $first->getId() == Item::IRON_BLOCK
                    && $second->getId() == Item::IRON_BLOCK
                ){
                    $block->getLevel()->setBlock($first, new Air());
                    $block->getLevel()->setBlock($second, new Air());
                }else{
                    $first = $block->getSide(Vector3::SIDE_NORTH);
                    $second = $block->getSide(Vector3::SIDE_SOUTH);
                    if(
                        $first->getId() == Item::IRON_BLOCK
                        && $second->getId() == Item::IRON_BLOCK
                    ){
                        $block->getLevel()->setBlock($first, new Air());
                        $block->getLevel()->setBlock($second, new Air());
                    }else{
                        return;
                    }
                }

                if($second != null){
                    $entity = PureEntities::create("IronGolem", Position::fromObject($block->add(0.5, -2, 0.5), $block->level));
                    if($entity != null){
                        $entity->spawnToAll();
                    }

                    $block->getLevel()->setBlock($entity, new Air());
                    $block->getLevel()->setBlock($block->add(0, -1, 0), new Air());
                    $ev->setCancelled();
                }
            }
        }
    }
    
    /**
     * @param Position $pos
     * @param int $entityid
     * @param Level $level
     * @param string $type
     * 
     * @return boolean
     */
    public function scheduleCreatureSpawn(Position $pos, int $entityid, Level $level, string $type) {
        $this->getServer()->getPluginManager()->callEvent($event = new CreatureSpawnEvent($this, $pos, $entityid, $level, $type));
        if($event->isCancelled()) {
            return false;
        } else {
            $entity = self::create($entityid, $pos);
            $entity->spawnToAll();
            return true;
        }
    }
}
