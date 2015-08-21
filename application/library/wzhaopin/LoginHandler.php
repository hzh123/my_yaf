<?php
/**
 * Created by PhpStorm.
 * User: will
 * Date: 1/29/15
 * Time: Will 15:47
 */

class LoginHandler
{
    const OAUTH_TYPE_SINA = 'weibo';
    const OAUTH_TYPE_QQ = 'qq';

    private static $client = null;
    private static $type = "";
//    private static $sinaClient = null;
    private static $clsMap = array(
        'qq' => 'QQClient',
        'weibo' => 'SaeTOAuthV2'
    );

    public static function getClient($type)
    {
        $config = Yaf_Registry::get("config");
        if (self::$client == null || self::$type != $type)
        {
            self::$type = $type;
            $clsName = self::$clsMap[$type];
            $session = Yaf_Session::getInstance();
            $oauthToken = $session->offsetGet("wrm_oauth_token");
            self::$client = new $clsName(
                $config[$type]['app_id'],
                $config[$type]['app_key'],
                $config[$type]['scope'],
                $config[$type]['callback'],
                "",
                $oauthToken
                );
        }
        return self::$client;
    }

    public static function currentClient()
    {
        if(self::$client)
        {
            return self::$client;
        }
        return null;
    }

    /**
     * @param $param
     *
     */
    public static function updateSessionToken($param)
    {
        //TODO: update access token in session
        $session = Yaf_Session::getInstance();
        $session->offsetSet('wrm_oauth_id', $param['uid']);
        $session->offsetSet('wrm_oauth_type', $param['type']);
        $session->offsetSet('wrm_oauth_expire', $param['expires_in']);
        $session->offsetSet('wrm_oauth_token', $param['access_token']);

    }

    public static function delSession()
    {
        $session = Yaf_Session::getInstance();
        $session->offsetUnset('obj_id');
        $session->offsetUnset('type');
        $session->offsetUnset('wrm_oauth_id');
        $session->offsetUnset('wrm_oauth_type');
        $session->offsetUnset('wrm_oauth_expire');
        $session->offsetUnset('wrm_oauth_token');

    }

    public static function updateCookie($uid, $expire)
    {
        setcookie("wrm_aid", UidEncryptUtil::encryptUid($uid), $expire, "/", ".renmai.cn");
        setcookie("wrm_atype", self::$type, $expire, "/", ".renmai.cn");
    }

    public static function delCookie()
    {
        setcookie("wrm_uid", "", -1000, "/", "/");
        setcookie("wrm_aid", "", -1000, "/", ".renmai.cn");
        setcookie("wrm_atype", "", -1000, "/", ".renmai.cn");
    }

    public static function isLogin()
    {
        if (isset($_REQUEST['login']))
        {
            return $_REQUEST['login'];
        }
        if (!isset($_COOKIE['wrm_aid']) || !isset($_COOKIE['wrm_atype']))
        {
            return false;
        }
        else
        {
            $cookieId = UidEncryptUtil::decryptUid($_COOKIE['wrm_aid']);
            $cookieType = $_COOKIE['wrm_atype'];
            $session = Yaf_Session::getInstance();
            $oauthId = $session->offsetGet('wrm_oauth_id');
            $oauthType = $session->offsetGet('wrm_oauth_type');
            $oauthExpire = intval($session->offsetGet('wrm_oauth_expire'));
            $oauthToken = $session->offsetGet("wrm_oauth_token");
            self::getClient($cookieType);
            if ( ($cookieId != $oauthId) || ($cookieType != $oauthType) )
            {
                if (!empty($oauthId))
                {
                    self::delSession();
                }
                return false;
                UserApi::getAccessToken($cookieId, $cookieType);
                $oauthToken = $session->offsetGet("wrm_oauth_token");
            }
            if (empty($oauthToken))
            {
                self::delSession();
                self::delCookie();
                return false;
            }
            if ($oauthExpire > time())
            {
                self::$client = null;
                self::getClient($cookieType);
                return true;
            }
            else
            {
                if (!empty($oauthId) && !empty($oauthType))
                {
                    $client = self::getClient($oauthType);
                    $client->getAccessToken();
                    if ($client->expireTime > time())
                    {
                        return true;
                    }
                }
                self::delSession();
                self::delCookie();
                return false;
            }
        }
    }

    public static function getOAuthUrl($state, $type, $backUrl = "/")
    {
        $config = Yaf_Registry::get("config");

        $clsName = self::$clsMap[$type];
        $client = new $clsName($config[$type]['app_id'], $config[$type]['app_key'], $config[$type]['scope'], $config[$type]['callback']);

        $url = $client->getAuthorizeURL($state);
        if (strpos($url, "?") !== false)
        {
            $url .= "&type=".$type."&back_url=" . urlencode($backUrl);
        }
        else
        {
            $url .= "?type=".$type."back_url=" . urlencode($backUrl);
        }
        return $url;
    }
}