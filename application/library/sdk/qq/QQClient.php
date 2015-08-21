<?php
/**
 * Created by PhpStorm.
 * User: will
 * Date: 1/29/15
 * Time: Will 15:49
 */
class QQClient
{
    public $appId;
    public $appKey;
    public $accessToken;
    public $refreshToken;
    public $expireTime;
    public $scope;
    public $code;
    public $url;
    public $callback;
//    public $oauthUrl = "https://graph.qq.com/oauth2.0/authorize";
//    public $tokenUrl = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code";

    public function __construct($appId, $appKey, $scope, $callback, $cookie='', $accessToken=null, $refreshToke=null)
    {
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->scope = $scope;
        $this->callback = $callback;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToke;
    }

    /**
     * 获取Oauth Url
     * @param $type string 授权类型, 固定为code
     * @param $scope string 授权列表，逗号分隔，eg: get_user_info,list_album,upload_pic,do_like
     * @param $state string 防止CSRF攻击
     * @return string OAuthUrl
     */
    public function getAuthorizeURL($state, $type="code")
    {
        $url = "https://graph.qq.com/oauth2.0/authorize";
        $params = [
            'response_type' =>$type,
            'client_id' => $this->appId,
            'redirect_uri' => $this->callback,
            'state' => $state,
            'scope' => $this->scope
        ];
        $queryStr = http_build_query($params);
        return $url."?".$queryStr;
    }

    /**
     * @param $code string Auth code returned by qq
     * @return array if success return access_token, expire_in and refresh_token
     *               if failed return error code and description
     *
    'access_token' => string '313F00C54AC512A7ED1C664EE198B615' (length=32)
    'expires_in' => string '7776000' (length=7)
    'refresh_token' => string 'A5D30D009A349AA8197C86DECEC77BD8' (length=32)
    'code' => int 0
     */
    public function getAccessToken($code="")
    {
        if (empty($code) && empty($this->refreshToken))
        {
            Logger::ERROR("Try to get access token, but neither code nor refresh token was empty.", "", "", "qq_sdk_err");
            return ['code' => -1, 'desc' => "neither code nor refresh token was empty."];
        }
        $url = "https://graph.qq.com/oauth2.0/token";
        $isRefresh = !empty($this->refreshToken);
        $params = [
            'grant_type' => $isRefresh ? 'refresh_token' : 'authorization_code',
            'client_id' => $this->appId,
            'client_secret' => $this->appKey,
            'redirect_uri' => $this->callback
        ];
        if ($isRefresh)
        {
            $params['refresh_token'] = $this->refreshToken;
        }
        else
        {
            $params['code'] = $code;
        }

        $response = @file_get_contents($url."?".http_build_query($params));

        $result = [];
        if (strpos($response, "callback") !== false)
        {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
            $msg = json_decode($response);
            if (isset($msg->error))
            {
                $result['code'] = $msg->error;
                $result['desc'] = $msg->error_description;
                return $result;
            }
        }

        parse_str($response, $result);
        $result['code'] = 0;
        $this->accessToken = $result['access_token'];
        $this->refreshToken = $result['refresh_token'];
        $result['expires_in'] += time();
        $user = self::getSelfInfo();
        if (!$user['openid'])
        {
            return $user;
        }
        $result['uid'] = $user['openid'];
        UserApi::saveAccessToken($result);
        return $result;
    }

    function revokeAccessToken()
    {
//        $url = "https://api.weibo.com/oauth2/revokeoauth2";
//        $this->oAuthRequest($url."?access_token=".$this->access_token, "GET", []);
    }

    /**
     * @return array
     *
     'client_id' => string '101190038' (length=9)
     'openid' => string '30DB9652F909CCA658045AF561C7B5CD' (length=32)
     */
    public function getSelfInfo()
    {
        if (!$this->checkAccessToken())
        {
            return ['code' => -2, 'desc' => "Access token not valid"];
        }
        $url = "https://graph.qq.com/oauth2.0/me?access_token=" . $this->accessToken;
        $response = @file_get_contents($url);

        if (strpos($response, "callback") !== false)
        {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
        }
        $user = json_decode($response, true);
        if (empty($user))
        {
            Logger::ERROR("Got empty userinfo from qq", "", "", "qq_sdk_err");
            return ['code'=>-3, "desc" => "Error Response from qq"];
        }
        if (isset($user->error))
        {
            Logger::ERROR("Got userinfo error from qq", "", "", "qq_sdk_err");
            return ['code' => $user['error'], 'desc' => $user['error_description']];
        }
        return $user;
    }

    public function getUserInfo($uid)
    {
        $get_user_info = "https://graph.qq.com/user/get_user_info?"
            . "access_token=" . $this->accessToken
            . "&oauth_consumer_key=" . $this->appId
            . "&openid=" . $uid
            . "&format=json";

        $info = @file_get_contents($get_user_info);
        $arr = json_decode($info, true);
        return $arr;
    }

    public function checkAccessToken()
    {
        if (empty($this->accessToken))
        {
            if (empty($this->refreshToken))
            {
                Logger::ERROR("No Access Token.", "", "", "qq_sdk_err");
                return false;
            }
            else
            {
                $this->getAccessToken();
                if (empty($this->accessToken))
                {
                    Logger::ERROR("Can NOT refresh access token.", "", "", "qq_sdk_err");
                    return false;
                }
            }
        }
        return true;
    }
}