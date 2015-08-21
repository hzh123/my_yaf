<?php

class YafController extends Yaf_Controller_Abstract{

    protected function getLegalParam($tag,$legalType,$legalList=array(),$default=null)
    {
        //检查是否是post请求
        if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') == 0)
        {
            $param = $this->getRequest()->getPost($tag,$default);
        }
        else
        {
            $param = $this->getRequest()->get($tag,$default);
        }
        if($param!==null)
        {
            switch($legalType)
            {
                case 'eid': //encrypted id
                {
                    if($param)
                    {
                        if ($param === $default)
                        {
                            return $default;
                        }
                        else
                        {
                            return UidEncryptUtil::decryptUid($param);
                        }
                    }
                    else
                        return null;
                    break;
                }
                case 'id':
                {
                    if (preg_match ('/^\d{1,20}$/', strval($param) ))
                    {
                        return strval($param);
                    }
                    break;
                }
                case 'time':
                {
                    return intval($param);
                    break;
                }
                case 'int':
                {
                    if (!is_numeric($param))
                    {
                        break;
                    }
                    if ($param >= -2147483648 && $param <= 2147483647)
                    {
                        $val = intval($param);
                    }
                    else
                    {
                        $val = $param * 1;
                    }

                    if(count($legalList)==2)
                    {
                        if($val>=$legalList[0] && $val<=$legalList[1])
                            return $val;
                    }
                    else
                        return $val;
                    break;
                }
                case 'float':
                {
                    if (!is_numeric($param)) {
                        break;
                    }
                    $var = floatval($param);
                    return $var;
                    break;
                }
                case 'str':
                {
                    $val = strval($param);
                    if(count($legalList)==2)
                    {
                        if(($val)>=$legalList[0] && ($val)<=$legalList[1])
                            return $val;
                    }
                    else
                        return $val;
                    break;
                }
                case 'trim_spec_str':
                {
                    $val = trim(strval($param));
                    if(!preg_match("/['.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$val))
                    {
                        if(count($legalList)==2)
                        {
                            if(strlen($val)>=$legalList[0] && strlen($val)<=$legalList[1])
                                return $val;
                        }
                        else
                            return $val;
                    }
                    break;
                }
                case 'enum':
                {
                    if(in_array($param,$legalList))
                    {
                        return $param;
                    }
                    break;
                }
                case 'array':
                {
                    if(count($legalList)>0)
                        return explode($legalList[0],strval($param));
                    else
                    {
                        if (empty($param))
                            return array();
                        return explode(',',strval($param));
                    }

                    break;
                }
                case 'json':
                {
                    return json_decode(strval($param),true);
                    break;
                }
                case 'raw':
                {
                    return $param;
                    break;
                }
                default:
                    break;
            }
        }
        if ($default != null)
        {
            return $default;
        }
        return false;
    }
    protected function getPageParams()
    {
        $param['offset'] = $this->getLegalParam('offset', 'int', array(), 0);
        $param['length'] = $this->getLegalParam('length', 'int', array(), 20);

        return $param;
    }
    protected function getSharpParam()
    {
        $url = $_SERVER['REQUEST_URI'];
        $idx = stripos($url, "#");
        if($idx === false)
            return array();
        $param = array();
        $paramstr = substr($url, $idx);
        return $paramstr;

    }
    protected function checkReferer()
    {
        //检查是否是post请求
        if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0)
        {
            $this->inputRefererErrorResult();
        }

        $refer = $_SERVER['HTTP_REFERER'];
        if(empty($refer))
        {
            $this->inputRefererErrorResult();
        }
        else
        {
            $legalHost = array('weibo.com', 'weibo.cn', 'zhaopin.weibo.cn','zhaopin.weibo.com', "pre.zhaopin.weibo.com","www.zhaopin.weibo.com", "zhaopin.renmai.cn");
            $testHost = array('local.weibo.com', 'local.weibo.cn','renmai.weibo.com',
                'renmai.weibo.cn','renmai.cn','fix.zhaopin.weibo.com', 'dev.zhaopin.weibo.com',
                'local.zhaopin.weibo.com', "pre.zhaopin.weibo.com", "fix.weizhaopin.com");
            $config = HaloEnv::getConfig();
            if ($config->app->debug == 1)
                $legalHost = array_merge($legalHost, $testHost);
            $url = parse_url($refer);
            $result = false;
            foreach($legalHost as $v)
            {
//                $pos = stripos($url['host'],$v);
//                if($pos!==false)
                if($v == $url['host'])
                {
                    $result = true;
                    break;
                }
            }
            if($result===false)
                $this->inputRefererErrorResult();
            else
            {
                if( !$_FILES  && $_REQUEST['trace_type']!='ajax')
                    $this->inputRefererErrorResult();
            }
        }
    }

    protected function getLegalParamArray($fields)
    {
        $params = array();
        foreach($fields as $f => $type)
        {
            $params[$f] = $this->getLegalParam($f, $type);
        }
        return $params;
    }

    protected function getRequestDate($year='year', $month='month', $day='day')
    {
        $y = $this->getLegalParam($year, 'int');
        $m = $this->getLegalParam($month, 'int');
        $d = $this->getLegalParam($day, 'int');
        return mktime(0, 0, 0, $m, $d, $y);
    }

    protected function inputIdResult($result,$model)
    {
        if($result<0)
            $this->inputErrorResult($result, $model);
        else
            $this->inputResult(array('id'=>$result));
    }

    protected function inputStateResult($result,$model)
    {
        if($result<0)
            $this->inputErrorResult($result, $model);
        else
            $this->inputResult(array('state'=>$result));
    }

    protected function inputNullResult($result,$model)
    {
        if($result<0)
            $this->inputErrorResult($result, $model);
        else
            $this->inputResult();
    }

    protected function inputUpgradeResult($result, $model)
    {
        $desc = $model->getErrorText($result['code']);
        echo json_encode(array('data'=>$result,'code'=>$result['code'], 'desc'=>$desc));
        die();
    }

    protected function inputResult($data=null)
    {
        echo json_encode(array('data'=>$data,'code'=>0));
        haloDie();
    }

    protected function inputBase64Result($data=null)
    {
        $data['base64'] = true;
        if (isset($data['html']))
        {
            $data['html'] = base64_encode($data['html']);
        }

        echo json_encode(array('data'=>$data,'code'=>0));
        haloDie();
    }

    protected function inputErrorResult($code)
    {
//        $desc = ErrorCode::errorMsgByCode($code);
        $desc = WrmErrorCodeManager::getErrorInfo($code, "client");
        echo json_encode(array('code'=>$code,'desc'=>$desc));
        haloDie();
    }

    protected function inputParamErrorResult()
    {
//        echo json_encode(array('code'=>-100,'desc'=>'param error'));
        $this->inputErrorResult(WrmErrorCodeManager::CLIENT_ERR_ILLEGAL_PARAM);
//        haloDie();
    }

    protected function inputRefererErrorResult()
    {
//        echo json_encode(array('code'=>-101,'desc'=>'referer error'));
//        haloDie();
        YafDebug::log(sprintf("Illegal referer: uid is %s, referer is %s, params is %s ", WZhaopinEnv::getWid(), $_SERVER['HTTP_REFERER'], json_encode($_SERVER)), "refer_error");
        $this->inputErrorResult(WrmErrorCodeManager::CLIENT_ERR_ILLEGAL_REFER);
    }

    protected function _forward($action,$controller='',$parameters=array())
    {
        $this->forward('Index', $controller, $action, $parameters);
    }

    protected function render($tpl, array $parameters = null)
    {
        $this->display($tpl, $parameters);
    }
}

