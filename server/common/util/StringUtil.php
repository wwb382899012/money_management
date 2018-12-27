<?php

define('MAX_STR_LEN', 1000000);//最大字串长度
define('MAX_INT', 2147483647);//最大整数
define('MIN_INT', -2147483648);//最小整数
define('HexString', 1);    //0-9 a-f A-F
define('LowHexString', 2);
define('UpperHexString', 3);
define('DigistString', 4);
define('AlphaString', 5);// a-z A-Z
define('ALnumString', 6);//a-z A-Z 0-9
define('UinString', 7);// a-z A-Z 0-9 _ 用户ID
define('EmailString', 8);///^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$/ email
define('MobileString', 9);///^0?(13[0-9]|15[012356789]|17[678]|18[0-9]|14[57])[0-9]{8}$/ mobile
define('TelString', 10);// /^([0-9]{3,4}-)?[0-9]{7,8}$/ telephone
define('QQString', 11);// /^[1-9][0-9]{4,9}$/ QQ
define('DBString', 12);// /^\w+_db.t_\w+$/ db库表
class StringUtil
{
    /**
     * 对变量进行 JSON 编码
     * @param mixed value 待编码的 value ，除了resource 类型之外，可以为任何数据类型，该函数只能接受 UTF-8 编码的数据
     * @return string 返回 value 值的 JSON 形式
     */
    public static function json_encode_ex($value)
    {
        if(version_compare(PHP_VERSION,'5.4.0','<'))
        {
            $str = json_encode($value);
            $str = preg_replace_callback(
                '#\\\u([0-9a-f]{4})#i',
                function($matchs)
                {
                    return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
                },
                $str
            );
            return $str;
        }
        else
        {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
    }

    public static function create_password($pw_length = 8)
    {
        $randPwd = '';
        for ($i = 0; $i < $pw_length; $i++)
        {
            $randPwd .= chr(mt_rand(33, 126));
        }
        return $randPwd;
    }

    public static function create_token()
    {
        return sha1(uniqid(self::random('alnum', 32), TRUE));
    }

    public static function random($type = NULL, $length = 8)
    {
        if ($type === NULL)
        {
            // Default is to generate an alphanumeric string
            $type = 'alnum';
        }

        $utf8 = FALSE;

        switch ($type)
        {
            case 'alnum':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alpha':
                $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'hexdec':
                $pool = '0123456789abcdef';
                break;
            case 'numeric':
                $pool = '0123456789';
                break;
            case 'nozero':
                $pool = '123456789';
                break;
            case 'distinct':
                $pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
                break;
            default:
                $pool = (string) $type;
                $utf8 = ! UTF8::is_ascii($pool);
                break;
        }

        // Split the pool into an array of characters
        $pool = ($utf8 === TRUE) ? UTF8::str_split($pool, 1) : str_split($pool, 1);

        // Largest pool key
        $max = count($pool) - 1;

        $str = '';
        for ($i = 0; $i < $length; $i++)
        {
            // Select a random character from the pool and add it to the string
            $str .= $pool[mt_rand(0, $max)];
        }

        // Make sure alnum strings contain at least one letter and one digit
        if ($type === 'alnum' AND $length > 1)
        {
            if (ctype_alpha($str))
            {
                // Add a random digit
                $str[mt_rand(0, $length - 1)] = chr(mt_rand(48, 57));
            }
            elseif (ctype_digit($str))
            {
                // Add a random letter
                $str[mt_rand(0, $length - 1)] = chr(mt_rand(65, 90));
            }
        }

        return $str;
    }

    public static function create_login_token($user_id, $tel)
    {
        $tm = microtime(true);
        return md5($tm.$user_id.$tel.H5_LOGIN_TOKEN_PRIVATE_KEY);
    }

    public static function create_seq()
    {

    }

    /**
     * 签名
     * @param  [type] $data [description]
     * @param  [type] $key  [description]
     * @return [type]       [description]
     */
    public static function getSign($data, $key)
    {
        if(isset($data['key']))
            unset($data);
        $sign_arr = array();
        foreach($data as $k=>$v)
            $sign_arr[] = $v;
        $sign_arr[] = $key;

        return md5(implode("-",$sign_arr));
    }

    /**
     * 加密
     *
     * @param string $string  需要加密的字符串
     *
     */
    public static function encode($string, $key) {

        $encode_str = '';

        $base64_str = base64_encode($string);
        $base64_str_len = strlen($base64_str);
        $base64_key = base64_encode($key);
        $base64_key_len = strlen($base64_key);

        for($i = 0; $i < $base64_str_len ; $i ++) {

            $str_ord = ord($base64_str[$i]);
            $key_ord = ord($base64_key[$i % $base64_key_len]);
            $ord = $str_ord ^ $key_ord;
            $chr = chr($ord);
            $encode_str .= $chr;
        }

        return base64_encode($encode_str);
    }

    public static function isStrValid($str, $type) {
        if (!is_string($str)) {
            return false;
        }
        $ret = false;
        switch ($type) {
            case HexString:
                $ret = preg_match('/^[0-9a-fA-F]*$/', $str);
                break;
            case LowHexString:
                $ret = preg_match('/^[0-9a-f]*$/', $str);
                break;
            case UpperHexString:
                $ret = preg_match('/^[0-9A-F]*$/', $str);
                break;
            case DigistString:
                $ret = preg_match('/^[0-9]*$/', $str);
                break;
            case AlphaString:
                $ret = preg_match('/^[a-zA-Z]*$/', $str);
                break;
            case ALnumString:
                $ret = preg_match('/^[a-zA-Z0-9]*$/', $str);
                break;
            case UinString:
                $ret = preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_]*$/', $str);
                break;
            case EmailString:
                $ret = preg_match('/^(\w-*\.*)+@(\w-?)+(\.\w{2,})+$/', $str);
                break;
            case MobileString:
                $ret=true;
                try{
                    $ret=MobileUtil::isVirtureMobile($str);
                }catch (Exception $e){
                    $ret=false;
                }
                if ($ret) {
                    //虚拟账户放过
                    $ret = true;
                } else {
                    $ret = preg_match('/^0?(10[0-9]|13[0-9]|15[012356789]|17[0123456789]|18[0-9]|14[57])[0-9]{8}$/', $str);
                }
                break;
            case TelString:
                $ret = preg_match('/^[0-9]{3,4}-[0-9]{7,8}$/', $str);
                break;
            case QQString:
            	$ret = preg_match('/^[1-9][0-9]{4,9}$/', $str);
            	break;
			case DBString:
				$ret = preg_match('/^\w+_db.t_\w+$/', $str);
				break;
            default:
                throw new \Exception("不支持该种字符串类型:{$type}",19002037);
        }
        return $ret;

    }

    public static function validStr($str, $type) {
        if (!StringUtil::isStrValid($str, $type)) {
            //$error = sprintf("%s:格式错误，非法输入 (%d)",$str,$type);
            BaseError::throw_exception(OtherError::$STRING_FORMAT_ERR, array ('str' => $str, 'type' => $type));
        }
    }

