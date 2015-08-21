<?php

class WZhaopinEnv
{
    const ACTIVE = 4;   //100
    const HR = 2;       //010
    const BLUE_V = 1;   //001

    private static $weiboClient = null;
    private static $memCache = null;
    private static $redis = null;
    private static $pinyin = null;

    const SEND_EMAIL_ADDRESS = 'no_reply@zhaopin.weibo.com';
    const CONTACT_US_EMAIL = 'wzp@zhaopin.weibo.com';

    private static $weiboUserInfo = null;

    public static $staticUrl = null;
    public static $industry = null;
    public static $weiboUid = 0;

    private static $permissionChangeList = [];

    public static function isDebug()
    {
        if (isset(self::getConfig()->app->debug)) {
            return self::getConfig()->app->debug >= 1;
        }
        return false;
    }

    public static function getWid()
    {
        $session = Yaf_Session::getInstance();
        $wid = intval($session->get('wid'));
        if ($wid <= 0) {
            return false;
        } else {
            return $wid;
        }
    }

    public static function getWeiboUid()
    {
        return intval(self::$weiboUid);
    }

    public static function getWeiboUserInfo($paramName = null)
    {
        if (self::$weiboUserInfo != null) {
            if ($paramName != null) {
                return (isset(self::$weiboUserInfo[$paramName])) ? self::$weiboUserInfo[$paramName] : null;
            } else {
                return self::$weiboUserInfo;
            }
        }

        $config = self::getConfig()->redis;
//        $result = self::getRedis()->setRangeByScore($config->prefix->weibo, self::getWeiboUid(), self::getWeiboUid());
        $result = self::getRedis()->hashGet($config->prefix->weibo, self::getWeiboUid());
        if (empty($result)) {
            return false;
        } else {
            $weiboUserInfo = json_decode($result, true);
            if (!is_array($weiboUserInfo)) {
                self::getRedis()->hashDel($config->prefix->weibo, self::getWeiboUid());
                throw new YoloException('Weibo信息错误');
            }

            self::$weiboUserInfo = $weiboUserInfo;
            if (isset($paramName)) {
                return (isset($weiboUserInfo[$paramName])) ? $weiboUserInfo[$paramName] : null;
            } else {
                return $weiboUserInfo;
            }
        }
    }

    public static function getWeiboClient()
    {
        if (self::$weiboClient == null) {
            $config = self::getConfig();
            $akey = $config->weibo->zhaopin->akey;
            //$skey = $config['weibo']['skey'];
            self::$weiboClient = new SaeTClientV2($akey, null, $_COOKIE, null, '');
        }
        return self::$weiboClient;
    }

    /**
     * @return array weibo user information
     */
    public static function getWeiboUser()
    {
        $uid = self::getWeiboUid();
        if ($uid <= 0)
            return false;

        $user = null;

        for ($count = 0; $count < 5; $count++) {
            $user = self::getWeiboClient()->show_user_by_id($uid);
            if ($user != null) {
                break;
            }
        }

        if ($user == null) {
            return false;
        } else {
            $config = self::getConfig()->redis;
//            self::getRedis()->setDeleteRange($config->prefix->weibo, $uid, $uid);
//            self::getRedis()->setAdd($config->prefix->weibo, json_encode($user), 1, $uid);
            self::getRedis()->hashSet($config->prefix->weibo, [
                $uid => json_encode($user)
            ]);
            return $uid;
        }
    }

    public static function permissionHas($param, $wid = null)
    {
        if (empty($wid) || $wid <= 0) {
            $wid = self::getWid();
        }
        $config = self::getConfig()->redis;
//        $result = self::getRedis()->setRangeByScore($config->prefix->permission, $wid, $wid);
        $result = self::getRedis()->hashGet($config->prefix->permission, $wid);
        if (empty($result)) {
            return false;
        }
        $result = json_decode($result, true);
        return !empty($result[$param]);
    }

