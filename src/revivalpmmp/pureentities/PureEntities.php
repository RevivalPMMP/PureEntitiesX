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

namespace revivalpmmp\pureentities;

use revivalpmmp\pureentities\entity\animal\flying\Bat;
use revivalpmmp\pureentities\entity\animal\jumping\Rabbit;
use revivalpmmp\pureentities\entity\animal\swimming\Squid;
use revivalpmmp\pureentities\entity\monster\swimming\Guardian;
use revivalpmmp\pureentities\entity\monster\swimming\ElderGuardian;
use revivalpmmp\pureentities\entity\monster\jumping\MagmaCube;
use revivalpmmp\pureentities\entity\monster\jumping\Slime;
use revivalpmmp\pureentities\entity\animal\walking\Villager;
use revivalpmmp\pureentities\entity\animal\walking\Horse;
use revivalpmmp\pureentities\entity\animal\walking\Mule;
use revivalpmmp\pureentities\entity\animal\walking\Donkey;
use revivalpmmp\pureentities\entity\animal\walking\Chicken;
use revivalpmmp\pureentities\entity\animal\walking\Cow;
use revivalpmmp\pureentities\entity\animal\walking\Mooshroom;
use revivalpmmp\pureentities\entity\animal\walking\Ocelot;
use revivalpmmp\pureentities\entity\animal\walking\Pig;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;
use revivalpmmp\pureentities\entity\monster\flying\Blaze;
use revivalpmmp\pureentities\entity\monster\flying\Ghast;
use revivalpmmp\pureentities\entity\monster\walking\CaveSpider;
use revivalpmmp\pureentities\entity\monster\walking\Creeper;
use revivalpmmp\pureentities\entity\monster\walking\Enderman;
use revivalpmmp\pureentities\entity\monster\walking\IronGolem;
use revivalpmmp\pureentities\entity\monster\walking\PigZombie;
use revivalpmmp\pureentities\entity\monster\walking\Silverfish;
use revivalpmmp\pureentities\entity\monster\walking\Skeleton;
use revivalpmmp\pureentities\entity\monster\walking\WitherSkeleton;
use revivalpmmp\pureentities\entity\monster\walking\SnowGolem;
use revivalpmmp\pureentities\entity\monster\walking\Spider;
use revivalpmmp\pureentities\entity\monster\walking\Wolf;
use revivalpmmp\pureentities\entity\monster\walking\Zombie;
use revivalpmmp\pureentities\entity\monster\walking\ZombieVillager;
use revivalpmmp\pureentities\entity\monster\walking\Husk;
use revivalpmmp\pureentities\entity\monster\walking\Stray;
use revivalpmmp\pureentities\entity\projectile\FireBall;
use revivalpmmp\pureentities\tile\Spawner;
use revivalpmmp\pureentities\task\AutoSpawnMonsterTask;
use revivalpmmp\pureentities\task\AutoSpawnAnimalTask;
use revivalpmmp\pureentities\task\AutoDespawnTask;
use revivalpmmp\pureentities\event\CreatureSpawnEvent;
use pocketmine\block\Air;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\InteractPacket;
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
	        //Bat::class,
            Squid::class,
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
            WitherSkeleton::class,
            Slime::class,
            SnowGolem::class,
            Spider::class,
            Wolf::class,
            Zombie::class,
            ZombieVillager::class,
            FireBall::class
        ];
        foreach($classes as $class){
            Entity::registerEntity($class);
            if(
                $class == IronGolem::class
                || $class == FireBall::class
                || $class == SnowGolem::class
                || $class == ZombieVillager::class
            ){
                continue;
            }
            $item = Item::get(Item::SPAWN_EGG, $class::NETWORK_ID);
            if(!Item::isCreativeItem($item)){
                Item::addCreativeItem($item);
            }
        }
        
		//self::registerTile(Spawner::class);
        
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] You're Running PureEntitiesX v".$this->getDescription()->getVersion());
        
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] The Original Code for this Plugin was Written by milk0417. It is now being maintained by RevivalPMMP for PMMP 'Unleashed'.");
    }

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] Plugin has been enabled");
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] You're running PureEntitiesX Dev!");
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoSpawnMonsterTask($this), 100);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoSpawnAnimalTask($this), 100);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoDespawnTask($this), 20);
    }

    public function onDisable(){
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] Plugin has been disabled");
    }

    /**
     * @param int|string $type
     * @param Position $source
     * @param $args
     * 
     * @return Entity
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

	/**
	 * @param PlayerInteractEvent $ev
	 */
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

    /**
     * @param DataPacketReceiveEvent $ev
     * @return boolean
     */
    public function shearSheep(DataPacketReceiveEvent $ev){
        $packet = $ev->getPacket();
        $player = $ev->getPlayer();
        if($packet->pid() === Info::INTERACT_PACKET) {
            if($packet->action === InteractPacket::ACTION_RIGHT_CLICK) {
                foreach($player->level->getEntities() as $entity) {
                    if($entity instanceof Sheep && $entity->distance($player) <= 4) {
                        if($entity->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHEARED) === true) {
                            return false;
                        } else {
                            $player->getLevel()->dropItem($entity, Item::get(Item::WOOL, 0, mt_rand(1, 3)));
                            $entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHEARED, true);
                            $player->setDataProperty(Entity::DATA_INTERACTIVE_TAG, Entity::DATA_TYPE_STRING, "");
                            return true;
                        }
                    }
                }
            }
        }
        return false;
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
     * @param int $entityId
     * @param Level $level
     * @param string $type
     * 
     * @return boolean
     */
    public function scheduleCreatureSpawn(Position $pos, int $entityId, Level $level, string $type){
        $this->getServer()->getPluginManager()->callEvent($ev = new CreatureSpawnEvent($this, $pos, $entityId, $level, $type));
        if($ev->isCancelled()) {
            return false;
        } else {
            $entity = self::create($entityId, $pos);
            $entity->spawnToAll();
            return true;
        }
    }
}