    /*
     * 是否为整数,
     * 32位机器 支持 -2147483648 至2147483647L 整数
     * 64位机器支持 -9223372036854775807 至 9223372036854775807
     */
    public static function isInt($str) {
        if (is_int($str)) {
            return true;
        }
        if (!is_string($str)) {//非字符串
            return false;
        }

        $index = 0;
        $len = strlen($str);
        $positive_flag = true;//
        if ($len > 0 && $str[0] == '-') {
            $positive_flag = false;
            $index++;
            if (!ctype_digit(substr($str, 1))) {
                return false;
            }
        } else if ($len > 0 && $str[0] == '+') {
            $index++;
            if (!ctype_digit(substr($str, 1))) {
                return false;
            }
        } else {
            if (!ctype_digit($str)) {
                return false;
            }
        }

        //去掉前导0
        while ($index < $len && $str[$index] == '0') {
            $index++;
        }
        if ($index == $len) {//全部是0
            return true;
        }

        if (PHP_INT_SIZE == 4) {
            //2147483647L
            if ($len - $index < 10) {
                return true;
            }
            if ($len - $index > 10) {
                return false;
            }

            if ($positive_flag) {
                if ($index > 0) {
                    return strcmp(substr($str, $index), '2147483647') <= 0;
                } else {
                    return strcmp($str, '2147483647') <= 0;
                }
            } else {
                if ($index > 0) {
                    return strcmp(substr($str, $index), '2147483648') <= 0;
                } else {
                    return strcmp($str, '2147483648') <= 0;
                }
            }
        }
        if (PHP_INT_SIZE == 8) {
            //9223372036854775807
            if ($len - $index < 19) {
                return true;
            }
            if ($len - $index > 19) {
                return false;
            }

            if ($positive_flag) {
                if ($index > 0) {
                    return strcmp(substr($str, $index), '9223372036854775807') <= 0;
                } else {
                    return strcmp($str, '9223372036854775807') <= 0;
                }
            } else {
                if ($index > 0) {
                    return strcmp(substr($str, $index), '9223372036854775807') <= 0;
                } else {
                    return strcmp($str, '9223372036854775807') <= 0;
                }
            }
        } else {
            //这里不可能
            BaseError::throw_exception(OtherError::$UNSUPPORT_COMPUTER);
        }
    }

    /*
     * 转化为整数
     */
    public static function toInt($str) {
        if (StringUtil::isInt($str)) {
            return intval($str);
        } else {
            BaseError::throw_exception(OtherError::$NOT_INT_ERR, array ('str' => $str));
        }
    }

    /*
     * 元转化为分
     */
    public static function yuan2fen($yuan) {
   	
        if (is_float($yuan)) {
            //BaseError::throw_exception(OtherError::$YUAN2FEN_NOT_SUPPORT_FLOAT);
	    $yuan = strval($yuan);
        }
        if (is_int($yuan) || ctype_digit($yuan)) {
            $y = StringUtil::toInt($yuan);
            $f = 0;
        } else if (preg_match('/^[0-9]+.[0-9]$/', $yuan)) {
            $y = StringUtil::toInt(substr($yuan, 0, -2));
            $f = StringUtil::toInt(substr($yuan, -1)) * 10;
        } else if (preg_match('/^[0-9]+.[0-9][0-9]$/', $yuan)) {
            $y = StringUtil::toInt(substr($yuan, 0, -3));
            $f = StringUtil::toInt(substr($yuan, -2));
        } else if (!is_string($yuan)) {//非字符串
            BaseError::throw_exception(OtherError::$NOT_STANDARD_YUAN, array ('yuan' => $yuan));
        } else {
            $error = sprintf("(%s)不是正规的元单位", $yuan);
            BaseError::throw_exception(OtherError::$NOT_STANDARD_YUAN, array ('yuan' => $yuan));
        }
        //转化为分
        if (PHP_INT_SIZE === 4) {//32位机器
            if ($y < 21474836) {
                return $y * 100 + $f;
            } else if ($y == 21474836 && $f <= 47) {
                return $y * 100 + $f;
            } else {
                BaseError::throw_exception(OtherError::$YUAN_TO_FEN_ERR, array (
                    'yuan' => $yuan, 'num' => '2147483647L'));
            }
        } else if (PHP_INT_SIZE === 8) {//64位机器
            if ($y < 92233720368547758) {
                return $y * 100 + $f;
            } else if ($y == 92233720368547758 && $f <= 07) {
                return $y * 100 + $f;
            } else {
                BaseError::throw_exception(OtherError::$YUAN_TO_FEN_ERR, array (
                    'yuan' => $yuan, 'num' => '9223372036854775807'));
            }
        } else {
            BaseError::throw_exception(OtherError::$UNSUPPORT_COMPUTER);
        }

    }

    /*
     * 分转化为元
     */
    public static function fen2yuan($fen) {
        if (is_numeric($fen) && $fen <= 0) {
            return 0;
        }
        if (is_int($fen) || ctype_digit($fen)) {
            $fen = StringUtil::toInt($fen);
            if ($fen % 100 === 0) {
                return sprintf("%d", $fen / 100);
            } else {
                return sprintf("%d.%02d", ($fen - $fen % 100) / 100, $fen % 100);
            }

        } else {
            //$error = sprintf("(%s)not fen ",$fen);
            BaseError::throw_exception(OtherError::$FEN_TO_YUAN_ERR, array ('fen' => $fen));
        }
    }

    //number division 10
    public static function  numDivision10($num){
        if (is_numeric($num) && $num <= 0) {
            return 0;
        }
        if (is_int($num) || ctype_digit($num)) {
            $num = StringUtil::toInt($num);
            if ($num % 10 === 0) {
                return sprintf("%d", $num / 10);
            } else {
                return sprintf("%d.%01d", ($num - $num % 10) / 10, $num % 10);
            }
        }
    }

    //number multiplication 10
    //该函数仅适用于处理一位小数
    public static function  numMultiplication10($num){
        if (is_numeric($num) && $num <= 0) {
            return 0;
        }

        if(strpos($num , '.') === false){
            return $num * 10;
        }

        $decimal = explode('.' , $num);
        return $decimal[0] * 10 + $decimal[1];
    }

    /*
     * 数组序列化
     */
    public static function array2string($x, $minLen = 0, $maxLen = PHP_INT_MAX, $strType = 0) {
        if (is_array($x)) {
            if (count($x) === 0) {
                return '';
            }
            $value = json_encode($x, JSON_UNESCAPED_UNICODE);
            if ($minLen === 0 && $maxLen === PHP_INT_MAX) {
                return $value;
            }

            $len = mb_strlen($value, "UTF8");
            if ($len < $minLen || $len > $maxLen) {
                //$error = sprintf("array2string'value:(%s):length should be bettwen (%d)(%d)",$value,$minLen,$maxLen);
                BaseError::throw_exception(OtherError::$ARRAY_TO_STRING_LEN_ERR, array (
                    'value' => $value, 'minLen' => $minLen, 'maxLen' => $maxLen));
            }
            if ($strType) {
                StringUtil::validStr($value, $strType);
            }
            return $value;
        } else {
            BaseError::throw_exception(OtherError::$NOT_ARRAY_ERR, array ('type' => gettype($x)));
        }

    }

