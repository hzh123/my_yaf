<?php
/**
 * Created by PhpStorm.
 * User: chenjian
 * Date: 15/3/11
 * Time: 09:51
 */

date_default_timezone_set('Asia/ShangHai');

//defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../'));
//
//defined('LIB_PATH') || define('LIB_PATH', realpath(APPLICATION_PATH . '/library'));
//
//defined('APPLICATION_ENV') || define('APPLICATION_ENV', getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production');
//
//defined('APPLICATION_CONFIG_PATH') || define('APPLICATION_CONFIG_PATH', APPLICATION_PATH . '/../config/');
//
//defined('APPLICATION_LIB_PATH') || define('APPLICATION_LIB_PATH', APPLICATION_PATH . '/library');
//
//Yaf_Loader::import(LIB_PATH . '/YoluLoggerUtils.php');
//
//Yaf_Loader::import(LIB_PATH . '/AutoLoader.php');

//set_include_path(implode(':', array(
//    realpath(APPLICATION_LIB_PATH),
//    realpath(APPLICATION_LIB_PATH . '/halo'),
//    realpath(APPLICATION_PATH . '/../models'),
//    realpath(APPLICATION_PATH . '/../models/dao'),
//    realpath(APPLICATION_PATH . '/../models/cache'),
//    realpath(APPLICATION_LIB_PATH . 'proxy/'),
//    get_include_path(),
//)));


class SystemConfig1
{
    public static function autoLoader()
    {
        spl_autoload_register(array('SystemConfig', 'autoload'));
    }

    public static function  initEssential()
    {
        Yaf_Loader::import(APPLICATION_PATH . '/library/halo/HaloMethod.php');
        Logger::initConfig(Yaf_Registry::get('config'));
    }

    public static function getSuffixClassPathMap()
    {
        $registerKey = __METHOD__;
        $suffixPathMap = Yaf_Registry::get($registerKey);
        if (empty($suffixPathMap)) {
            $path = APPLICATION_PATH . '/application';
            $suffixPathMap = array(
                'Model' => $path . '/models/',
                'Dao' => $path . '/models/dao',
                'Controller' => $path . '/controllers/',
                'Plugin' => $path . '/plugins/',
                'Action' => false,
                'Builder' => $path . '/builders/',
                'Object' => $path . '/objects',
                'Proxy' => $path . '/Proxy',
                'Api' => $path . '/api',
                'Util' => $path . '/utils',
                'Exception' => $path . '/exception',
                'Interface' => $path . '/interfaces',
            );
            Yaf_Registry::set($registerKey, $suffixPathMap);
        }
        return $suffixPathMap;
    }

    public static function getSpecialClassMap()
    {
        $registerKey = __METHOD__;
        $map = Yaf_Registry::get($registerKey);
        if (empty($map)) {
            $map = [
                'HaloRedis' => LIB_PATH . '/HaloRedis/HaloRedisMod.php',
                'QRcode' => LIB_PATH . '/phpqrcode/qrlib',
                'CallCenterEnv' => LIB_PATH . '/CallCenterUtils/CallCenterEnv.php',
                'WrmStatisticLog' => LIB_PATH . '/CallCenterUtils/WrmStatisticLog.php',
                'CallCenterFatalException' => LIB_PATH . '/CallCenterUtils/CallCenterFatalException.php',
                'AESEncryptUtil' => LIB_PATH . '/CallCenterUtils/CallCenterAESEncrypt.php',
                'YoloYafView' => LIB_PATH . '/yolo/YoloYafView.php',
                'WZhaopinEnv' => LIB_PATH . '/wzhaopin/WZhaopinEnv.php',
                'MemCacheBase' => LIB_PATH . '/wzhaopin/MemCacheBase.php',
                'LoginHandler' => LIB_PATH . '/wzhaopin/LoginHandler.php',
                'SaeTClientV2' => LIB_PATH . '/sdk/sina/saetv2.ex.class.php',
                'SaeTOAuthV2' => LIB_PATH . '/sdk/sina/saetv2.ex.class.php',
                'QQClient' => LIB_PATH . '/sdk/qq/QQClient.php',
                'Logger' => LIB_PATH . '/halo/Logger.php',
                'HaloEnv' => LIB_PATH . '/halo/HaloEnv.php',
                'WrmPlatformClient' => LIB_PATH . '/wrm-platform-sdk/WrmPlatformClient.php',
//                'util_LogUtil' => LIB_PATH . '/util/LogUtil.php',
                'IdCardUploadUtil' => LIB_PATH . '/utils/IdCardUploadUtil.php',
                'NameCardUploadUtil' => LIB_PATH . '/utils/NameCardUploadUtil.php',
                'CompanyLicenseUploadUtil' => LIB_PATH . '/utils/CompanyLicenseUploadUtil.php',
                'CompanyApplyUploadUtil' => LIB_PATH . '/utils/CompanyApplyUploadUtil.php',
                'ImageManageUtil' => LIB_PATH . '/utils/ImageManageUtil.php',
                'HaloXhprof' => LIB_PATH . '/halo/HaloXhprof.php',
                'YoloException' => LIB_PATH . '/yolo/YoloException.php',
                'YoloParamException' => LIB_PATH . '/yolo/YoloParamException.php'
            ];
            Yaf_Registry::set($registerKey, $map);
        }
        return $map;

    }

    private static function autoload($className)
    {
        $classFile = '';
        $specialClassMap = self::getSpecialClassMap();
        if (isset($specialClassMap[$className])) {
            $classFile = $specialClassMap[$className];
        } else if (substr($className, 0, 10) == "Illuminate") {
            $scriptPath = LIB_PATH;
            $scriptName = str_replace('\\', '/', $className);
            if ($scriptPath) {
                $classFile = sprintf("%s/%s.php", $scriptPath, $scriptName);
            }
        } else {
            $suffixPathMap = self::getSuffixClassPathMap();
            foreach ($suffixPathMap as $key => $val) {
                $pos = stripos($className, $key);
                if ($pos === (strlen($className) - strlen($key))) {
                    if ($val !== false) {
                        $classFile = $val . '/' . substr($className, 0, $pos) . '.php';
                        if (!file_exists($classFile)) {
                            echo $classFile . " is not found \n";
                        }
                        break;
                    }
                }
            }
        }
        Yaf_Loader::import($classFile);
    }

    public static function getWeiboClient() {
        $key = __METHOD__;
        $res = Yaf_Registry::get($key);
        if (empty($res)) {
            $config = Yaf_Registry::get("config");
            $session = Yaf_Session::getInstance();
            $token = UserTokenModel::singleton()->getToken($session->offsetGet('obj_id'));
//            var_dump($token);
            $res = new SaeTClientV2($config['weibo']['app_id'], $config['weibo']['app_key'], null, null, null, $token);
            Yaf_Registry::set($key, $res);
        }
        return $res;
    }

}