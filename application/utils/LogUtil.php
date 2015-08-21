<?php

class util_LogUtil
{
    public static $LOG_LEVELS = [
        'FATAL' => 100,
        'ERROR' => 10,
        'WARNING' => 9,
        'NOTICE' => 8,
        'INFO' => 7,
        'DEBUG' => 6,
        'SQL' => 3,
    ];

    private static $isLoaded = false;

    private static $enableLog = true;
    private static $logLevel = null;
    private static $target = null;

    public static function loadConfig() {
        if (self::$isLoaded) {
            return;
        }

        $globalConfig = Yaf_Registry::get('config');
        if (!isset($globalConfig->log)) {
            self::$enableLog = false;
            return;
        }

        $globalLogConfig = $globalConfig->log;

        if (isset($globalLogConfig->enable)) {
            self::$enableLog = $globalLogConfig->enable;
        }

        if (isset($globalLogConfig->log_level)) {
            self::$logLevel = self::$LOG_LEVELS[$globalLogConfig->log_level];
        }

        if (!is_string($globalLogConfig->target)) {
            $tmpTarget = [];
            foreach ($globalLogConfig->target as $k => $v) {
                $k = strtoupper($k);
                $tmpTarget[$k] = $v;
            }
            self::$target = $tmpTarget;
        } else {
            self::$target = ['DEFAULT' => $globalLogConfig->target];
        }

        self::$isLoaded = true;
    }

    public static function Log($msg, $level = 'NOTICE', $source = 'DEFAULT') {
        self::loadConfig();

        if (isset(self::$LOG_LEVELS[$level])) {
            if (self::$LOG_LEVELS[$level] < self::$logLevel) {
                return;
            }
        }

        $level = strtoupper($level);
        $source = strtoupper($source);
        if ($source == null) {
            $source = 'DEFAULT';
        }

        if (isset(self::$target[$source])) {
            $logTarget = self::$target[$source];
        } else if (isset(self::$target[$level])) {
            $logTarget = self::$target[$level];
        } else {
            $logTarget = self::$target['DEFAULT'];
        }

        $logTarget = str_replace('{%date}', date("Y-m-d"), $logTarget);
        $logTarget = str_replace('{%year}', date('Y'), $logTarget);
        $logTarget = str_replace('{%month}', date('m'), $logTarget);

        $pos = strrpos($logTarget, '/');
        $path = substr($logTarget, 0, $pos);
        if (!is_dir($path)) {
            mkdir($path);
            chmod($path, 0777);
        }

        $logStr = sprintf("%s\t%s\t%s\t%s\n", date('Y-m-d G:i:s'), $source, $level, $msg);

        self::write($logTarget, $logStr);
    }

    private static function write($filePath, $str) {
        file_put_contents($filePath, $str, FILE_APPEND);
    }

    public static function Fatal($msg, $source = null) {
        self::Log($msg, 'FATAL', $source);
    }

    public static function Error($msg, $source = null) {
        self::Log($msg, 'ERROR', $source);
    }

    public static function Warning($msg, $source = null) {
        self::Log($msg, 'WARNING', $source);
    }

    public static function Notice($msg, $source = null) {
        self::Log($msg, 'NOTICE', $source);
    }

    public static function Info($msg, $source = null) {
        self::Log($msg, 'INFO', $source);
    }

    public static function Debug($msg, $source = null) {
        self::Log($msg, 'DEBUG', $source);
    }

}