    /*
     * 数组序列化
     * objectArray2string与array2string不同之处：
     * array2string			转化普通数组		array('0'=>'0','1'=>'5','2'=>'8') => ["0","5","8"]
     * objectArray2string	转化普通数组		array('0'=>'0','1'=>'5','2'=>'8') => {"0":"0","1":"5","2":"8"}
     */
    public static function objectArray2string($x, $minLen = 0, $maxLen = PHP_INT_MAX, $strType = 0) {
        if (is_array($x)) {
            if (count($x) === 0) {
                return '';
            }
            $value = json_encode((object)$x, JSON_UNESCAPED_UNICODE);
            if ($minLen === 0 && $maxLen === PHP_INT_MAX) {
                return $value;
            }

            $len = mb_strlen($value, "UTF8");
            if ($len < $minLen || $len > $maxLen) {
                //$error = sprintf("array2string'value:(%s):length should be bettwen (%d)(%d)",$value,$minLen,$maxLen);
                BaseError::throw_exception(OtherError::$ARRAY_TO_STRING_LEN_ERR, array (
                    'value' => $value, 'minLen' => $minLen, 'maxLen' => $maxLen));
            }
            if ($strType) {
                StringUtil::validStr($value, $strType);
            }
            return $value;
        } else {
            BaseError::throw_exception(OtherError::$NOT_ARRAY_ERR, array ('type' => gettype($x)));
        }

    }

    public static function string2array($x) {
        if ($x == NULL || $x == "") {//先做兼容处理
            return array ();
        }

        return json_decode($x, True);
    }


    /*
     * 向上取整数
     */
    public static function divUpper($num, $div) {
        if (PHP_INT_SIZE == 4 && $num <= PHP_INT_MAX) {    #32bitPHP下解决整数溢出的权宜之计
            $num = intval($num);
        }

        $div = StringUtil::toInt($div);
        if ($div == 0) {
            BaseError::throw_exception(OtherError::$DIV_ERR);
        }
        if (is_int($num) || ctype_digit($num)) {
            $num = StringUtil::toInt($num);
            if ($num % $div) {
                return ($num - $num % $div) / $div + 1;
            } else {
                return $num / $div;
            }
        } else {
            //$error = sprintf("(%s)not int type:(%s)",$num,gettype($num));
            BaseError::throw_exception(OtherError::$NOT_INT_ERR, array ('str' => gettype($num)));
        }
    }

    /*
     * 整除
     */
    public static function div($num, $div) {
        $div = StringUtil::toInt($div);
        if ($div == 0) {
            BaseError::throw_exception(OtherError::$DIV_ERR);
        }
        if (is_int($num) || ctype_digit($num)) {
            $num = StringUtil::toInt($num);
            if ($num % $div) {
                return ($num - $num % $div) / $div;
            } else {
                return $num / $div;
            }
        } else {
            //$error = sprintf("(%s)not int type:(%s)",$num,gettype($num));
            BaseError::throw_exception(OtherError::$NOT_INT_ERR, array ('str' => $num));
        }
    }

    /*
     * 分转化为元向上取整
     */
    public static function fen2yuanUpper($fen) {
        return StringUtil::divUpper($fen, 100);
    }


    /*
     * 计算首付
     * request
     * $amount	本金	  单位为元
     * $fp_ratio 首付百分比率
     * response
     * firstpay	首付 单位元
     */
    public static function getFirstpay($amount, $fp_ratio) {
        $amount = StringUtil::yuan2fen($amount);

        return StringUtil::divUpper(StringUtil::toInt($amount) * StringUtil::toInt($fp_ratio), 10000);
    }

    /*
     * 计算首付百分比
     * request
     * $amount	   本金	单位为元
     * $firstpay 首付	单位为元
     * response
     * fp_ratio	首付百分比
     */
    public static function get_fp_ratio($amount, $firstpay) {
        $amount = StringUtil::yuan2fen($amount);
        $firstpay = StringUtil::yuan2fen($firstpay);

        if (0 == $amount || 0 == $firstpay) {
            return 0;
        }

        $fp_ratio = StringUtil::div($firstpay * 100, $amount);
        if ($fp_ratio % 5) {
            $fp_ratio = $fp_ratio - $fp_ratio % 5;
        }
        return $fp_ratio;
    }

    /*
     * 计算首付百分比  以5的倍数上取整
     * request
     * $amount	   本金	单位为元
     * $firstpay 首付	单位为元
     * response
     * fp_ratio_upper 首付比 以5倍数上取整
     */
    public static function getFpRatioUpper($amount, $firstpay) {
        $amount = StringUtil::yuan2fen($amount);
        $firstpay = StringUtil::yuan2fen($firstpay);

        $fp_ratio = StringUtil::divUpper($firstpay * 100, $amount);
        if ($fp_ratio % 5) {
            $fp_ratio += 5;
            $fp_ratio = $fp_ratio - $fp_ratio % 5;
        }
        return $fp_ratio;
    }

    /*
     * 计算月本金
     * request
     * $amount 单位为元
     * firstpay 单位为元
     * $fq_num	分期数
     *
     * response
     * capital 本金 单位元
     */
    public static function getCapital($amount, $firstpay, $fq_num) {
        $amount = StringUtil::yuan2fen($amount);
        $firstpay = StringUtil::yuan2fen($firstpay);

        $fq_num = ($fq_num != 0) ? $fq_num : 1;
        return StringUtil::divUpper(
            StringUtil::toInt($amount) - StringUtil::toInt($firstpay),
            StringUtil::toInt($fq_num) * 100);
    }

    /*
     * 计算月服务费
     * request
     * $amount 本金		单位为元
     * $firstpay 首付	单位为元
     * $fq_num 分期数
     * $ratio	费率		万分比
     * response
     * fee	服务费 单元元
     */
    public static function getFee($amount, $firstpay, $fq_num, $ratio) {
        $amount = StringUtil::yuan2fen($amount);
        $firstpay = StringUtil::yuan2fen($firstpay);

        //百分比到万分比做的兼容
        if ($ratio < 100 && $ratio != 0) {
            $ratio = StringUtil::yuan2fen($ratio);
        }

        $fq_num = ($fq_num != 0) ? $fq_num : 1;
        if (PHP_INT_SIZE == 4) {    #32bitPHP下解决整数溢出的权宜之计
            return StringUtil::divUpper(
                (StringUtil::toInt($amount) - StringUtil::toInt($firstpay)) * StringUtil::toInt($ratio) / 100,
                StringUtil::toInt($fq_num) * 10000);
        } else {
            return StringUtil::divUpper(
                (StringUtil::toInt($amount) - StringUtil::toInt($firstpay)) * StringUtil::toInt($ratio),
                StringUtil::toInt($fq_num) * 1000000);
        }
    }

    /*
     * 计算月供
     * request
     * $amount 		本金		单位为元
     * $firstpay 	首付		单位为元
     * $fq_num 		分期数
     * $ratio		费率		万分比
     * response
     * $fen		月供		单位为元
     */
    public static function getMonPay($amount, $firstpay, $fq_num, $ratio) {

        $capital = StringUtil::yuan2fen(StringUtil::getCapital($amount, $firstpay, $fq_num));
        $fee = StringUtil::yuan2fen(StringUtil::getFee($amount, $firstpay, $fq_num, $ratio));

        return StringUtil::fen2yuan($capital + $fee);
    }

