<?php

namespace revivalpmmp\pureentities\features;


use pocketmine\Player;

interface IntfShearable {

    public function shear (Player $player) : bool;

    public function isSheared () : bool;

    public function setSheared (bool $sheared);

}