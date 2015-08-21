<?php

/**
 * Created by PhpStorm.
 * User: zhenghua.hao
 * Date: 15/8/12
 * Time: 上午10:50
 */
class BaseModel
{
    private static $_instance = [];

    protected function __construct()
    {
    }

    public static function getInstance()
    {
        $className = get_called_class();
        if (!self::$_instance[$className]) {
            self::$_instance[$className] = new $className();
        }
        return self::$_instance[$className];
    }
}