    public static function permissionGet($param, $wid = null)
    {
        if (empty($wid) || $wid <= 0) {
            $wid = self::getWid();
        }
        $config = self::getConfig()->redis;
//        $result = self::getRedis()->setRangeByScore($config->prefix->permission, $wid, $wid);
        $result = self::getRedis()->hashGet($config->prefix->permission, $wid);
        if (empty($result)) {
            return null;
        }
        $result = json_decode($result, true);
        return empty($result[$param]) ? null : $result[$param];
    }

    public static function permissionSet($param, $val, $wid = null, $flushImmediately = true)
    {
        if (empty($wid) || $wid <= 0) {
            $wid = self::getWid();
        }
        $currentVal = self::permissionGet($param, $wid);
        if ($currentVal === $val) {
            return;
        }
        if (!isset(self::$permissionChangeList[$wid])) {
            self::$permissionChangeList[$wid] = [];
        }
        self::$permissionChangeList[$wid][$param] = $val;
        if ($flushImmediately) {
            self::permissionFlush($wid);
        }
    }

    public static function permissionFlush($wid = null)
    {
        if (empty($wid) || $wid <= 0) {
            $wid = self::getWid();
        }
        if (!empty(self::$permissionChangeList[$wid])) {
            $permission = self::getPermission($wid);
            if ($permission === false) {
                $permission = [];
            }
            foreach (self::$permissionChangeList[$wid] as $key => $val) {
                $permission[$key] = $val;
            }
            self::setPermission($permission);
            unset(self::$permissionChangeList[$wid]);
        }
    }

    /**
     * @return bool|null|string
     * @throws YoloException
     * @throws YoloParamException
     */
    public static function getUserStatus()
    {
        $wid = self::getWid();
        if ($wid <= 0) {
            return false;
        }
        if (self::permissionHas('user_status')) {
            return intval(self::permissionGet('user_status'));
        }
        $memberInfo = UserApi::singleton()->getMemberInfo($wid);
        if (isset($memberInfo)) {
            self::permissionSet('company_wid', $memberInfo->company_wid, null, false);
            if ($wid == $memberInfo->company_wid) {
                // Activated HR BlueV
                self::permissionSet('user_status', intval(self::ACTIVE | self::HR | self::BLUE_V));
                return self::ACTIVE | self::HR | self::BLUE_V;   // BlueV
            } else {
                self::permissionSet('user_status', intval(self::ACTIVE | self::HR));
                return self::ACTIVE | self::HR;   // Common Hr
            }
        } else {
            $companyInfo = null;
            try {
                $companyInfo = UserApi::singleton()->getCompanyInfo($wid);
            } catch (YoloPlatformException $e) {
            }

            if ($companyInfo == null || !is_a($companyInfo, 'CompanyObject')) {
                self::permissionSet('user_status', intval(0));
                return 0;   // Inactivated Common User
            } else if ($wid == $companyInfo->wid) {
                if ($companyInfo->status != -1) {
                    throw new YoloException('未知错误');
                }
                self::permissionSet('user_status', intval(self::HR | self::BLUE_V));
                return self::HR | self::BLUE_V;   // Inactivated BlueV
            }
        }
        return false;
    }

    /* 是否是HR用户 */
    public static function isHR()
    {
        $userStatus = self::getUserStatus();
        return (self::HR & $userStatus);
    }

    /* 是否是其他用户 */
    public static function isJobSeeker()
    {
        return !self::isHR();
    }

    /* 是否是蓝V */
    public static function isBlueV()
    {
        $userStatus = self::getUserStatus();
        return (self::BLUE_V & $userStatus);
    }

    /* 是否激活 */
    public static function isActivated()
    {
        $userStatus = self::getUserStatus();
        return (self::ACTIVE & $userStatus);
    }

    /**
     * @deprecated
     */
    public static function flushSession()
    {
        self::clearPermission();
    }

