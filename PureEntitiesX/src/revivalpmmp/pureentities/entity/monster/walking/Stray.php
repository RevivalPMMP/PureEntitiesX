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

namespace revivalpmmp\pureentities\entity\monster\walking;

use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Timings;
use pocketmine\item\Bow;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;

class Stray extends WalkingMonster implements ProjectileSource {
    const NETWORK_ID = Data::STRAY;
    public function initEntity()
    {
        parent::initEntity();
        $this->width = Data::WIDTHS[self::NETWORK_ID];
        $this->height = Data::HEIGHTS[self::NETWORK_ID];
    }

    public function getSpeed(): float {
        return $this->speed;
    }

    public function getName(): string {
        return "Stray";
    }

    /**
     * Attack a player
     *
     * @param Entity $player
     */
    public function attackEntity(Entity $player) {
        if ($this->attackDelay > 30 && mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 55) {
            $this->attackDelay = 0;

            $f = 1.2;
            $yaw = $this->yaw + mt_rand(-220, 220) / 10;
            $pitch = $this->pitch + mt_rand(-120, 120) / 10;
            $nbt = new CompoundTag("", [
                "Pos" => new ListTag("Pos", [
                    new DoubleTag("", $this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5)),
                    new DoubleTag("", $this->y + 1.62),
                    new DoubleTag("", $this->z + (cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5))
                ]),
                "Motion" => new ListTag("Motion", [
                    new DoubleTag("", -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f),
                    new DoubleTag("", -sin($pitch / 180 * M_PI) * $f),
                    new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f)
                ]),
                "Rotation" => new ListTag("Rotation", [
                    new FloatTag("", $yaw),
                    new FloatTag("", $pitch)
                ]),
            ]);

            /** @var Projectile $arrow */
            $arrow = Entity::createEntity("Arrow", $this->getLevel(), $nbt, $this);

            $ev = new EntityShootBowEvent($this, Item::get(Item::ARROW, 0, 1), $arrow, $f);
            $this->server->getPluginManager()->callEvent($ev);

            $projectile = $ev->getProjectile();
            if ($ev->isCancelled()) {
                $projectile->kill();
            } elseif ($projectile instanceof Projectile) {
                $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($projectile));
                if ($launch->isCancelled()) {
                    $projectile->kill();
                } else {
                    $projectile->spawnToAll();
                    $this->level->addSound(new LaunchSound($this), $this->getViewers());
                }
            }
        }
    }

    public function spawnTo(Player $player) {
        parent::spawnTo($player);

        $pk = new MobEquipmentPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->item = new Bow();
        $pk->inventorySlot = 10;
        $pk->hotbarSlot = 10;
        $player->dataPacket($pk);
    }

    public function entityBaseTick(int $tickDiff = 1): bool {
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $time = $this->getLevel()->getTime() % Level::TIME_FULL;
        if (
            !$this->isOnFire()
            && ($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE)
        ) {
            $this->setOnFire(100);
        }

        Timings::$timerEntityBaseTick->stopTiming();
        return $hasUpdate;
    }

    public function getDrops(): array {
        $drops = [];
        if ($this->isLootDropAllowed()) {
            array_push($drops, Item::get(Item::ARROW, 0, mt_rand(0, 2)));
            array_push($drops, Item::get(Item::BONE, 0, mt_rand(0, 2)));
        }
        return $drops;
    }

    public function getMaxHealth(): int {
        return 20;
    }

    public function getKillExperience(): int {
        return 5;
    }


}