    /*
     * 计算日供
     * request
     * $amount 		本金		单位为元
     * $firstpay 	首付		单位为元
     * $fq_num 		分期数
     * $ratio		费率		万分比
     * response
     * day_pay		日供		单位为元
     */
    public static function getDayPay($amount, $firstpay, $fq_num, $ratio) {
        $mon_pay = StringUtil::getMonPay($amount, $firstpay, $fq_num, $ratio);
        return ($mon_pay - $mon_pay % 30) / 30;
    }


    /*
     * 随意还  服务费
     * request
     * amount  本金		单位元
     * load_day	借款日
     * repay_day 还款日
     * $ratio	费率  万分比
     * response
     * random_fee 随意还服务费 单位元
     */
    public static function getRandomFee($amount, $load_date, $repay_date, $ratio) {

        if (!StringUtil::isValidDate($load_date) || !StringUtil::isValidDate($repay_date)) {
            BaseError::throw_exception(UserError::$ORDER_DATE_FORMAT_ERR);
        }
        $day_num = StringUtil::dayMinus($repay_date, $load_date);
        $amount = StringUtil::yuan2fen($amount);

        if ($day_num <= 0 || $amount <= 0) {
            return 0;
        } else {
            return StringUtil::fen2yuan(StringUtil::divUpper($amount * $day_num * $ratio, 10000));
        }

    }

    /*
     * 随意还  罚金服务费
     * request
     * capital 本金	单位元
     * penalty_date	罚息起始日
     * $overdue_ratio	逾期费率 万分比
     * response
     * random_penalty	随意还罚金 单位元
     */
    public static function getRandomPenalty($capital, $penalty_date, $overdue_ratio) {

        if (!StringUtil::isValidDate($penalty_date)) {
            BaseError::throw_exception(UserError::$ORDER_DATE_FORMAT_ERR);
        }
        $day_num = StringUtil::dayMinus(StringUtil::getToday(), $penalty_date);
        $capital = StringUtil::yuan2fen($capital);

        if ($day_num <= 0 || $capital <= 0) {
            return 0;
        } else {
            return StringUtil::fen2yuan(StringUtil::divUpper($capital * $day_num * $overdue_ratio, 10000));
        }

    }

    /*
     * 取现手续费
     * 计算规则：取现金额的2%，最低5元
     *
     * 参数：
     * request
     * $capital 本金 	单位元
     * $handling_ratio 费率，万分比
     * $min_handling_fee  最低手续费 单位元
     *
     * response
     * handling_fee	手续费	单位元
     */
    public static function getHandlingFee($capital, $handling_ratio, $min_handling_fee = 3) {
        $capital = StringUtil::yuan2fen($capital);
        if ($capital <= 0 || $handling_ratio == 0) {//本金小于零 or 手续费费率为零 直接返回手续费金额为零
            return 0;
        } else {
            return max($min_handling_fee, StringUtil::fen2yuan(StringUtil::divUpper($capital * $handling_ratio, 10000)));
        }
    }

    /*
     * 默认值 为空
     */
    public static function getParam($set, $key) {
        if (null === $set) {
            throw new \Exception('参数集合不能为空', ERR_BAD_PARAM);
        }
        if (!is_array($set)) {
            throw new \Exception('参数集合必须为数组', ERR_BAD_PARAM);
        }
        if (null === $key) {
            throw new \Exception('参数集合不能为空', ERR_BAD_PARAM);
        }
        if (array_key_exists($key, $set)) {
            return $set[$key];
        } else {
            return '';
        }
    }

    /*
     * 获取字符串参数
     * 如果值不存在默认值 为空
     */
    public static function getStrParam($set, $key, $minLen = 0, $maxLen = PHP_INT_MAX, $strType = 0) {
        $value = StringUtil::getParam($set, $key);
        
        if (is_int($value) || is_float($value)) {
            $value = strval($value);
        } else if (!is_string($value)) {
            BaseError::throw_exception(OtherError::$UNSUPPORT_TYPE, array ('key' => $key, 'type' => gettype($value)));
        }
        $len = mb_strlen($value, "UTF8");
        if ($len < $minLen || $len > $maxLen) {
            BaseError::throw_exception(OtherError::$STRING_LEN_ERR, array (
                'key' => $key, 'value' => $value, 'minLen' => $minLen, 'maxLen' => $maxLen));
        }
        if ($strType) {
            StringUtil::validStr($value, $strType);
        }  
        return $value;
    }

    /*
     * 字符串参数是否合法
     * 不存在认为不合法
     */
    public static function isStrParamValid($set, $key, $minLen = 0, $maxLen = PHP_INT_MAX, $strType = 0) {
        if (null === $set) {
            BaseError::throw_exception(OtherError::$PARAM_SET_EMPTY);
        }
        if (null === $key) {
            BaseError::throw_exception(OtherError::$PARAM_SET_EMPTY);
        }
        if (!array_key_exists($key, $set)) {
            return false;
        }
        $value = $set[$key];

        if (is_int($value)) {
            $value = strval($value);//数字兼容处理
        } else if (!is_string($value)) {
            return false;//
        }
        $len = mb_strlen($value, "UTF8");
        if ($len < $minLen || $len > $maxLen) {//长度不正确
            return false;
        }
        if ($strType) {
            return StringUtil::isStrValid($value, $strType);
        }
        return true;
    }

    /*
     * 获取数字参数
     * 如果值不存在默认值 为0
     */
    public static function getIntParam($set, $key, $min = 0, $max = PHP_INT_MAX) {
        $value = StringUtil::getParam($set, $key);
        if (is_string($value)) {//字符串处理
            if (StringUtil::isInt($value)) {
                $value = StringUtil::toInt($value);
            } else if (empty($value)) {
                $value = 0;
            } else {
                $value = 0;
            }
        } else if (is_int($value)) {
            //do nothing
        } else {
            $value = 0;
        }
        if ($value < $min || $value > $max) {
            $value = 0;
        }
        return $value;
    }

    /*
     * 数字参数是否合法
     * 不存在或为空认为不合法
     */
    public static function isIntParamValid($set, $key, $min = 0, $max = PHP_INT_MAX) {
        if (null === $set) {
            BaseError::throw_exception(OtherError::$PARAM_SET_EMPTY);
        }
        if (null === $key) {
            BaseError::throw_exception(OtherError::$PARAM_SET_EMPTY);
        }
        if (!array_key_exists($key, $set)) {
            return false;
        }
        $value = $set[$key];

        if (is_string($value)) {//字符串处理
            if (StringUtil::isInt($value)) {
                $value = StringUtil::toInt($value);
            } else {
                return false;//非数字
            }
        } else if (is_int($value)) {
            //do nothing
        } else {
            return false;//非数字
        }
        if ($value < $min || $value > $max) {
            return false;//数字范围不合法
        }
        return true;
    }

    public static function getArrayParam($set, $key) {
        $value = StringUtil::getParam($set, $key);
        if (is_array($value)) {
            //do nothing
        } else if (empty($value)) {
            $value = array ();
        } else {
            //$error = sprintf("(%s):value:(%s):should be array not ".gettype($value),$key,$value);
            BaseError::throw_exception(OtherError::$NOT_ARRAY_ERR, array ('type' => $key));
        }
        return $value;
    }

