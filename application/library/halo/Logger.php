<?php

defined('HALO_LOG_DEBUG') || define('HALO_LOG_DEBUG', 0);
defined('HALO_LOG_WARNING') || define('HALO_LOG_WARNING', 1);
defined('HALO_LOG_INFO') || define('HALO_LOG_INFO', 2);
defined('HALO_LOG_TRACKER') || define('HALO_LOG_TRACKER', 3);
defined('HALO_LOG_ERROR') || define('HALO_LOG_ERROR', 4);
defined('HALO_LOG_FATAL') || define('HALO_LOG_FATAL', 5);

define('SERVER_NAME', 'WRM-B');


class Logger
{
    private $_domain = '';
    public static $log2Console = false;
    const  PLATFORM_WEB = 0;
    const  PLATFORM_WAP = 1;
    const  USER_TRACE_LOG_KEY = 'USER_TRACE_LOG_KEY';
    const  MC_TRACE_LOG_KEY = 'MC_TRACE_LOG_KEY';
    public static $level = HALO_LOG_DEBUG;
    public static $config = null;

    public function __construct($domain)
    {
        $this->_domain = $domain;
    }

    public static function initConfig($config)
    {
        self::$config = $config;
    }

    public static function isDebugEnabled()
    {
        return (intval(self::$level) == HALO_LOG_DEBUG);
    }

    public static function writeException(Exception $ex, $info = array())
    {
        if (self::$level > HALO_LOG_ERROR) {
            return;
        }
        $list = array();
        $list['code'] = $ex->getCode();
        $list['message'] = $ex->getMessage();
        $exceptionTrace = $ex->getTrace();
        if (isset($exceptionTrace[0])) {
            $trace = $exceptionTrace[0];
            $list['function'] = sprintf('%s%s%s(%s)', $trace['class'], $trace['type'], $trace['function'], StringUtil::printParams($trace['args']));
        }
        $list['file'] = sprintf('%s(%d)', $ex->getFile(), $ex->getLine());
        $message = sprintf('%s - %s', date('H:i:s'), get_class($ex));
        if (count($info)) {
            $list = array_merge($list, $info);
        }
        foreach ($list as $k => $val) {
            $message = sprintf("%s  |  %s", $message, strtoupper($k) . ': ' . $val);
        }

        $filePath = Logger::loggerFileName('');
        @file_put_contents($filePath, $message . "\n\n", FILE_APPEND);
    }


    /* class method */
    private static function write($level, &$domain, &$info, $file = '', $line = '', $output = 'file')
    {
        if ($level < self::$level) {
            return;
        }
        $level_str_list = array('DEBUG', 'WARNING', 'INFO', 'TRACKER', 'ERROR', 'FATAL');
        $level_str = $level_str_list[$level];

        if (strlen($file) > 0) {
            $file = substr($file, strlen($_SERVER['DOCUMENT_ROOT']));
        }

        $time = date('H:i:s');
        $info = print_r($info, true);
        $message = sprintf("%s - %s %s %s %s\r\n", $time, $level_str, $file, $line, ' ' . $info);

        if ($output == 'mem') {
            $_REQUEST['MEM_LOG'][] = $message;
        } else {
            $filePath = Logger::loggerFileName($domain);
            @file_put_contents($filePath, $message, FILE_APPEND);
            if (self::$log2Console)
                printf("%s", $message);
        }
    }

    public static function loggerFileName($domain, $ext = 'log')
    {
        $date = date('Y-m-d');
        $hour = date('H');
        $filePath = WZhaopinEnv::getLogDir() . '/' . $date . '/';
        ensureFilePath($filePath, true);
        if (strlen($domain) > 0) {
            $filePath = sprintf('%s%s-%02d.%s', $filePath, $domain, $hour, $ext);
        } else {
            $filePath = sprintf('%s%d.%s', $filePath, $hour, $ext);
        }
        return $filePath;
    }

    public static function DEBUG($info, $file = '', $line = '', $domain = '', $output = 'file')
    {
        self::write(HALO_LOG_DEBUG, $domain, $info, $file, $line, $output);
    }

    public static function INFO($info, $file = '', $line = '', $domain = '', $output = 'file')
    {
        self::write(HALO_LOG_INFO, $domain, $info, $file, $line, $output);
    }

    public static function WARNING($info, $file = '', $line = '', $domain = '', $output = 'file')
    {
        self::write(HALO_LOG_WARNING, $domain, $info, $file, $line, $output);
    }

    public static function ERROR($info, $file = '', $line = '', $domain = '', $output = 'file')
    {
        self::write(HALO_LOG_ERROR, $domain, $info, $file, $line, $output);
    }

    public static function FATAL($info, $file = '', $line = '', $domain = '', $output = 'file')
    {
        self::write(HALO_LOG_FATAL, $domain, $info, $file, $line, $output);
    }

    public static function TRACKER($info, $file = '', $line = '', $domain = '', $output = 'file')
    {
        self::write(HALO_LOG_TRACKER, $domain, $info, $file, $line, $output);
    }


    /* instance method */
    public function __DEBUG__($info, $file = '', $line = '', $output = 'file')
    {
        Logger::write(HALO_LOG_DEBUG, $this->_domain, $info, $file, $line, $output);
    }

    public function __INFO__($info, $file = '', $line = '', $output = 'file')
    {
        Logger::write(HALO_LOG_INFO, $this->_domain, $info, $file, $line, $output);
    }

    public function __WARNING__($info, $file = '', $line = '', $output = 'file')
    {
        Logger::write(HALO_LOG_WARNING, $this->_domain, $info, $file, $line, $output);
    }

    public function __ERROR__($info, $file = '', $line = '', $output = 'file')
    {
        Logger::write(HALO_LOG_ERROR, $this->_domain, $info, $file, $line, $output);
    }

    public function __FATAL__($info, $file = '', $line = '', $output = 'file')
    {
        Logger::write(HALO_LOG_FATAL, $this->_domain, $info, $file, $line, $output);
    }

    public function __TRACKER__($info, $file = '', $line = '', $output = 'file')
    {
        Logger::write(HALO_LOG_TRACKER, $this->_domain, $info, $file, $line, $output);
    }

    public static function LOG($domain)
    {
        $log = new Logger($domain);
        return $log;
    }


}