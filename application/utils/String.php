<?php

/**
 * Created by PhpStorm.
 * User: chenjian
 * Date: 15/3/24
 * Time: 15:37
 */
class StringUtil
{
    const REGEX_XSS = "[><].*(a |xml|img|script|alert|src|onload|body|iframe|input|link|meta|style|div|table|embed|base|href|object).*[><]*";

    const REGEX_TEL_NUM = "(1[345678][0-9０-９\-－ 　]{9,11})|([0-9]{3,4}[\- ][0-9０-９\-－ 　]{7,8}|\d{7,8})";

    const REGEX_EMAIL = "(\w+([-+.]\w+)*@\\w+([-.]\w+)*\.\w+([-.]\w+)*)";


    public static function hasMatch($pattern, $subject, $ignoreCase = true, $encoding = "UTF-8")
    {
        mb_regex_encoding($encoding); //会全局设置
        return call_user_func($ignoreCase ? "mb_eregi" : "mb_ereg", $pattern, $subject) > 0;
    }

    public static function hasXss($content)
    {
        return self::hasMatch(self::REGEX_XSS, $content);
    }

    public static function isEmail($content)
    {
        return self::hasMatch(self::REGEX_XSS, $content);
    }

    public static function isTelNum($content) {
        return self::hasMatch(self::REGEX_TEL_NUM, $content);
    }

    public static function printParams($params, $glue = ',') {
        $res = '';
        if (count($params)) {
            foreach ($params as $val) {
                if (is_string($val)) {
                    $val = sprintf('\'%s\'', $val);
                } else if (is_int($val) || is_float($val)) {
                    null;
                } else if (is_bool($val)) {
                    $val = $val ? 'true' : 'false';
                } else if (is_array($val)) {
                    $val = sprintf('array:%s', json_encode($val, JSON_UNESCAPED_UNICODE));
                } else if (is_object($val)) {
                    $val = sprintf('[object]%s:%s', get_class($val), json_encode($val, JSON_UNESCAPED_UNICODE));
                } else if (is_resource($val)) {
                    $val = sprintf('[resource]%s', get_resource_type($val));
                } else {
                    $val = 'other';
                }
                $res .= $glue . ' ' . strval($val);
            }
        }
        return substr($res, 2);
    }
}