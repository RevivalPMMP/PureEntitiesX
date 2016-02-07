# EntityManager
  
Author(제작자): **[@milk0417(승원)](https://github.com/milk0417)**  
  
자매품(Nukkit): [EntityManager-Nukkit](https://github.com/SW-Team/EntityManager)
    
**[NOTICE] This plug-in is not perfect, the entity may move abnormally (PHP7 Only)**
  
EntityManager is a plugin for managing entities, literally.  
Most entities are moving around, and jumps if needed.  
  
EntityManager also has simple API for developers,  
such as **clear()** or **create()**.  
  
See documentation page for details.  
  
**[알림] 이 플러그인은 완벽하지 않으며 엔티티가 비정상적으로 움직일 수 있습니다 (PHP7만 지원합니다)**  
  
엔티티매니저는 말 그대로 엔티티를 관리하는 플러그인을 의미합니다.  
많은 엔티티들은 주위를 돌아다니거나 뛰어다닙니다.  

엔티티매니저는 또한 개발자 여러분을 위해  
**clear()** 또는 **create()** 와 같은 간단한 API가 제공됩니다.  
  
자세한 사항은 아래를 보시기 바랍니다

### YAML data
  * config.yml
``` yml
entity:
  explode: false #Whether the entity explosion
spawn:
  rand: "1/4" #Entity spawn probability
  tick: 100 #Entity spawn period
autospawn:
  turn-on: true #Whether auto-spawn
  radius: 25 #Radius will spawn location
autoclear:
  turn-on: true #Whether the entity automatically removed
  tick: 6000 #Entity remove period
  entities: [Projectile, DroppedItem] #List of entities to be removed
```
  * spawner.yml
    * TODO
  * drops.yml
    * TODO
  
### Commands(명령어)
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
    * description: literally(If blank, it is set as a Player)

### Method(메소드)
  * EntityManager
    * public static function clear(array $type = [BaseEntity::class], Level $level = null) : void
    * public static function create(int|string $type, Position $pos, ...$args) : BaseEntity
  * BaseEntity
    * public function isCreated() : bool
    * public function isMovement() : bool
    * public function isWallCheck() : bool
    * public function setMovement(bool $value) : void
    * public function setWallCheck(bool $value) : void
  * Monster
    * public function getDamage(int $difficulty = null) : float
    * public function setDamage(float|float[] $damage, int $difficulty = null) : void
  * PigZombie
    * public function isAngry() : bool
    * public function setAngry(int $angry) : void

### Method Examples(메소드 예시)
``` php
//Entity Method
foreach(EntityManager::getEntity() as $entity){
    if(!$entity->isMovement()){
        $entity->setMovement(true);
    }
    if($entity instanceof Monster){
        $entity->setDamage(10);

        $entity->setMaxDamage(10);
        $entity->setMinDamage(10);
    }
}

//Create Entity
$arrow = EntityManager::create("Arrow", $pos, $player, true);
$zombie = EntityManager::create("Zombie", $pos);

//Remove Entity
EntityManager::clear([BaseEntity::class, Projectile::class, Item::class]);
```
