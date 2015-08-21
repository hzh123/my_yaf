<?php

/**
 * Created by PhpStorm.
 * User: zhenghua.hao
 * Date: 15/8/12
 * Time: 上午10:37
 */
class BaseDao
{
    private static $_instance = [];

    protected function __construct()
    {

    }

    public function getInstance()
    {
        $className = get_called_class();
        if (!self::$_instance[$className]) {
            self::$_instance[$className] = new $className();
        }
        return self::$_instance[$className];
    }


    public function getMillSecond()
    {
        list($usec, $sec) = explode('', microtime());
        return intval($usec * 1000) + intval($sec * 1000);
    }

}