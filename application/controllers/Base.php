<?php

/**
 * Created by PhpStorm.
 * User: zhenghua.hao
 * Date: 15/8/12
 * Time: 下午3:44
 */
define('PARAM_INT', 'int');
define('PARAM_STR', 'str');
define('PARAM_ID', 'id');
define('PARAM_ARRAY', 'array');
define('PARAM_FLOAT', 'float');
define('PARAM_RAW', 'raw');
define('PARAM_BOOL', 'bool');
define('PARAM_JSON', 'json');
define('PARAM_ENUM', 'enum');

define('DEFAULT_OFFSET', 0);
define('DEFAULT_LENGTH', 20);

class BaseController extends Yaf_Controller_Abstract
{
    public static $uid;
    public static $postContent = [];

    public function init()
    {
        $controllerName = $this->getRequest()->getControllerName();
        $actionName = strtolower($this->getRequest()->getActionName()) . 'action';
    }


    public function jsonReturn($result)
    {
        exit(json_encode($result));
    }
}