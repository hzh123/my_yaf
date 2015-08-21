<?php
/**
 *
 * Created by PhpStorm.
 * User: zhenghua.hao
 * Date: 15/8/11
 * Time: 下午6:22
 */
@date_default_timezone_set('Asia/Shanghai');
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../application'));
defined('APPLICATION_CONFIG_PATH') ||defined('APPLICATION_CONFIG_PATH', APPLICATION_PATH . '/../config/');
defined('APPLICATION_LIB_PATH') || defined('APPLICATION_LIB_PATH', APPLICATION_PATH . '/../logs/');


class SystemConfig
{
    public static function init()
    {
        spl_autoload_register();
//        $config = self::getAPPConfig();
//        $logDir = isset($config->log->dir) ? $config->log->dir : false;

    }

    private function getAPPConfig()
    {
        $key = __METHOD__;
        $config = Yaf_Registry::get($key);
        if ($config == null) {
            $config = Yaf_Application::app() == null ? new Yaf_Application(APPLICATION_CONFIG_PATH . '/application.ini') : Yaf_Application::app();
            Yaf_Registry::set($key, $config);
        }
        return $config;
    }

    private function autoload($className)
    {
        $suffix = self::getSuffix($className);
        Yaf_Loader::import();
    }


    private function getSuffix($className)
    {
        $arr = preg_split('/(?=[A-Z])/', $className);
        return !empty($arr) ? end($arr) : $arr;
    }
}