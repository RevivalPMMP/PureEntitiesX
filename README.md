# EntityManager   
  
Author: **[@milk0417(승원)](https://github.com/milk0417)**  

**현재 자바에 맞게 작성되어져 있습니다  
이 사항은 곧 수정됩니다**  
  
**[NOTICE] This plugin is NOT perfect at all.  
It can cause your server crash, or something bad else :P**
  
EntityManager is a plugin for managing entities, literally.  
Most entities are moving around, and jumps if needed.  
  
EntityManager also has simple API for developers,  
such as **clear()** or **create()**.  
  
See documentation page for details.  
  
**[알림] 이 플러그인은 완벽하지 않습니다  
크래쉬가 발생하거나, 그외에 다른문제가 발생할수도 있습니다**  
  
엔티티매니저는 문자 그대로 엔티티를 관리하는 플러그인을 의미합니다.  
많은 엔티티들은 주위를 돌아다니고, 만약 필요하다면 점프도 합니다.  

엔티티매니저는 또한 개발자 여러분을 위해  
**clear()** 또는 **create()** 와 같은 간단한 API가 제공됩니다.  
  
자세한 사항은 아래를 보시기 바랍니다

### Method(메소드)
  * EntityManager
    * public static Map<Integer, BaseEntity> getEntities()
    * public static Map<Integer, BaseEntity> getEntities(Level level)
    * 
    * public static void clear()
    * public static void clear(Class[] type, type)
    * public static void clear(Class[] type, Level level)
    * 
    * public static BaseEntity create(int|String type, Position pos, Object... args)
  * BaseEntity
    * public boolean isCreated()
    * public boolean isMovement()
    * public boolean isWallCheck()
    * public void setMovement(boolean value)
    * public void setWallCheck(boolean value)
  * Monster
    * public double getDamage();
    * public double getDamage(int difficulty)
    * 
    * public void setDamage(double damage)
    * public void setDamage(double[] damage)
    * public void setDamage(double damage, int difficulty)
  * PigZombie
    * public boolean isAngry() #r u angry?
    * public void setAngry(int angry) #set how angry he is.  
  
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

### Method Examples(메소드 예시)
``` java  
//Entity Method  
EntityManager.getEntities().forEach((id, baseEntity) -> {  
    if(!baseEntity.isMovement()){  
        baseEntity.setMovement(true);  
    }  
    if(baseEntity instanceof Monster){
        Monster mob = (Monster) baseEntity;
        
        mob.setDamage(10);
          
        mob.setMaxDamage(10);  
        mob.setMinDamage(10);  
    }  
});  
  
//Create Entity  
Entity arrow = EntityManager.create("Arrow", position, player, true); //Nukkit default Class
Entity baseEntity = EntityManager.create("Zombie", position);  
  
//Remove Entity  
EntityManager.clear(new Class[]{BaseEntity.class, Projectile.class, Item.class});  
```
