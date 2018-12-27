<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/4
 * Time: 16:43
 */

defineConstant();

//设置db默认配置
think\Db::setConfig(getDbConfig());

/**
 * 获取DB配置
 * @param string $name
 * @return array
 */
function getDbConfig($name = 'BASE')
{
    $name = 'ENV_DB_' . strtoupper($name);
    if (defined($name.'_TYPE') && defined($name.'_HOST')
        && defined($name.'_PORT') && defined($name.'_DATABASE')
        && defined($name.'_USERNAME') && defined($name.'_PASSWORD')) {
        $type = constant($name.'_TYPE');
        $host = constant($name.'_HOST');
        $port = constant($name.'_PORT');
        $database = constant($name.'_DATABASE');
        $username = constant($name.'_USERNAME');
        $password = constant($name.'_PASSWORD');
    } else {
        //配置中包含特殊字符，解析会有问题
        $conf = JmfUtil::getEnvConfig($name);
        $url = new UrlUtil($conf);
        $type = $url->getProtocol();
        $host = $url->getHost();
        $port = $url->getPort();
        $database = $url->getParameter('dbname');
        $username = $url->getUsername();
        $password = $url->getPassword();
    }

    $debug = defined('MYSQL_LOG') && constant('MYSQL_LOG') ? true : false;
    $params = $type == 'mysql' && PHP_SAPI == 'cli' ? [PDO::ATTR_PERSISTENT => true] : [];

    return [
        // 数据库类型
        'type'             => $type,
        // 服务器地址
        'hostname'        => $host,
        // 数据库名
        'database'        => $database,
        // 用户名
        'username'        => $username,
        // 密码
        'password'        => $password,
        // 端口
        'hostport'        => $port,
        // 是否需要断线重连
        'break_reconnect' => true,
        // 数据库调试模式
        'debug'            => $debug,
        'params'           => $params,
    ];
}

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string  $name 字符串
 * @param integer $type 转换类型
 * @param bool    $ucfirst 首字母是否大写（驼峰规则）
 * @return string
 */
function parseName($name, $type = 0, $ucfirst = true)
{
    if ($type) {
        $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $name);
        return $ucfirst ? ucfirst($name) : lcfirst($name);
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

/**
 * 加密验证
 * @param $params
 * @param $secretKey
 * @return string
 */
function secretGet($params , $secretKey = 'aabb')
{
    ksort($params);
    foreach($params as $key => $value) {
        if (in_array($key, ['secret', 'sessionToken'])) {
            continue;
        }
        $strs[] = $key . '=' . $value;
    }
    $str = implode('&' , $strs).$secretKey;
    return sha1($str);
}

/**
 * @wiki http://oa.jyblife.com/services
 * @demo $res = soapCall('getWorkflowRequest', array('in0' => 1, 'in1' => 10, 'in2' => 100));print_r($res);
 * @param $method
 * @param $params
 * @param string $wsdl
 * @param array $options
 * @return bool|mixed
 */
function soapCall($method, $params, $wsdl = 'OA_WEBSERVICE_WORKFLOW', array $options = ['encoding'=>'UTF-8', 'features' => SOAP_SINGLE_ELEMENT_ARRAYS])
{
    try {
        $wsdl = constant('ENV_'.$wsdl);
        $client = new SoapClient($wsdl, $options);
        //print_r($client->__getFunctions());
        //print_r($client->__getTypes());
        return $client->$method($params);
        //return $res['out'] ?? $res;
    } catch (SoapFault $e) {
        CommonLog::instance()->getDefaultLogger()->warn('请求WebService失败：'.$e->getMessage());
        return false;
    }
}

function packRet($code, $msg = 'success', $data = [])
{
    return array(PARAM_CODE => $code, PARAM_MSG => $msg, PARAM_DATA => $data);
}

/**
 * 获取和设置配置参数
 * @param string|array  $name 参数名
 * @param mixed         $value 参数值
 * @param string        $range 作用域
 * @return mixed
 */
function config($name = '', $value = null, $range = '')
{
    if (is_null($value) && is_string($name)) {
        return 0 === strpos($name, '?') ? money\Config::has(substr($name, 1), $range) : money\Config::get($name, $range);
    } else {
        return money\Config::set($name, $value, $range);
    }
}

/**
 * 获取外部系统详情页面URL
 * @param string $systemFlag
 * @param string $outOrderNum
 * @return string
 */
function getExternalDetailUrl($systemFlag, $outOrderNum)
{
    static $list = [];
    if (!isset($list[$systemFlag])) {
        $mInterfacePriv = new \money\model\InterfacePriv();
        $res = $mInterfacePriv->getOne(['system_flag' => $systemFlag], 'detail_url');
        $list[$systemFlag] = $res['detail_url'] ?? null;
    }
    $url = $list[$systemFlag] ?? '';
    //OA系统的外部单号可能包含版本号
    if (strtolower($systemFlag) == 'oa' && strpos($outOrderNum, '_') != false) {
        $outOrderNum = strstr($outOrderNum, '_', true);
    }
    return str_replace('{$outOrderNum}', $outOrderNum, $url);
}

/**
 * 定义配置文件里面的常量
 */
function defineConstant()
{
    $config = require_once dirname(__DIR__).'/config/define.php';
    foreach ($config as $k => $v) {
        if (!defined($k)) {
            define($k, $v);
        }
    }
}
