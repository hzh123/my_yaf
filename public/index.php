<?php
/**
 * Created by PhpStorm.
 * User: zhenghua.hao
 * Date: 15/3/31
 * Time: 上午11:48
 */

date_default_timezone_set('Asia/Shanghai');

if (empty($_SERVER['UNIQUE_ID'])) {
    $_SERVER['UNIQUE_ID'] = uniqid();
}
require '../application/config/SystemConfig.php';
SystemConfig::init();
$app = new Yaf_Application(APPLICATION_PATH . '/config/application.ini');
$app->bootstrap()->run();