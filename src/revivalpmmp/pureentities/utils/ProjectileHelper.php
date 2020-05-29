<?php
declare(strict_types=1);

namespace revivalpmmp\pureentities\utils;


use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\level\Position;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3;

class ProjectileHelper{

	public static function createProjectile(int $type, Position $startingPoint, Vector3 $target) : Projectile{
		$motion = $target->subtract($startingPoint);
		$nbt = Entity::createBaseNBT($startingPoint, $motion, self::getYawForProjectile($startingPoint, $target), self::getPitchForProjectile($startingPoint, $target));
		$projectile = Entity::createEntity($type, $startingPoint->getLevel(), $nbt);
		if(!$projectile instanceof Projectile){
			throw new \InvalidArgumentException("Invalid type of $type passed to ProjectileHelper::createProjectile()!");
		}
		return $projectile;
	}

	public static function launchProjectile(Projectile $projectile){
		$launch = new ProjectileLaunchEvent($projectile);
		$launch->call();
		if($launch->isCancelled()){
			$projectile->kill();
		}else{
			$projectile->spawnToAll();
			$projectile->level->addSound(new LaunchSound($projectile));
		}
	}

	private static function getYawForProjectile(Vector3 $source, Vector3 $target) : float{
		$xDist = $target->x - $source->x;
		$zDist = $target->z - $source->z;
		$yaw = -(atan2($zDist, $xDist) / M_PI * 180 - 90);
		if($yaw < 0){
			$yaw += 360.0;
		}
		return $yaw;
	}

	private static function getPitchForProjectile(Vector3 $source, Vector3 $target) : float{
		$horizontal = sqrt(($target->x - $source->x) ** 2 + ($target->z - $source->z) ** 2);
		$vertical = $target->y - $source->y;
		return atan2($vertical, $horizontal) / M_PI * 180;
	}
}