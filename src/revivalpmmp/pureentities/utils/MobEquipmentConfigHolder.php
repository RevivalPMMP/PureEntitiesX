<?php
/**
 * Created by PhpStorm.
 * User: mige
 * Date: 07.03.17
 * Time: 11:24
 */

namespace revivalpmmp\pureentities\utils;


use revivalpmmp\pureentities\config\mobequipment\EntityConfig;

class MobEquipmentConfigHolder {

    /**
     * @var array
     */
    private static $config = [];

    /**
     * Returns mob equipment configuration for a specific entity name
     *
     * @param string $entityName
     * @return mixed null|EntityConfig
     */
    public static function getConfig(string $entityName) {
        // check if configuration already cached - if not create it and store it
        if (!array_key_exists($entityName, self::$config)) {
            self::$config[$entityName] = new EntityConfig($entityName);
        }

        return self::$config[$entityName];
    }


}