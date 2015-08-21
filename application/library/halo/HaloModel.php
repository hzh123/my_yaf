<?php

class Halo_Model
{
    /**
     * @var HaloDataSource
     */
    public $db;
    /**
     * @var SaeTClientV2
     */
    public $weibo;
    public $errorCode;
    public $sysErrorCode = array(
        array('key' => 'sys_sham_limit', 'text' => '您的帐号中有虚假信息，请修改'),
        array('key' => 'sys_shutup_limit', 'text' => '您的帐号已被禁言'),
        array('key' => 'sys_gfw_limit', 'text' => '抱歉，您输入的内容包含敏感字符'),
    );

    /**
     * @var Logger
     */
    public $logger;


    public function __construct()
    {
        $this->db = DataCenter::getDb('wcontact');

//		$this->mc = DataCenter::getMc();
        $clazz = get_class($this);
        $this->logger = Logger::LOG($clazz);
        $this->weibo = null;
    }

    public function microTime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return $sec * 1000 + intval($usec * 1000);
    }

    public function safeImplode($delimiter, $array)
    {
        if (!empty($array) && is_array($array)) {
            return implode($delimiter, $array);
        }
        return 0;
    }

    public function initWeibo()
    {
        $this->weibo = DataCenter::getWeiboClient();
    }

    public function ridFieldPrefix($data)
    {
        $result = array();
        if ($data) {
            foreach ($data as $k => $v) {
                $result[substr($k, 1)] = $v;
            }
        }
        return $result;
    }

    public function ridResultSetPrefix(&$data)
    {
        $result = array();
        if (!empty($data)) {
            foreach ($data as &$row) {
                $result[] = $this->ridFieldPrefix($row);
            }
        }
        return $result;
    }

    public function checkPunish($uid)
    {
        $sham = $this->db->getVarByCondition('account_user', 'Fuid=' . $uid, 'Fsham');
        if ($sham > 0)
            return $this->getErrorCode('sys_sham_limit');
        else
            return 0;
    }

    public function getErrorCode($tag)
    {
        foreach ($this->sysErrorCode as $k => $v) {
            if ($v['key'] == $tag) {
                return -2000 - ($k + 1);
            }
        }
        foreach ($this->errorCode as $k => $v) {
            if ($v['key'] == $tag) {
                return 0 - ($k + 1);
            }
        }
        return -1000;

// 		$pos = array_search($tag,$this->errorCode);
// 		if($pos>=0)
// 			return 0-($pos+1);
// 		else
// 			return -1000;
    }

    public function getErrorText($code)
    {
        if ($code <= -2000) {
            $idx = 2000 + abs($code) - 1;
            if (isset($this->sysErrorCode[$idx])) {
                return $this->sysErrorCode[$idx]['text'];
            }
        } else {
            $idx = abs($code) - 1;
            if (isset($this->errorCode[$idx])) {
                return $this->errorCode[$idx]['text'];
            }
        }
        return 'Unknow error';
    }

    public static function arrayTransform($data, $key, $value)
    {
        $res = array();
        if (!$data || count($data) == 0)
            return $res;

        foreach ($data as $item) {
            $res[$item[$key]] = $item[$value];
        }
        return $res;
    }

    public function tidyPlaceData($data)
    {
        return self::arrayTransform($data, 'id', 'name');
    }

    public function log($function, $key, $data = null)
    {
//不再使用        
//		$session = Yaf_Session::getInstance();
//		$logUids = array('1662702927','3028151737','1676007674', '1188674754');
//		$uid = $session->offsetGet('uid');
//		if(in_array($uid, $logUids))
//		{
//			$row = array('Fuid'=>$uid,'Ffunction'=>$function,'Fkey'=>$key,'Ftime'=>haloMicroTime());
//			if($data)
//			{
//				$row['Fdata']=$data;
//			}
//			$this->db->insertTable('data_log_model', $row);
//		}
    }

    public function getCurrentTime()
    {
        return time();
    }


    public function getPinyin($string, $onlyFull = false)
    {
        $len = mb_strlen($string, 'utf-8');
        $pinyin = '';
        $pinyin_se = '';
        $pinyin_th = '';
        for ($index = 0; $index < $len; $index++) {
            $char = mb_substr($string, $index, 1, 'utf-8');
            $ascii = ord($char);
            if ($ascii < 128 && $ascii > 0) {
                $pinyin = $pinyin . $char;
                if (!$onlyFull) {
                    $pinyin_se = $pinyin_se . $char;
                    $pinyin_th = $pinyin_th . $char;
                }
            } else {
                $result = $this->db->getVarByCondition('data_pinyin', sprintf('Fword=\'%s\' LIMIT 1', $char), 'Fpy');
                if (!empty($result)) {
                    $pinyin = $pinyin . $result;
                    if (!$onlyFull) {
                        $pinyin_th = $pinyin_th . substr($result, 0, 1);
                        if (strlen($result) > 1) {
                            $c = substr($result, 0, 2);
                            if ($c == 'zh' || $c == 'ch' || $c == 'sh') {
                                $pinyin_se = $pinyin_se . $c;
                            } else {
                                $pinyin_se = $pinyin_se . substr($result, 0, 1);
                            }
                        } else {
                            $pinyin_se = $pinyin_se . $result;
                        }
                    }
                }
            }
        }
        return ($onlyFull ? $pinyin : sprintf('%s,%s,%s', $pinyin, $pinyin_se, $pinyin_th));
    }

    public function getCookieForAuth()
    {
        $session = $this->db->getRowByCondition('account_session', 'Fuid>0 AND Ftype=0 ORDER BY Ftime DESC');
        $token = WContact_Session_Handler::unserializesession($session['Fdata']);
        return $token['Auth'];
    }

    public function getSubArrayWithKey(&$array, $keys, $changeKeyMap = '')
    {
        if (empty($array))
            return false;

        if (is_string($keys)) {
            $keys = explode(',', $keys);
            array_walk($keys, function (&$val) {
                $val = trim($val);
            });
        }

        $ret = array_intersect_key($array, array_flip($keys));

        if (!empty($changeKeyMap) && is_array($changeKeyMap)) {
            $ret = $this->changeArrayItemKey($ret, $changeKeyMap);
        }
        return $ret;

    }


    /**
     * 改变Array中指定元素key的值
     * @param $array
     * @param $keyMap ，eg:array('Fid'=>'id','Fname'='name')
     * @return mixed
     */
    public function changeArrayItemKey(&$array, $keyMap)
    {
        if (!empty($keyMap)) {
            foreach ($keyMap as $old => $new) {
                if ($array[$old] != null) {
                    $array[$new] = $array[$old];
                    unset($array[$old]);
                }

            }
        }

        return $array;
    }


    public function arrayGetSubArrayWithKey(&$array, $keys, $changeKeyMap = '')
    {
        if (empty($array))
            return array();

        $result = array();
        foreach ($array as $v) {
            $result[] = $this->getSubArrayWithKey($v, $keys, $changeKeyMap);
        }
        return $result;
    }

}
