<?php

/**
 * Created by PhpStorm.
 * User: zhenghua.hao
 * Date: 15/3/24
 * Time: 下午5:58
 */
class CommonUtil
{


    public static function getCityId($location)
    {
        if (is_object($location)) {
            $location = CommonUtil::object2array($location);
        }
        if (!is_array($location)) {
            return 0;
        }
        if ($location['cid']) {
            $cityId = $location["cid"];
        } else {
            $cityId = $location["pid"];
        }
        return $cityId;
    }

    public static function object2array(&$object)
    {
        $object = json_decode(json_encode($object), true);
        return $object;
    }
}