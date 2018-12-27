<?php
/**
 * Created by PhpStorm.
 * User: xupengpeng
 * Date: 2018/8/17
 * Time: 8:32
 */

namespace money\logic;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use money\base\RSAUtil;
use money\model\InterfacePriv;
use money\model\NotifyLog;

class NotifyLogic extends AbstractLogic
{
    /**
     * @var NotifyLog
     */
    protected $mNotifyLog;

    public function __construct()
    {
        $this->mNotifyLog = new NotifyLog();
    }

    /**
     * 推送通知
     * @param array $config
     * @param mixed $data
     * @param bool $force
     * @return bool
     */
    protected function push(array $config, $data, $force = false)
    {
        $requestId = $data['request_id'];
        $timestamp = $data['timestamp'];
        if (empty($requestId) || empty($timestamp)) {
            \CommonLog::instance()->getDefaultLogger()->info("通知入参有误");
            return false;
        }
        $service = $config['service_name'];
        $method = 'call'.ucfirst($config['service_type']).'Service';
        if (empty($service) || !is_callable([$this, $method])) {
            \CommonLog::instance()->getDefaultLogger()->info("服务【{$service}】配置有误，不支持此服务类型");
            return false;
        }
        //校验通知日志
        if (!$force && !$this->validateNotifyLog($config, $requestId, $timestamp)) {
            \CommonLog::instance()->getDefaultLogger()->info("已经成功通知过或通知次数超过限制");
            return true;
        }
        $options = !empty($config['service_options']) ? json_decode($config['service_options'], true) : [];
        //数据签名
        if (!empty($options['is_data_sign'])) {
            $priv = $this->getInterfacePriv($config['system_flag']);
            if (empty($priv['notice_key'])) {
                \CommonLog::instance()->getDefaultLogger()->info("服务【{$service}】配置有误，notice_key不能为空");
                return false;
            }
            $data['secret'] = secretGet($data, $priv['notice_key']);
        }
        //数据加密
        if (!empty($options['is_data_encrypt'])) {
            $priv = $this->getInterfacePriv($config['system_flag']);
            if (empty($priv['public_key'])) {
                \CommonLog::instance()->getDefaultLogger()->info("服务【{$service}】配置有误，public_key不能为空");
                return false;
            }
            $u = new RSAUtil();
            $data = $u->publicEncrypt(json_encode($data), $priv['public_key']);
        }
        //请求服务
        $res = $this->$method($service, $data, $options);
        $msg = "请求服务【{$service}】，入参：".json_encode($data, JSON_UNESCAPED_UNICODE)."|出参：".json_encode($res, JSON_UNESCAPED_UNICODE);
        \CommonLog::instance()->getDefaultLogger()->info($msg);
        //保存通知日志
        $this->saveNotifyLog($config['id'], $requestId, $timestamp, $res);
        return $res['code'] == 0 ? true : false;
    }

    /**
     * 校验通知日志
     * @param array $config
     * @param string $requestId
     * @param int $timestamp
     * @return bool
     */
    protected function validateNotifyLog(array $config, $requestId, $timestamp)
    {
        $configId = $config['id'];
        //判断最新的通知记录是否成功
        $log = $this->mNotifyLog->getOne(['config_id' => $configId, 'request_id' => $requestId], 'timestamp, resp_code', 'id desc');
        if (empty($log)) {
            return true;
        } elseif ($log['timestamp'] == $timestamp && $log['resp_code'] == '0') {
            return false;
        }
        //判断是否超过通知次数限制
        if ($config['notify_times'] > 0 && $this->mNotifyLog->getCount(['config_id' => $configId, 'request_id' => $requestId, 'timestamp' => $timestamp]) >= $config['notify_times']) {
            return false;
        }
        return true;
    }

    /**
     * 获取外部系统访问权限数据
     * @param string $systemFlag
     * @return mixed
     */
    protected function getInterfacePriv($systemFlag)
    {
        static $priv = [];
        if (empty($priv[$systemFlag])) {
            $mInterfacePriv = new InterfacePriv();
            $priv[$systemFlag] = $mInterfacePriv->getOne(['system_flag' => $systemFlag]);
        }
        return $priv[$systemFlag];
    }

    /**
     * 调用微服务
     * @param string $service
     * @param mixed $data
     * @param array $options
     * @return mixed
     */
    protected function callJmfService($service, $data, array $options = [])
    {
        $timeout = $options['timeout'] ?? 5;
        return \JmfUtil::call_Jmf_consumer($service, $data, $timeout);
    }

    /**
     * 调用cmd接口
     * @param string $service
     * @param mixed $data
     * @param array $options
     * @return array
     */
    protected function callCmdService($service, $data, array $options = [])
    {
        if (empty($options['base_uri'])) {
            return packRet(__LINE__, "服务【{$service}】配置有误，base_uri不能为空");
        }
        $data = [
            'cmd' => $service,
            'data' => $data,
        ];
        return $this->callRestService($service, $data, $options);
    }

    /**
     * 调用restful接口
     * @link http://docs.guzzlephp.org/en/stable/request-options.html
     * @param string $service
     * @param mixed $data
     * @param array $options
     * @return array
     */
    protected function callRestService($service, $data, array $options = [])
    {
        try {
            $config = [
                'base_uri' => $options['base_uri'] ?? null,
                'headers' => $options['headers'] ?? null,
                'timeout' => $options['timeout'] ?? 5,
                'verify' => false,
            ];
            $client = new Client($config);
            $method = strtoupper($options['method']) ?? 'POST';
            $bodyType = $options['body_type'] ?? 'json';
            $response = $client->request($method, $service, [$bodyType => $data]);
            $contents = $response->getBody()->getContents();
            $json = json_decode($contents, true);
            return packRet($json['code'] ?? -1, $json['msg'] ?? '未知错误', $json['data'] ?? null);
        } catch (GuzzleException $e) {
            return packRet($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 保存通知日志
     * @param int $configId
     * @param string $requestId
     * @param string $timestamp
     * @param array $response
     */
    protected function saveNotifyLog($configId, $requestId, $timestamp, $response)
    {
        $data = [
            'config_id' => $configId,
            'request_id' => $requestId,
            'timestamp' => $timestamp,
            'resp_code' => $response['code'],
            'resp_msg' => $response['msg'],
            'create_time' => date('Y-m-d H:i:s'),
        ];
        $this->mNotifyLog->insert($data);
    }
}
