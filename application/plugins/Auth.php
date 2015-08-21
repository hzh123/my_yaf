<?php

/**
 * Created by PhpStorm.
 * User: Frederick
 * Date: 2015/01/28
 * Time: 18:12
 */
class AuthPlugin extends Yaf_Plugin_Abstract
{
    const STATUS_UNAUDITED = 0;
    const STATUS_AUDITING = 1;
    const STATUS_PASS = 2;
    const STATUS_UNQUALIFIED = 3;

    public static $guestActionMap = [
        'login' => ['index', 'token', 'unregistered', 'login', 'logout']
    ];

    public static $userActionMap = [
        'login' => ['logout']
    ];


    public static function isGuestAccessible(Yaf_Request_Abstract $request) {
        $controller = strtolower($request->getControllerName());
        $actionAliasName = strtolower($request->get('action_alias_name'));
        $uri = $request->getRequestUri();
        return $uri == '/' || (isset(self::$guestActionMap[$controller]) && in_array($actionAliasName, self::$guestActionMap[$controller]));
    }


    public function userAccessRedirect(Yaf_Request_Abstract $request) {
        $controller = strtolower($request->getControllerName());
        $actionAliasName = strtolower($request->get('action_alias_name'));
        if (!UserRegisterModel::singleton()->hasUserRegistered($this->getObjectId()) && ($controller != 'login' && $actionAliasName != 'unregistered')) {
            $this->headerLocation('http://zhaopin.renmai.cn/login/unregistered');
        }
        $status = self::getUserStatus();
        $jumpUrlMap = array(0 => '/auth/choose', 1 => '/auth/result', 2 => '/resume/search', 3 => '/auth/result');
        if (in_array($status, array(self::STATUS_UNAUDITED, self::STATUS_AUDITING,self::STATUS_UNQUALIFIED))) {
            if ($controller != 'auth' && !(isset(self::$userActionMap[$controller]) && in_array($actionAliasName, self::$userActionMap[$controller]))) {
                $this->headerLocation('http://zhaopin.renmai.cn' . $jumpUrlMap[$status]);
            }
        }
    }

    public function userAccessible() {

    }

    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

        $session = Yaf_Session::getInstance();
        if (!isset($_COOKIE['wrm_uuid'])) {
            $identify = uniqid() . $_COOKIE['PHPSESSID'];
            setcookie('wrm_uuid', $identify, time() + 604800, '/', ".renmai.cn");
        } else {
            setcookie('wrm_uuid', $_COOKIE['wrm_uuid'], time() + 604800, '/', ".renmai.cn");
        }
        $isLogin = LoginHandler::isLogin();

        if (!$isLogin) {
            $_REQUEST['login'] = false;
            if ($this->isGuestAccessible($request)) {
                return;
            } else {
                if ($request->get('trace_type') == 'ajax') {
                    echo json_encode(array('code' => -1999, 'data' => array('login_status' => 0)));
                    haloDie();
                } else {
                    $this->goLoginWithBackUrl();
                }
                return;
            }

        } else {
            $_REQUEST['login'] = true;
            $this->syncSession($session, $response);
            $this->userAccessRedirect($request);


            //
            $model = UserAuthModel::singleton();
//            $userInfo = $model->getBasicAuthInfo($this->getObjectId(),2);
//            var_dump($userInfo);
        }
    }


    private function syncSession(Yaf_Session $session, Yaf_Response_Abstract $response)
    {
        $authId = $session->offsetGet("wrm_oauth_id");
        $authType = $session->offsetGet("wrm_oauth_type");
        $session->offsetSet('obj_id', $authId);
        if ($authType == LoginHandler::OAUTH_TYPE_SINA) {
            $session->offsetSet('type', 'weibo');
        } else if ($authType == LoginHandler::OAUTH_TYPE_QQ) {
            $session->offsetSet('type', 'qq');
        } else {
//            AuthPlugin::errorUser("Type was invalid", $response);
        }
        if (is_null($session->offsetGet('status'))) {
            $state = UserRegisterModel::singleton()->getUserAuthState($session->offsetGet('obj_id'));
            $session->offsetSet('status', $state);
        }
    }

    public static function getUserStatus() {
        $session = Yaf_Session::getInstance();
        $status = Yaf_Registry::get('user_status') ? Yaf_Registry::get('user_status') : null;
        if (is_null($status)) {
//            $status = UserRegisterModel::singleton()->getUserAuthState($session->offsetGet('obj_id'));
            $status = UserRegisterModel::singleton()->getUserAuthState($session->offsetGet('obj_id'));

            Yaf_Registry::set('user_status', $status);
        }
        $status = 2;
        return $status;
    }

    public function goLoginWithBackUrl()
    {
        $back = $_SERVER['REQUEST_URI'];
//        $toUrl = "/login?auto=login&back=" . urlencode($back);
        $toUrl = "/login";
        AuthPlugin::headerLocation($toUrl);
    }

    /**
     * @param string $url The URL to go to
     */
    public function headerLocation($url)
    {
        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'Windows') || strstr($_SERVER['HTTP_USER_AGENT'], 'Trident')) {
            header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTR STP IND DEM"');
        }
        header('Location: ' . $url);
        die();
    }

    private function getObjectId() {
        $session = Yaf_Session::getInstance();
        return isset($session['obj_id']) ? $session['obj_id'] : 0;
    }

}