    public static function clearPermission($wid = null)
    {
        if (empty($wid) || $wid <= 0) {
            $wid = self::getWid();
        }
        $config = self::getConfig()->redis;
//        $result = self::getRedis()->setDeleteRange($config->prefix->permission, $wid, $wid);
        $uid = json_decode(json_decode(self::getRedis()->hashGet($config->prefix->permission, $wid), true)['weibo_user'], true)['uid'];
        $result = self::getRedis()->hashDel($config->prefix->permission, $wid);
        if (!empty($uid)) {
            $result &= self::getRedis()->hashDel($config->prefix->weibo, $uid);
        }
        if ($wid == self::getWid()) {
            $session = Yaf_Session::getInstance();
            $result &= $session->del('wid');
        }
        return $result;
    }

    public static function getPermission($wid = null)
    {
        if (empty($wid) || $wid <= 0) {
            $wid = self::getWid();
        }
        if (empty($wid)) {
            return false;
        }
        $config = self::getConfig()->redis;
//        $result = self::getRedis()->setRangeByScore($config->prefix->permission, $wid, $wid);
        $result = self::getRedis()->hashGet($config->prefix->permission, $wid);
        if (empty($result)) {
            return false;
        } else {
            return json_decode($result, true);
        }
    }

    public static function setPermission($val, $wid = null)
    {
        if (empty($wid) || $wid <= 0) {
            $wid = self::getWid();
        }
        if (is_array($val)) {
            ksort($val);
            $val = json_encode($val);
        }
        $config = self::getConfig()->redis;
        $currentVal = self::getPermission($wid);
        if (($currentVal !== false) && ($currentVal === $val)) {
            return;
        }
//        self::getRedis()->setDeleteRange($config->prefix->permission, $wid, $wid);
//        self::getRedis()->setAdd($config->prefix->permission, $val, 1, $wid);
        self::getRedis()->hashSet($config->prefix->permission, [
            $wid => $val
        ]);
    }

    public static function getMemCache()
    {
        if (self::$memCache == null) {
            self::$memCache = new MemCacheBase();
        }
        return self::$memCache;
    }

    public static function getRedis()
    {
        if (!isset(self::$redis)) {
            $config = self::getConfig()->redis;
            self::$redis = new HaloRedis($config->host, $config->port, empty($config->password) ? null : $config->password);
        }
        return self::$redis;
    }

    /**
     * @return PinyinUtil
     */
    public static function getPinyin()
    {
        if (self::$pinyin == null) {
            self::$pinyin = new PinyinUtil();
        }
        return self::$pinyin;
    }

    public static function getVersion()
    {
        $config = self::getConfig();
        $ver = $config->app->version;
        if (empty($ver)) {
            $ver = "1.0.0";
        }
        return $ver;
    }

    public static function getLogDir()
    {
        $config = self::getConfig();
        if ($config->log->basedir) {
            $basedir = $config->log->basedir;
        } else {
            $basedir = APPLICATION_PATH . '/logs';
        }

        return $basedir;
    }

    public static function getConfig()
    {
        return Yaf_Registry::get('config');
    }

    public static function getStaticFileUrl()
    {
        if (self::$staticUrl != null) {
            return self::$staticUrl;
        }

        $host = self::getConfig()->static_file->host;
        $ver = self::getConfig()->static_file->version;

        if ($host == null) {
            return '';
//            $host = self::getConfig()->img->url->cdn;
        }
        if ($ver == null) {
            self::$staticUrl = "//{$host}";
        } else {
            self::$staticUrl = "//{$host}/hr{$ver}";
        }

        return self::$staticUrl;
    }

    public static function getIpAddress()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if (isset(self::getConfig()->proxy)) {
                $proxyAddress = self::getConfig()->proxy;

                if (strlen($proxyAddress) > 0
                    && strpos($_SERVER['REMOTE_ADDR'], $proxyAddress) == 0
                ) {
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
            } else {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }
}
