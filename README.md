# PureEntities
  
Author(제작자): **[SW승원(milk0417)](https://github.com/milk0417)**  
  
자매품(Nukkit): [PureEntities-JAVA](https://github.com/SW-Team/PureEntities)  
추가 플러그인(Sub Module): [EntityManager](https://github.com/milk0417/EntityManager)
    
**[NOTICE] This plug-in is not perfect, the entity may move abnormally (It was written in Java8)**
  
PureEntities is a plugin for implement entities.  
The plugin provides Mob AIs like walking, auto-jumping, etc.  
  
PureEntities also has simple API for developers, such as **isMovement()** or **isWallCheck()**.  
See documentation page for details.  
  
**[알림] 이 플러그인은 완벽하지 않으며 엔티티가 비정상적으로 움직일 수 있습니다 (Java8로 작성되었습니다)**  
  
PureEntities는 엔티티를 구현시켜주는 플러그인입니다.  
이 플러그인은 MobAI(움직임, 자동점프 및 기타 등등)을 제공합니다.  
  
PureEntities는 또한 개발자 여러분을 위해 **isMovement()** 또는 **isWallCheck()** 와 같은 간단한 API가 제공됩니다.  
자세한 사항은 아래를 보시기 바랍니다  

### Method(메소드)
  * PureEntities
    * public static function create(int|string $type, Position $pos, ...$args) : Entity
  * BaseEntity
    * public function isMovement() : bool
    * public function isFriendly() : bool
    * public function isWallCheck() : bool
    * public function setMovement(bool $value) : void
    * public function setFriendly(bool $value) : void
    * public function setWallCheck(bool $value) : void
  * Monster
    * public function getDamage(int $difficulty = null) : float
    * public function setDamage(float|float[] $damage, int $difficulty = null) : void
  * PigZombie
    * public function isAngry() : bool
    * public function setAngry(int $angry) : void

### Method Examples(메소드 예시)
``` php
foreach(EntityManager::getEntity() as $entity){
    $entity->setWallCheck(false);
    $entity->setMovement(!$entity->isMovement());

    if($entity instanceof Monster){
        $entity->setDamage(10);

        $entity->setMaxDamage(10);
        $entity->setMinDamage(10);
    }
}

$arrow = PureEntities::create("Arrow", $pos, $player, true);
$zombie = PureEntities::create("Zombie", $pos);
```