    public static $_TIME_DELTA = 0;

    /*
     * $date - 当前日期
     */
    public static function diffDayFromNow($datetime) {
        return (strtotime($datetime) - strtotime(date("Y-m-d", time() + StringUtil::$_TIME_DELTA))) / (60 * 60 * 24);
    }

    public static function diffSecondFromNow($datetime) {
        return (strtotime($datetime) - (time() + StringUtil::$_TIME_DELTA));
    }

    public static function getToday() {
        return date("Y-m-d", time() + StringUtil::$_TIME_DELTA);
    }

    public static function getNday($Nday=1) {
        return date("Y-m-d",strtotime("-{$Nday} day"));
    }

    public static function dayMinus($day1, $day2) {
        return (strtotime($day1) - strtotime($day2)) / (60 * 60 * 24);
    }

    public static function dayAdd($date, $num) {
        return date("Y-m-d", strtotime($date) + 60 * 60 * 24 * $num);
    }

    public static function isValidDate($dateA) {
        if (strlen($dateA) != 10) {
            return false;
        }
        if (date("Y-m-d", strtotime($dateA)) != $dateA) {
            return false;
        }
        return true;
    }

    public static function daycmp($dateA, $dateB) {
        if (!StringUtil::isValidDate($dateA)) {
            BaseError::throw_exception(OtherError::$NOT_DATE_FORMAT, array ('value' => $dateA));
        }
        if (!StringUtil::isValidDate($dateB)) {
            BaseError::throw_exception(OtherError::$NOT_DATE_FORMAT, array ('value' => $dateB));
        }
        return strcmp($dateA, $dateB);
    }

    public static function daycmpToday($dateA) {
        if (!StringUtil::isValidDate($dateA)) {
            BaseError::throw_exception(OtherError::$NOT_DATE_FORMAT, array ('value' => $dateA));
        }
        return strcmp($dateA, date("Y-m-d", time() + StringUtil::$_TIME_DELTA));
    }

    public static function getFirstName($name) {
        return mb_substr($name, 0, 1, "UTF-8") . '**';
    }

    /*
     * 最多取$len的个字符，英文算1位宽度,汉字算2位宽度，
     * 只针对utf8
     */
    public static function cut_chinese($str, $len) {
        if (empty($str)) {
            return "";
        }
        $total_len = strlen($str);
        $pos = 0;
        $chinese_len = 0;
        $tmp_len = 0;
        while ($pos < $total_len && $chinese_len < $len) {
            $ch = ord($str[$pos]) & 0xFF;
            if ($ch < 128) {//一个字符
                $tmp_len = 1;
            } else if ($ch < 0xE0) {//两个字
                $tmp_len = 2;
            } else if ($ch < 0xF0) {//三个字
                $tmp_len = 3;
            } else if ($ch < 0xF8) {//4个字符
                $tmp_len = 4;
            } else if ($ch < 0xFC) {//5个字符
                $tmp_len = 5;
            } else if ($ch < 0xFE) {//6个字符
                $tmp_len = 6;
            } else {
                BaseError::throw_exception(OtherError::$NOT_UTF8, array ('value' => $str));
            }
            if ($tmp_len == 1) {
                if ($chinese_len + 1 <= $len) {
                    $chinese_len++;
                } else {
                    break;
                }
            } else {
                if ($chinese_len + 2 <= $len) {
                    $chinese_len += 2;
                } else {
                    break;
                }
            }
            $pos += $tmp_len;
        }
        return substr($str, 0, $pos);
    }

