<?php

/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/7/28
 * Time: 15:08
 */
class JmfUtil
{
    static $m_env = null;
    const ENV_PATH = '/../config/';
    const MACHINE_CONFIG_FILE = '/data/product/machine_config/machine_config.properties';

    /**
     * 脚本初始化jmf_consumer框架
     * $APP_NAME         程序名称
     * $APP_SRC_PATH     程序路径
     */
    public static function RequireJmfApiInit($APP_NAME, $APP_SRC_PATH)
    {
        $jmf_path = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'jyb_microservice_framework' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'JmfApi.php';

        if (!file_exists($jmf_path)) {
            $jmf_path = '/data/product/jyb_microservice_framework/api/JmfApi.php';
        }
        require_once($jmf_path);

        JmfApi::configure($APP_NAME, $APP_SRC_PATH);
    }

    public static function envInit()
    {
        $conf = self::getEnvConfig();
        foreach ($conf as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
    public static function loadConfigFile($configFile)
    {
        $config = array();
        if (file_exists($configFile))
        {
            $config = parse_ini_file($configFile, true);
        }
        return $config;
    }

    public static function getMicroServiceConfig()
    {
        $path = APPLICATION_PROJECT_PATH.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.env';
        if (file_exists($path))
        {
            $config = parse_ini_file($path, TRUE);
            $env = self::getMachineEnv();
            if (isset($config[$env]))
            {
                return $config[$env];
            }
        }
        return array();
    }

    public static function getMicroEnvConfig($key)
    {
        $config = self::getMicroServiceConfig();
        if (empty($config[$key]))
        {
            throw new \Exception('micro_mq config exception', 5000020009);
        }
        return $config[$key];
    }

    public static function getMachineEnv()
    {
        if (empty(self::$m_env))
        {
            if (file_exists(self::MACHINE_CONFIG_FILE))
            {
                $machineConfig = self::loadConfigFile(self::MACHINE_CONFIG_FILE);
                if (isset($machineConfig['env']))
                {
                    self::$m_env = $machineConfig['env'];
                }
            }
            else
            {
                self::$m_env = 'dev';
            }
        }
        return self::$m_env;
    }

    public static function getEnvConfig($envKey = null, $default = null)
    {
        $conf = self::loadConf();
        if ($envKey) {
            return isset($conf[$envKey])?$conf[$envKey]:$default;
        } else {
            return $conf;
        }
    }


    public static function loadConf()
    {
        $confPath = dirname(__FILE__) . self::ENV_PATH;
        $devPath = $confPath . 'env.default';
        $conf = parse_ini_file($devPath);
        $envPath = $confPath . '.env';
        if (file_exists($envPath)) {
            $envConf = parse_ini_file($envPath);
            $conf = array_merge($conf, $envConf);
        }
        foreach ($conf as $key => $value) {
            switch ($value) {
                case 'true':
                    $conf[$key] = true;
                    break;
                case 'null':
                    $conf[$key] = null;
                    break;
                case 'empty':
                    $conf[$key] = '';
                    break;
            }
        }
        return $conf;
    }

    public static function call_Jmf_consumer($server, $request, $timeout = 3, $cash_timeout=0)
    {
        $proxy = JmfApi::newProxy($server,$timeout,$cash_timeout);
        return $proxy->invoke($request);
    }

    /**
     * 正则校验是否手机号
     * @param  [type]  $phone [description]
     * @return boolean        [description]
     */
    public static function isPhoneNum($phone)
    {
        if(strlen($phone) > 11)
            return false;
        if(preg_match("/^1[34578]{1}\d{9}$/", $phone))
            return true;
        return false;
    }

    /**
     * list集合转换为map
     * @param $list
     * @param $hash_key
     * @return array
     */
    public static function list_to_map($list , $hash_key){
        if(empty($list)){
            return array();
        }
        $data = array();
        foreach($list as $row){
            $_tmp_key = "";
            if(is_array($hash_key)){
                foreach($hash_key as $key){
                    $_tmp_key .= $row[$key]."_";
                }
                $_tmp_key = preg_replace("@_$@" , "" , $_tmp_key);
            }else{
                $_tmp_key = $row[$hash_key];
            }
            $data[$_tmp_key] = $row;
        }
        return $data;
    }
    /**
     * 获取分页信息
     * @param $page_index
     * @param $page_size
     * @return array
     */
    public static function getPageInfo($page_index , $page_size){
        if ($page_size > 20 || $page_size < 1) {
            $page_size = 20;
        }
        if ($page_index < 1) {
            $page_index = 1;
        }
        $offset = $page_size * ($page_index - 1);
        return array($offset , $page_size);
    }

    public static function array_column($input, $columnKey, $indexKey = NULL)
    {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? TRUE : FALSE;
        $indexKeyIsNull = (is_null($indexKey)) ? TRUE : FALSE;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? TRUE : FALSE;
        $result = array();

        foreach ((array)$input AS $key => $row)
        {
            if ($columnKeyIsNumber)
            {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : NULL;
            }
            else
            {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : NULL;
            }
            if ( ! $indexKeyIsNull)
            {
                if ($indexKeyIsNumber)
                {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && ! empty($key)) ? current($key) : NULL;
                    $key = is_null($key) ? 0 : $key;
                }
                else
                {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }

            $result[$key] = $tmp;
        }

        return $result;
    }

    /**
     * 该方法慎用，主要考虑到从mod框架set 在微服务里get的情况，因为mod框架set值的时候会按一些规则存，get也要按它的规则取
     * @param $value
     * @param bool $row
     * @return mixed
     */
    public static function mod_redis_get($value , $row = false)
    {
        if($row === true)
        {
            return $value;
        }
        $data=unserialize($value);
        if(!is_array($data))
            return $value;
        return $data[0];
    }

    /**
     * 从10进制数字中获取其二进制中某一位的值
     * @param unknown $int_value  10进制数字
     * @param unknown $bit_index  二进制位置，从低位到高位算起，第一位为 1 ，以此类推
     * @return number 返回 0 或者 1
     */
    public static function get_bit_from_int($int_value, $bit_index) {
        $bit_str = decbin ( $int_value );
        $bit_len = strlen ( $bit_str );

        if ($bit_len < $bit_index) {
            return 0;
        }

        $bit = substr ( $bit_str, $bit_len - $bit_index, 1 );
        if ($bit == 0) {
            return 0;
        }

        return 1;
    }

    /**
     * 设置 10 进制转化为 2 进制后某一位的值，并返回对应的 10 进制值
     * @param unknown $int_value  10 进制数字
     * @param unknown $bit_value  要设置的二进制值：0或者1
     * @param unknown $bit_index  二进制位置，从低位到高位算起，第一位为 1 ，以此类推
     * @return unknown|number     返回设置后的10进制值
     */
    public static function set_bit_to_int($int_value, $bit_value,$bit_index) {
        $bit_str = decbin ( $int_value );

        if ($bit_index < 1) {
            return $int_value;
        }

        // 如果设置指定位为 1
        if ($bit_value == 1) {
            $tmp = 1;
            if ($bit_index > 1) {
                $tmp = $tmp << ($bit_index - 1);
            }

            return ($int_value | $tmp);
        }

        // 如果设置指定位为 0
        $i = 1;
        $tmp = '';
        while ( $i <= strlen ( $bit_str ) ) {
            if ($i == $bit_index) {
                $tmp = '0' . $tmp;
            } else {
                $tmp = '1' . $tmp;
            }
            $i ++;
        }

        $tmp = bindec ( $tmp );
        return ($int_value & $tmp);
    }

    /**
     * 等额本息
     * @param $loan_month
     * @param $loan_total_amount
     * @param $loan_rate 名义月利率
     * @return array
     *  @param stages_list 分期列表明细
     *  @param interest_total 总利息
     */
    public static function debx($loan_month, $loan_total_amount, $loan_rate)
    {
        $lxTotal = 0; //总利息
        $stages_list = array();

        if($loan_rate == 0)
        {
            $q_total = round($loan_total_amount / $loan_month , 0);
            $_total = 0;
            for ($i = 0;$i<$loan_month ;$i++)
            {
                if($i < $loan_month - 1)
                {
                    $_total += $q_total;
                }else{
                    $q_total = $loan_total_amount - $_total;
                }

                $stages_list[] = array(
                    'period_seq' => $i + 1, //第几期
                    'capital'    => $q_total,    //本金
                    'interest'   => 0,    //利息
                    'em_total'   => $q_total //总额
                );
            }
        }else{
            $copy_loan_total_amount = $loan_total_amount;
            //根据名义月利率得出实际月利率
            $loan_rate = self::calcRealMonthRate($loan_rate , $loan_month);
            $em_total = $loan_total_amount * $loan_rate * pow(1 + $loan_rate, $loan_month) / (pow(1 + $loan_rate, $loan_month) - 1); //每月还款金额

            $bj_total = 0;
            for ($i = 0; $i < $loan_month; $i++) {
                $lx = round($loan_total_amount * $loan_rate , 0);   //每月还款利息
                $em = round($em_total - $lx , 0);  //每月还款本金

                if($i < $loan_month - 1)
                {
                    $bj_total += $em;
                }else{
                    $em = $copy_loan_total_amount - $bj_total;
                    $lx = $em_total - $em;
                    //$em_total = $em + $lx;
                }

                $stages_list[] = array(
                    'period_seq' => $i + 1, //第几期
                    'capital'    => round($em , 0),    //本金
                    'interest'   => round($lx , 0),    //利息
                    'em_total'   => round($em_total, 0) //总额
                );
                $loan_total_amount = $loan_total_amount - $em;
                $lxTotal = $lxTotal + $lx;
            }
        }
        return array(
            'stages_list'    => $stages_list,
            'interest_total' => round($lxTotal,0)    //总利息
        );
    }

    /**
     * 等额本金
     * @param $loan_month
     * @param $loan_total_amount
     * @param $loan_rate
     * @return array
     *  @param stages_list 分期列表明细
     *  @param interest_total 总利息
     */
    public static function debj($loan_month, $loan_total_amount, $loan_rate)
    {
        $copy_loan_total_amount = $loan_total_amount;
        //根据名义月利率得出实际月利率
        $loan_rate = self::calcRealMonthRate($loan_rate , $loan_month);
        $em = round($loan_total_amount / $loan_month , 0); //每个月还款本金
        $lxTotal = 0; //总利息
        $stages_list = array();

        $bj_total = 0;

        for ($i = 0; $i < $loan_month; $i++) {
            $lx = round($loan_total_amount * $loan_rate,0); //每月还款利息

            if($i < $loan_month - 1)
            {
                $bj_total += $em;
            }else{
                $em = $copy_loan_total_amount - $bj_total;
            }

            $stages_list[] = array(
                'period_seq' => $i + 1, //第几期
                'capital'    => round($em,0),    //本金
                'interest'   => round($lx,0),    //利息
                'em_total'   => round($em + $lx , 0) //总额
            );

            $loan_total_amount -= $em;
            $lxTotal = $lxTotal + $lx;
        }
        return array(
            'stages_list'    => $stages_list,
            'interest_total' => round($lxTotal,0)    //总利息
        );
    }

    /**
     * 计算实际月利率
     * @param $x 名义月利率
     * @param $m 分几期
     * @return string （保留6位小数）
     */
    public static function calcRealMonthRate($x , $m){
        if($x == 0)
        {
            return 0;
        }
        $big = $x;
        $pm = function ($y , $m)
        {
            return ($y*pow((1+$y) , $m))/(pow((1+$y) , $m)-1)-1.0/$m;
        };

        while(true)
        {
            $lval = $pm($big , $m);
            if($lval > $x)
            {
                break;
            }else{
                $big += 0.001;
            }
        }
        $small = $big - 0.001;
        while(true)
        {
            $mid = ($small + $big) / 2;
            $lval = $pm($mid , $m);
            if(abs($lval - $x) < 0.00000001 || $small > $big || $small == $big)
            {
                return sprintf("%.6f" , ($mid * 100) / 100) ;
            }else if($lval > $x)
            {
                $big = $mid;
            }else{
                $small = $mid;
            }
        }
    }
}
