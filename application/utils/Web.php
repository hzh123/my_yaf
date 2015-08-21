<?php

/**
 * Created by PhpStorm.
 * User: chenjian
 * Date: 14/11/17
 * Time: 02:52
 */
class WebUtil
{
    public function __construct()
    {

    }

    public static function httpGet($url, $params = array(), $cookie = null, $agent = null, $timeout = 0)
    {
        if ($params) {
            $arr = [];
            foreach ($params as $key => $val) {
                $arr[] = $key . '=' . $val;
            }
            $url .= '?' . implode('&', $arr);
        }
//        var_dump($url);
//        die();
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
//        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
        curl_setopt($ci, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        !empty($cookie) && curl_setopt($ci, CURLOPT_COOKIE, $cookie);
        !empty($agent) && curl_setopt($ci, CURLOPT_USERAGENT, $agent);
        $response = curl_exec($ci);
        curl_close($ci);
        return $response;
    }

    public static function downloadImage($imageUrl, $dir = '', $fileName = '', $timeout = 0)
    {
        if (empty($imageUrl)) {
            return false;
        }
        if (empty($dir)) {
            $dir = __DIR__ . '/';
        }
        if (empty($fileName)) {
            $fileName = $imageUrl;
        }
        $fileName = $dir . $fileName;
        if (file_exists($fileName)) {
            $fileName = $fileName . date("Y-m-d-H-i-s");
        }
        $curl = curl_init($imageUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        $imageStream = curl_exec($curl);
        curl_close($curl);
        if ($imageStream) {
            $fp = fopen($fileName, 'wb');
            fwrite($fp, $imageStream);
            fclose($fp);
            return true;
        } else {
            echo sprintf("can not get image from %s \n", $imageUrl);
            return false;
        }
    }

    public static function htmlUtf8($html)
    {
        return mb_convert_encoding($html, "UTF-8", "UTF-8,GBK,GB2312,BIG5");
    }

}