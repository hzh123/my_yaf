<?php

/*
 * 无线判断登录状态 SDK。根据是否存在 SUW Cookie 判断用户是否登录
 *
 */

final class SSOWirelessClient
{

    /**
     * @static
     * @var string 种SUW cookie跳转地址
     */
    static private $set_suw_url = 'http://login.weibo.cn/login/setssocookie/';

    /**
     * @static
     * @var string 登录回转地址
     */
    static private $back_url = '';

    /**
     * @param string $url 设置登陆回跳URL
     */
    static public function set_back_url($url)
    {
        if ( $url != '' ) {
            self::$back_url = $url;
        }
    }


    /**
     * 获取回跳的URL,默认当前URL
     * @return string
     */
    static public function get_back_url()
    {
        //如果没有设置过，默认取当前页url
        if ( self::$back_url == '' ) {
            self::$back_url = $_SERVER['SCRIPT_URI'] . '?' . $_SERVER['QUERY_STRING'];
        }
        return self::$back_url;
    }

    /**
     * 判断用户是否登录,这个仅作了cookie读取校验,没有对cookie进行校验
     *
     * @static
     *
     * @return bool true 登录 false 未登录
     */
    static public function is_logined()
    {
        // 如果存在 SUW 则处于登录状态；若不存在，则未登录
        if ( isset($_COOKIE['SUW']) && $_COOKIE['SUW']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取登录页面地址
     *
     * @static
     *
     * @return string 登录页面地址
     */
    static public function get_login_url($type = null)
    {
        
        //设置过cookie但是检测不到就认为不支持cookie，
        if ( $_GET['hassetsso'] &&  !isset($_COOKIE['SUW']) ) {
            return false;
        }

        $url = self::$set_suw_url;
        if ( strpos($url, '?')) {
            return $url . '&backUrl=' . urlencode(self::get_back_url()). '&loginpage='. $type;
        }
        return $url . '?backUrl=' . urlencode(self::get_back_url()). '&loginpage='. $type;
    }

}