<?php

/**
 * Created by PhpStorm.
 * User: zhenghua.hao
 * Date: 15/8/21
 * Time: 下午12:08
 */
class Bootstrap extends Yaf_Bootstrap_Abstract
{
    public function _iniYaf(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->autoRender(false);
        $dispatcher->disableView();
    }


    public function _iniRoute(Yaf_Dispatcher $dispatcher)
    {
        $uri = trim($dispatcher->getInstance()->getRequest()->getBaseUri(), '/');

    }

}