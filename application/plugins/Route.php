<?php

/**
 * Created by PhpStorm.
 * User: Frederick
 * Date: 2015/01/30
 * Time: 14:43
 */
class RoutePlugin extends Yaf_Plugin_Abstract
{
    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        if (strtoupper($request->getModuleName()) == 'PLATFORM') {
            $currentController = $request->getControllerName();
            $routedController = 'Platform' . $currentController;
            $request->setControllerName($routedController);
        }
    }
}