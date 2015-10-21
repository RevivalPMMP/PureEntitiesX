# EntityManager  

Author: **[@milk0417](https://github.com/milk0417)**  

**[NOTICE] This plugin is NOT perfect at all.  
It can cause your server crash, or something bad else :P**
  
EntityManager is a plugin for managing entities, literally.  
Most entities and mobs are moving around, and jumps if needed.

EntityManager also has simple API for developers,  
such as clearEntity($type, $level) (Clears all entities with given type.),  
createEntity($type, $pos). See documentation page for details.

### Method
  * EntityManager
    * public static BaseEntity[] getEntities(Level $level = null); #returns array of all entities in given server.
    * public static bool clearEntity($type = [BaseEntity::class], Level $level = null); #clears all entities with given type.
    * public static BaseEntity createEntity(string|int $type, Position $pos, ...$args); #spawns entity with given type and pos
  * BaseEntity
    * public bool isCreated(); #check if entity is created.
    * public bool isMovement() #check if entity is movable.
    * public bool isWallCheck() #check if entity can go through walls.
    * public void setMovement(bool $value) #literally.
    * public void setWallCheck(bool $value) #set if entity will check walls when it moves.
  * Monster
    * public bool getDamage(int $difficulty = null); #returns monster's damage in given difficulty.
    * public void setDamage(int $damage, int $difficulty = null) #set monster's damage with given difficulty.
  * PigZombie
    * public bool isAngry() #r u angry?
    * public void setAngry(int $angry) #set how angry he is.  
  
### Commands
  * /entitymanager
    * usage: /entitymanager (check|remove|spawn)
    * permission: entitymanager.command
  * /entitymanager check
    * usage: /entitymanager check (Level="")
    * permission: entitymanager.command.check
    * description: Check the number of entities(If blank, it is set as a default Level)
  * /entitymanager remove
    * usage: /entitymanager remove (Level="")
    * permission: entitymanager.command.remove
    * description: Remove all entities in Level(If blank, it is set as a default Level)
  * /entitymanager spawn:
    * usage: /entitymanager spawn (type) (x="") (y="") (z="") (Level="")
    * permission: entitymanager.command.spawn
    * description: literally(If blank, it is set as a Player  
  
### YAML Data
  * drops.yml
    * TODO
  * config.yml
    * TODO
  * spawner.yml
    * TODO

### Method Examples
``` php  
//Entity Method  
foreach(EntityManager::getEntities() as $baseEntity){  
    if(!$baseEntity->isMovement()){  
        $baseEntity->setMovement(true);  
    }  
    if($baseEntity instanceof Monster){  
        $baseEntity->~~setDamage~~(10); #@deprecated  
          
        $baseEntity->setMaxDamage(10); #$difficulty = null => Default difficulty  
        $baseEntity->setMinDamage(10); #$difficulty = null => Default difficulty  
    }  
}  
//Create Entity  
$arrow = EntityManager::createEntity("Arrow", $position, $player, true); //PMMP Default Class  
$baseEntity = EntityManager::createEntity("Zombie", $position);  
  
//Remove Entity  
EntityManager::clearEntity([BaseEntity::class, Projectile::class, Item::class]);  
```