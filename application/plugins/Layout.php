<?php

class LayoutPlugin extends Yaf_Plugin_Abstract
{
    private $_layoutDir;
    private $_layoutFile;
    public static $_layoutVars;

    public function __construct($layoutFile, $layoutDir = null)
    {
        $this->_layoutFile = $layoutFile;
        $this->_layoutDir = ($layoutDir) ? $layoutDir : APPLICATION_PATH . '/application/views/layout';
    }

    public function __set($name, $value)
    {
        $this->_layoutVars[$name] = $value;

        /**
         * 例子
         * $my = new LayoutPlugin(my.html);
         * $my->name='cj';
         * echo $my->name;  //if add __get() , will output cj
         */
    }

    public static function assign($name, $value)
    {
        self::$_layoutVars[$name] = $value;
    }

    public function dispatchLoopShutDown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

        if (!Yaf_Registry::get("disableLayout")) {

            //获取已经设置响应的Body
            $body = $response->getBody();

            //清除已经设置响应的body
            $response->clearBody();

            $layout = new YoloYafView($this->_layoutDir);
            $layout->content = $body;

            //self::assign('companyInfo', UserApi::singleton()->getCompanyInfo(WZhaopinEnv::getCompanyWid()));

            $layout->assign('layout', self::$_layoutVars);

            //设置响应的body
            $response->setBody($layout->render((Yaf_Registry::get("setLayout") ? Yaf_Registry::get("setLayout") : $this->_layoutFile) . '.phtml'));
        }
    }

    /**
     * @param Yaf_Request_Abstract|Yaf_Request_Http $request
     * @param Yaf_Response_Abstract $response
     */
    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        $controllerName = strtolower($request->getControllerName());
        $actionAliasName = strtolower($request->get('action_alias_name'));
        self::assign('controllerName', $controllerName);
        self::assign('actionName', $actionAliasName);

        $titles = [
            [
                'controller' => 'job',
                'action' => 'list',
                'title' => '职位管理'
            ]
        ];

        foreach ($titles as $title) {
            $changeTitle = true;
            if (isset($title['controller']))
                if ($title['controller'] != $controllerName)
                    $changeTitle = false;
            if (isset($title['action']))
                if ($title['action'] != $actionAliasName)
                    $changeTitle = false;
            if ($changeTitle) {
                self::assign('title', $title['title']);
                break;
            }
        }
    }

    public function preResponse(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }
}
