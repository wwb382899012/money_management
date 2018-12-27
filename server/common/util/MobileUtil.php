<?php

/**
 * 手机号码工具类
 * Class MobileUtil
 */

class MobileUtil {

    private static $VIR_MOBILE_PREFIX = '9';

    //特殊处理手机归属地
    public static function handleTelCity($city)
    {
        if(strpos($city, '北京') !== false)
        {
            return '北京';
        }
        else if(strpos($city, '天津') !== false)
        {
            return '天津';
        }
        else if(strpos($city, '上海') !== false)
        {
            return '上海';
        }
        else if(strpos($city, '重庆') !== false)
        {
            return '重庆';
        }
        else
        {
            return $city;
        }
    }

//    private static $MOBILE_PRE_FIX = array ('10', '13', '15', '17', '18');

    /**
     * 自动生成虚拟手机号
     *
     * @return mixed
     */
    public static function getVirtureMobileNo() {
        $random_fix = rand(1000000000, 9999999999);
        return '9'.$random_fix;
    }

    /**
     * 验证是否虚拟手机
     * 只需要通过正则表达式校验，无需查询test_user db
     * @param $mobile_no
     * @throws Exception
     */
    public static function isVirtureMobile($mobile_no) {
        if (MobileUtil::validateMobileWithOutVir($mobile_no)) {
            return false;
        } elseif (MobileUtil::validateMobileWithVir($mobile_no)) {
            return true;
        } else {
            BaseError::throw_exception(OtherError::$INVALID_PARAM);
        }
    }

    /**
     * 验证手机号合法性，兼容虚拟账号版
     *
     * @param $mobile_no
     * @return bool
     */
    public static function validateMobileWithVir($mobile_no) {
        $result = preg_match('/^9[0-9]{10}$/', $mobile_no);
        return $result;
    }

    /**
     * 验证手机号合法性，不兼容虚拟账号版
     *
     * @param $mobile_no
     * @return bool
     */
    public static function validateMobileWithOutVir($mobile_no) {
        $result = preg_match('/^(10[0-9]|13[0-9]|15[012356789]|17[0123456789]|18[0-9]|14[57])[0-9]{8}$/', $mobile_no);
        return $result;
    }

    public static function isPhoneNum($phone)
    {
        if(strlen($phone) > 11)
            return false;
        if(preg_match("/^1[34578]{1}\d{9}$/", $phone))
            return true;
        return false;
    }
}