    public static function isValidCreditId($credit_type, $credit_id) {
        if ($credit_type == 1) {
            if (strlen($credit_id) != 18) {
                return false;
            }
            $sum = 0;
            $w = array (7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            for ($i = 0; $i < 17; $i++) {
                if ($credit_id[$i] < '0' || $credit_id[$i] > '9') {
                    return false;
                }
                $sum += (ord($credit_id[$i]) - ord('0')) * $w[$i];
            }
            $sum %= 11;
            $v = array (1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2);
            $check = $v[$sum];
            if ($check == 10) {
                if ($credit_id[17] != 'x' && $credit_id[17] != 'X') {
                    return false;
                }
            } else {
                if (ord($credit_id[17]) - ord('0') != $check) {
                    return false;
                }
            }
            //判断生日
            $year = (int)substr($credit_id, 6, 4);
            $mon = (int)substr($credit_id, 10, 2);
            $day = (int)substr($credit_id, 12, 2);
            if (!DateUtil::isValidDate($year, $mon, $day)) {
                return false;
            }
            return true;
        } else {
            if (empty($credit_id)) {
                return false;
            } else {
                return true;
            }
        }
    }


    public static function getMonDay($year, $mon) {
        if ($mon < 0 || $mon > 12) {
            BaseError::throw_exception(OtherError::$MONTH_NUM_ERR);
        }
        $s = array (1, 3, 5, 7, 8, 10, 12);
        if (in_array($mon, $s)) {
            return 31;
        } else if ($mon != 2) {
            return 30;
        }
        if ($year % 400 == 0 || ($year % 100 != 0 && $year % 4 == 0)) {
            return 29;
        } else {
            return 28;
        }
    }

    public static function pic_url_encode($pic_url) {
        //uploaded/credit_id/image/日期_md5.xxx
        //格式为日期_md5
        if (empty($pic_url)) {
            return '';
        }
        if ($pic_url[0] == '/') {
            $arr = explode('/', $pic_url);
        } else {
            $arr = explode('\\', $pic_url);
        }

        if (count($arr) != 5) {
            //BaseError::throw_exception(OtherError::$PIC_URL_ERR);
            return '';
        }
        if ($arr[1] != 'uploaded') {
            //BaseError::throw_exception(OtherError::$PIC_URL_ERR);
            return '';
        }
        $ret = '';
        if ($arr[2] == 'credit_id') {
            $ret = '/c';
        } else if ($arr[2] == 'school') {
            $ret = '/s';
        } else if ($arr[2] == 'contract') {
            $ret = '/t';
        } else if ($arr[2] == 'group') {
            $ret = '/g';
        } else {
            //BaseError::throw_exception(OtherError::$PIC_URL_ERR);
            return '';
        }
        if ($arr[3] != 'image') {
            //BaseError::throw_exception(OtherError::$PIC_URL_ERR);
            return '';
        }
        if (strlen($arr[4]) < 32) {
            //throw new Exception('pic url error',ERR_BAD_PARAM);
            //BaseError::throw_exception(OtherError::$PIC_URL_ERR);
            return '';
        }
        $pic_name = substr($arr[4], 0, 31);
        $pic_suffix = substr($arr[4], 31);
        if (strlen($pic_name) != 31 || $pic_name[8] != '_') {
            //BaseError::throw_exception(OtherError::$PIC_URL_ERR);
            return '';
        }
        $pic_name = substr($pic_name, 9) . substr($pic_name, 0, 8);
        $pic_name = strtr(base64_encode(hex2bin($pic_name)), '+/', '-_');
        return $ret . '/' . $pic_name . $pic_suffix;
    }

    public static function pic_url_decode($pic_url) {
        if (empty($pic_url)) {
            return '';
        }
        if ($pic_url[0] == '/') {
            $arr = explode('/', $pic_url);
        } else {
            $arr = explode('\\', $pic_url);
        }

        if (count($arr) != 3) {
            BaseError::throw_exception(OtherError::$PIC_URL_ERR);
        }

        $ret = DIRECTORY_SEPARATOR . 'uploaded' . DIRECTORY_SEPARATOR;
        if ($arr[1] == 'c') {
            $ret .= 'credit_id';
        } else if ($arr[1] == 's') {
            $ret .= 'school';
        } else if ($arr[1] == 't') {
            $ret .= 'contract';
        } else if ($arr[1] == 'g') {
            $ret .= 'group';
        } else {
            BaseError::throw_exception(OtherError::$PIC_URL_ERR);
        }
        $ret .= DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR;


        if (strlen($arr[2]) < 15) {
            BaseError::throw_exception(OtherError::$PIC_URL_ERR);
        }
        $pic_name = substr($arr[2], 0, 20);
        $pic_suffix = substr($arr[2], 20);

        $bin = base64_decode(strtr($pic_name, '-_', '+/'));
        $pic_name = bin2hex($bin);
        if (strlen($pic_name) != 30) {
            BaseError::throw_exception(OtherError::$PIC_URL_ERR);
        }
        $pic_name = substr($pic_name, 22) . '_' . substr($pic_name, 0, 22);
        return $ret . $pic_name . $pic_suffix;
    }

    /**
     * 在字符串的特定位置之前插入字符串
     *
     * @param $str    被插的串
     * @param $insert 要插的串
     * @param $index  插入的位置
     * @return 生成的新串
     */
    public static function insert($str, $insert, $index) {
        if ($index < 0) {
            BaseError::throw_exception(OtherError::$POS_INT_ERR);
        }
        $sub_str1 = substr($str, 0, $index);
        $sub_str2 = substr($str, $index);
        return $sub_str1 . $insert . $sub_str2;
    }

    /**
     * 人民币数字转大写
     *
     * @param string $number        数值
     * @param string $int_unit      币种单位，默认"元"，有的需求可能为"圆"
     * @param bool   $is_round      是否对小数进行四舍五入
     * @param bool   $is_extra_zero 是否对整数部分以0结尾，小数存在的数字附加0,比如1960.30，
     *                              有的系统要求输出"壹仟玖佰陆拾元零叁角"，实际上"壹仟玖佰陆拾元叁角"也是对的
     * @return string
     */
    public static function num2rmb($number = 0, $int_unit = '元', $is_round = TRUE, $is_extra_zero = FALSE) {
        //将数字切分成两段
        $parts = explode('.', floatval($number), 2);
        $int = isset($parts[0]) ? strval($parts[0]) : '0';
        $dec = isset($parts[1]) ? strval($parts[1]) : '';

        //如果小数点后多于2位，不四舍五入就直接截，否则就处理
        $dec_len = strlen($dec);
        if (isset($parts[1]) && $dec_len > 2) {
            $dec = $is_round ? substr(strrchr(strval(round(floatval("0." . $dec), 2)), '.'), 1) : substr($parts[1], 0, 2);
        }

        //当number为0.001时，小数点后的金额为0元
        if (empty($int) && empty($dec)) {
            return '零';
        }

        //定义
        $chs = array ('0', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
        $uni = array ('', '拾', '佰', '仟');
        $dec_uni = array ('角', '分');
        $exp = array ('', '万');
        $res = '';

        // 整数部分从右向左找
        for ($i = strlen($int) - 1, $k = 0; $i >= 0; $k++) {
            $str = '';
            // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
            for ($j = 0; $j < 4 && $i >= 0; $j++, $i--) {
                $u = $int{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
                $str = $chs[$int{$i}] . $u . $str;
            }
            //echo $str."|".($k - 2)."<br>";
            $str = rtrim($str, '0');// 去掉末尾的0
            $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0
            if (!isset($exp[$k])) {
                $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位
            }
            $u2 = $str != '' ? $exp[$k] : '';
            $res = $str . $u2 . $res;
        }

        // 如果小数部分处理完之后是00，需要处理下
        $dec = rtrim($dec, '0');

        // 小数部分从左向右找
        if (!empty($dec)) {
            $res .= $int_unit;
            // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
            if ($is_extra_zero) {
                if (substr($int, -1) === '0') {
                    $res .= '零';
                }
            }

            for ($i = 0, $cnt = strlen($dec); $i < $cnt; $i++) {
                $u = $dec{$i} > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位
                $res .= $chs[$dec{$i}] . $u;
            }
            $res = rtrim($res, '0');// 去掉末尾的0
            $res = preg_replace("/0+/", "零", $res); // 替换多个连续的0
        } else {
            $res .= $int_unit . '整';
        }
        return $res;
    }

    /**
     * 判断名字是否符合规则
     */
    public static function isNameStr($name) {
        // FIXME 耦合性太大 后续要优化
        if (defined("NAME_MOCK")) {//测试桩
            return true;
        }
        if (!self::isUtf8($name)) {
            return false;
        }
        if (!preg_match("/^[\x{4e00}-\x{9fa5}\x{3400}-\x{4dff}\x{20000}-\x{2a6df}·]+$/u", $name)) {
            return false;
        }
        return true;
    }

    /**
     * 判断是否是utf8格式
     */
    public static function isUtf8($str) {
        //		if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$word) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$word) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$word) == true){
        //			return true;
        //		}
        //		return false;
        $pos = 0;
        $total_len = strlen($str);
        while ($pos < $total_len) {
            $ch = ord($str[$pos]) & 0xFF;
            if ($ch < 128) {//一个字符
                $tmp_len = 1;
            } else if ($ch < 0xE0) {//两个字
                $tmp_len = 2;
            } else if ($ch < 0xF0) {//三个字
                $tmp_len = 3;
            } else if ($ch < 0xF8) {//4个字符
                $tmp_len = 4;
            } else if ($ch < 0xFC) {//5个字符
                $tmp_len = 5;
            } else if ($ch < 0xFE) {//6个字符
                $tmp_len = 6;
            } else {
                return false;
            }
            $pos += $tmp_len;
        }
        return true;
    }

    /**
     * 替换字符串
     */
    public static function msubstr($str, $start, $length = '', $prefix = '*') {
        $strlen = mb_strlen($str);
        $pre = '';

        if ($length != '') {
            for ($i = 0; $i < $length; $i++) {
                $pre .= $prefix;
            }
            $str = mb_substr($str, 0, $start) . $pre . mb_substr($str, $start + $length);
        } else {
            for ($i = 0; $i < $strlen - $start; $i++) {
                $pre .= $prefix;
            }
            $str = mb_substr($str, 0, $start) . $pre;
        }

        return $str;
    }

    /**
     * 生成密码（包含大写字母，小写字母，数字，特殊字符）
     *
     * @param 生成密码长度 length，不少于6位
     */
    public static function gen_passwd($length) {
        if ($length < 6) {
            return false;
        }

        $temp_passwd = '';
        $gen_passwd_base = array (
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz', '0123456789', '~!@#$%^&*()_');

        $temp_passwd .= $gen_passwd_base[0][mt_rand(0, 25)];
        $temp_passwd .= $gen_passwd_base[1][mt_rand(0, 25)];
        $temp_passwd .= $gen_passwd_base[2][mt_rand(0, 9)];
        $temp_passwd .= $gen_passwd_base[3][mt_rand(0, 11)];

        for ($i = 0; $i < ($length - 4); $i++) {
            $base_index = mt_rand(1, 24) % 4;
            switch ($base_index) {
                case 0:
                    $temp_passwd .= $gen_passwd_base[$base_index][mt_rand(0, 25)];
                    break;
                case 1:
                    $temp_passwd .= $gen_passwd_base[$base_index][mt_rand(0, 25)];
                    break;
                case 2:
                    $temp_passwd .= $gen_passwd_base[$base_index][mt_rand(0, 9)];
                    break;
                case 3:
                    $temp_passwd .= $gen_passwd_base[$base_index][mt_rand(0, 11)];
                    break;
            }
        }
        return str_shuffle($temp_passwd);
    }

    /**
     * 概率运算
     *
     * @param 期望的概率 $cnt
     */
    public static function probability_operation($cnt) {
        $num = mt_rand(1, 100);
        if ($num <= $cnt) {
            return true;
        }
        return false;
    }


    /**
     * 浮点舍去
     * @param $number
     * @param int $precision 小数位数
     * @return float
     */
    public static function round_down($number, $precision = 2){
        $fig = (int) str_pad('1', ++$precision, '0');
        return (floor($number * $fig) / $fig);
    }

    /**
     * 浮点进一
     * @param $number
     * @param int $precision 小数位数
     * @return float
     */
    public static function round_up($number, $precision = 2)
    {
        $fig = (int) str_pad('1', ++$precision, '0');
        return (ceil($number * $fig) / $fig);
    }

    /*
     * 返回值转化为百万(值乘与1000000)取6位四舍五入
     */
    public static function setValue2MillionIfExist(&$row,$key){
        if(array_key_exists($key,$row)){
            $row[$key] = round($row[$key] * 1000000,0);
            return $row[$key];
        }
        return '';
    }

    /*
     * 返回值转化为小数(值除与1000000)
     */
    public static function setValue2DecimalIfExist(&$row,$key){
        if(array_key_exists($key,$row)){
            $row[$key] = $row[$key] / 1000000;
            return $row[$key];
        }
        return '';
    }

    /**
     * @param $email
     * @param int $start 从第几位开始打码
     * @param int $star_num 打码个数
     * add by eli
     */
    public static function mosaicEmail($email, $start = 3, $star_num = 4){
        if(empty($email))
            return;
        $email = explode('@', $email);
        if(count($email) < 2)
            return;
        $email_pre = $email[0];
        $email_suff = $email[1];
        $str_len = strlen($email_pre);
        if($str_len <= $star_num){
            $email_pre = substr_replace($email_pre, '*', strlen($email_pre)-1, 1);//邮箱@之前的长度《=star_num，只打最后一位
        }else{
            $mosaic_num = $str_len - $start;
            if($mosaic_num <= $star_num){//开始打码的位置到@之间的字符个数小于打码的个数，则全部打码
                $star = str_repeat('*', $mosaic_num);
                $email_pre = substr_replace($email_pre, $star, $start, $mosaic_num);
            }else{//否则，只打要打码的个数
                $star = str_repeat('*', $star_num);
                $email_pre = substr_replace($email_pre, $star, $start, $star_num);
            }
        }
        return $email_pre . '@' . $email_suff;
    }
    
    public static function yuan_sub($yuan_minuend, $yuan_subtrahend) {
    	return StringUtil::fen2yuan(StringUtil::yuan2fen($yuan_minuend)-StringUtil::yuan2fen($yuan_subtrahend));
    }

    /**
     * 给中文或者添加"*"或者其他标志"同学"
     * @param $str          字符串
     * @param int $start    开始标志位
     * @param int $len      长度
     * @param string $mark  标志字符串
     * @return string       返回加标志的字符
     */
    public static function mosaicName($name, $start=0, $len=1, $mark="**"){
        return mb_substr($name, $start, $len, 'utf-8') . $mark;
    }
    
    
    /**
     * 元转毫(毫为分的百分之一)
     * @param　$yuan 字符串格式数字
     * @return 毫
     */
    public static function yuan2hao($yuan){
		if (is_float($yuan)) {
			$yuan = strval($yuan);
		}
		if (is_int($yuan) || ctype_digit($yuan)) {
			$y = StringUtil::toInt($yuan);
			$f = 0;
		} else if (preg_match('/^[0-9]+.[0-9]$/', $yuan)) {
			$y = StringUtil::toInt(substr($yuan, 0, -2));
			$f = StringUtil::toInt(substr($yuan, -1)) * 1000;
		} else if (preg_match('/^[0-9]+.[0-9][0-9]$/', $yuan)) {
			$y = StringUtil::toInt(substr($yuan, 0, -3));
			$f = StringUtil::toInt(substr($yuan, -2)) * 100;
		}else if (preg_match('/^[0-9]+.[0-9][0-9][0-9]$/', $yuan)) {
			$y = StringUtil::toInt(substr($yuan, 0, -4));
			$f = StringUtil::toInt(substr($yuan, -3)) * 10;
		}else if (preg_match('/^[0-9]+.[0-9][0-9][0-9][0-9]$/', $yuan)) {
			$y = StringUtil::toInt(substr($yuan, 0, -5));
			$f = StringUtil::toInt(substr($yuan, -4));
		} else if (!is_string($yuan)) {//非字符串
			BaseError::throw_exception(OtherError::$NOT_STANDARD_YUAN, array ('yuan' => $yuan));
		} else {
			$error = sprintf("(%s)不是正规的元单位", $yuan);
			BaseError::throw_exception(OtherError::$NOT_STANDARD_YUAN, array ('yuan' => $yuan));
		}
		//转化为分
		if (PHP_INT_SIZE === 4) {//32位机器
			if ($y < 214748) {
				return $y * 10000 + $f;
			} else if ($y == 21474836 && $f <= 3647) {
				return $y * 10000 + $f;
			} else {
				BaseError::throw_exception(OtherError::$YUAN_TO_FEN_ERR, array (
					'yuan' => $yuan, 'num' => '2147483647L'));
			}
		} else if (PHP_INT_SIZE === 8) {//64位机器
			if ($y < 922337203685477) {
				return $y * 10000 + $f;
			} else if ($y == 92233720368547758 && $f <= 5807) {
				return $y * 10000 + $f;
			} else {
				BaseError::throw_exception(OtherError::$YUAN_TO_FEN_ERR, array (
					'yuan' => $yuan, 'num' => '9223372036854775807'));
			}
		} else {
			BaseError::throw_exception(OtherError::$UNSUPPORT_COMPUTER);
		}
    }


	/**
	 * 毫转元转(毫为元的万分之一)
	 * @param　$hao 字符串格式数字
	 * @return 元
	 */
	public static function hao2yuan($hao)
	{
		if (is_numeric($hao) && $hao <= 0) {
			return 0;
		}
		if (is_int($hao) || ctype_digit($hao)) {
			$hao = StringUtil::toInt($hao);
			if ($hao % 10000 === 0) {
				return sprintf("%d", $hao / 10000);
			} else {
				return sprintf("%d.%04d", ($hao - $hao % 10000) / 10000, $hao % 10000);
			}

		} else {
			//$error = sprintf("(%s)not fen ",$hao);
			BaseError::throw_exception(OtherError::$HAO_TO_YUAN_ERR, array ('hao' => $hao));
		}
	}


    /**
     * 用户优惠券分表过渡(旧数据查询旧表)
     * @param $disocuntId
     */
    public static function userDiscountShardTransition($disocuntId){
        //5千万以前的查询旧表记录,数据迁移完之后可以直接过渡切换成0
        if($disocuntId >= 50000000){
            return true;
        }
        return false;
    }

    /*
     *去除 字符串中 utf8 mb4 字符 */

    public static function removeUtf8Mb4($content) {

        if(is_string($content)) {
            $convert_content="";
            for($i=0;$i < strlen($content);$i++) {
                $c = substr($content,$i,1);
                if((ord($c) & 0xf8) == 0xf0){
                    // mb4 字符跳过
                    $i+=3;
                }
                else {
                    $convert_content.=$c;
                }

            }

            return $convert_content;

        }

        return $content;
    }


	/*提钱乐短贷费率计算 返回单位元
	*
	*/
	public static function getWorkRandomFee($amount, $interest_date, $repay_date, $ratio){
        //最低收取的服务费金额
		$min_ratio = 100;//最低收取1%利息
		$min_random_fee = StringUtil::yuan2fen(StringUtil::getRandomFee($amount, $interest_date, StringUtil::dayAdd($interest_date, 1), $min_ratio));

        //根据费率计算出的服务费金额
		$random_fee = StringUtil::yuan2fen(StringUtil::getRandomFee($amount, $interest_date, $repay_date, $ratio));

		return StringUtil::fen2yuan(max($min_random_fee, $random_fee));
	}


    /*
     * 数组的所有key值驼峰转成下划线
     */
    public static function Hump2Underline($params){
        foreach($params as $key=>$value){
            if(is_array($value)){
                $value = self::Hump2Underline($value);
            }
            unset($params[$key]);
            $key = self::convertHump($key);
            $params[$key] = $value;
        }
        return $params;
    }

    /*
     * 数组的所有key值下划线转成驼峰
     */
    public static function Underline2Hump($params){
        foreach($params as $key=>$value){
            if(is_array($value)){
                $value = self::Underline2Hump($value);
            }
            unset($params[$key]);
            $key = self::convertUnderline($key,false);
            $params[$key] = $value;
        }
        return $params;
    }

    /*
     * 字符串下划线转成驼峰
     */
    public static function convertUnderline($str , $ucfirst = true){
        $str = explode('_' , $str);
        foreach($str as $key=>$val)
            $str[$key] = ucfirst($val);

        if(!$ucfirst)
            $str[0] = strtolower($str[0]);

        return implode('' , $str);
    }

    /*
     * 字符串驼峰转下划线
     */
    public static function convertHump($param_name) {
        // AbcDef => abc_def or
        $param_name = strval($param_name);

        $ret = array ();
        $len = strlen($param_name);
        $index = 0;
        while ($index < $len) {
            //第一个大写
            $str = '';
            if ($param_name[$index] >= 'A' && $param_name[$index] <= 'Z') {
                $str .= chr(ord($param_name[$index]) + 32);
            }else{
                $str .= $param_name[$index];
            }

            $index++;
            while ($index < $len && ($param_name[$index] >= 'a' && $param_name[$index] <= 'z' ||
                    $param_name[$index] >= '0' && $param_name[$index] <= '9' || $param_name[$index] == '_')) {
                $str .= $param_name[$index++];
            }
            $ret[] = $str;
        }
//        var_dump('$param_name:'.$param_name.'   $ret:'.StringUtil::array2string($ret).'  index:'.$param_name[0].'  len:'.strlen($param_name));
        $ret_str = implode('_', $ret);

        return $ret_str;
    }

    /**
     * @param $vStr
     * @return bool
     * Date: ${DATE} ${TIME}
     * Comment:验证身份证号
     */
    public static function isCreditNo($vStr)
    {
        $vCity = array(
            '11','12','13','14','15','21','22',
            '23','31','32','33','34','35','36',
            '37','41','42','43','44','45','46',
            '50','51','52','53','54','61','62',
            '63','64','65','71','81','82','91'
        );
        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;
        if (!in_array(substr($vStr, 0, 2), $vCity)) return false;
        $vStr = preg_replace('/[xX]$/i', 'a', $vStr);
        $vLength = strlen($vStr);
        if ($vLength == 18)
        {
            $vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
        } else {
            $vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
        }
        if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
        if ($vLength == 18)
        {
            $vSum = 0;
            for ($i = 17 ; $i >= 0 ; $i--)
            {
                $vSubStr = substr($vStr, 17 - $i, 1);
                $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
            }
            if($vSum % 11 != 1) return false;
        }
        return true;
    }

    /**
     * 防注入和XSS攻击通用过滤 
     * @param $arr
     * @return array|mixed|string
     */
    public static function str_safe_filter (&$arr)
    {
        $ra=Array('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/','/script/','/javascript/','/vbscript/','/expression/','/applet/','/meta/','/xml/','/blink/','/link/','/style/','/embed/','/object/','/frame/','/layer/','/title/','/bgsound/','/base/','/onload/','/onunload/','/onchange/','/onsubmit/','/onreset/','/onselect/','/onblur/','/onfocus/','/onabort/','/onkeydown/','/onkeypress/','/onkeyup/','/onclick/','/ondblclick/','/onmousedown/','/onmousemove/','/onmouseout/','/onmouseover/','/onmouseup/','/onunload/');
        if (is_array($arr))
        {
            foreach ($arr as $key => $value)
            {
                if (!is_array($value))
                {
                    if (!get_magic_quotes_gpc())             //不对magic_quotes_gpc转义过的字符使用addslashes(),避免双重转义。
                    {
                        $value  = addslashes($value);           //给单引号（'）、双引号（"）、反斜线（\）与 NUL（NULL 字符）加上反斜线转义
                    }
                    $value = trim($value);   //去掉首尾空格
                    $value = preg_replace($ra,'',$value);     //删除非打印字符，粗暴式过滤xss可疑字符串
                    $arr[$key] = htmlentities(strip_tags($value),ENT_COMPAT,'UTF-8'); //去除 HTML 和 PHP 标记并转换为 HTML 实体
                }
                else
                {
                    self::safeFilter($arr[$key]);
                }
            }
        }else{
            if (!get_magic_quotes_gpc())             //不对magic_quotes_gpc转义过的字符使用addslashes(),避免双重转义。
            {
                $arr  = addslashes($arr);           //给单引号（'）、双引号（"）、反斜线（\）与 NUL（NULL 字符）加上反斜线转义
            }
            $arr = trim($arr);  //去掉首尾空格
            $arr = preg_replace($ra,'',$arr);     //删除非打印字符，粗暴式过滤xss可疑字符串
            $arr = htmlentities(strip_tags($arr),ENT_COMPAT,'UTF-8'); //去除 HTML 和 PHP 标记并转换为 HTML 实体
        }
        return $arr;
    }
}