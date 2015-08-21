<?php

class httpReq
{
    public static function httpGet($url, $params = null, $header = null)
    {
        if ($params != null && count($params) > 0) {
            $url = httpReq::getUrlBuilder($url, $params);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($header != null) {
            if (is_array($header)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            } else {
                $request_headers = array();
                $request_headers[] = $header;
                curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
            }
        }

        $output = curl_exec($ch);

        curl_close($ch);
        return $output;
    }

    public static function httpPost($url, $params, $header = null, $arrayJsonEncoded = true)
    {
        if (is_array($params)) {
            // Post an array, only values will be url encoded
            // JSON Encoding is only available in this mode

            $postData = '';
            if ($arrayJsonEncoded) {
                $postData = json_encode($params);
                if ($header == null) {
                    $header = array();
                }
                $header[] = 'Content-Type: application/json';
            } else {
                //create name value pairs separated by & and url encode the values
                foreach ($params as $k => $v) {
                    $postData .= $k . '=' . urlencode($v) . '&';
                }
                rtrim($postData, '&');
            }

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            if ($header != null) {
                if (is_array($header)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                } else {
                    $request_headers = array();
                    $request_headers[] = $header;
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
                }
            }

            $output = curl_exec($ch);
            curl_close($ch);
            return $output;
        } else {
            // Post a raw datum

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            if ($header != null) {
                if (is_array($header)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                } else {
                    $request_headers = array();
                    $request_headers[] = $header;
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
                }
            }

            $output = curl_exec($ch);
            curl_close($ch);
            return $output;
        }
    }

    private static function getUrlBuilder($baseUrl, $params)
    {
        $url = $baseUrl;
        if ($url[strlen($url - 1)] != '?') {
            $url .= '?';
        }
        foreach ($params as $key => $val) {
            $url .= $key . '=';
            $url .= urlencode($val);
            $url .= '&';
        }
        return substr($url, 0, strlen($url) - 1);
    }
}