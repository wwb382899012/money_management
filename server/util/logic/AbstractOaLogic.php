<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/6
 * Time: 9:34
 */

namespace money\logic;

use function GuzzleHttp\Promise\exception_for;
use money\model\InterfacePriv;
use money\model\LoanOrder;
use money\model\LoanTransfer;
use money\model\OaLoanBusiness;
use money\model\OaLoanNotifyLog;
use money\model\OaLoanRequestLog;
use money\model\OaModel;
use money\model\OaPayBusiness;
use money\model\OaPayRequestLog;
use money\model\OaPayNotifyLog;
use money\model\OaRepayBusiness;
use money\model\OaRepayNotifyLog;
use money\model\OaRepayRequestLog;
use money\model\PayOrder;
use money\model\PayTransfer;
use money\model\RepayOrder;
use money\model\Repay;
use money\model\SysFile;

abstract class AbstractOaLogic extends AbstractLogic
{
    /**
     * @var SysFile
     */
    protected $mSysFile;
    /**
     * @var PayOrder|LoanOrder|RepayOrder
     */
    protected $mOrder;
    /**
     * @var PayTransfer|LoanTransfer|Repay
     */
    protected $mTransfer;
    /**
     * @var OaPayBusiness|OaLoanBusiness|OaRepayBusiness
     */
    protected $mOaBusiness;
    /**
     * @var OaPayRequestLog|OaLoanRequestLog|OaRepayRequestLog
     */
    protected $mOaRequestLog;
    /**
     * @var OaPayNotifyLog|OaLoanNotifyLog|OaRepayNotifyLog
     */
    protected $mOaNotifyLog;
    /**
     * @var OaModel
     */
    protected $mOaModel;

    //OA调用指令的服务名称
    protected $oaCallService = '';

    // 付款业务映射表，workflow_requestbase表的字段workflowid，currentnodeid与formtable_maing表的关系
    protected $oaCallConfig = [];
    protected $oaNotifyConfig = [];

    const REDIS_KEY_ORDER_OA_CALL_LOCK = '';
    const REDIS_KEY_ORDER_OA_NOTIFY_LOCK = '';

    public function __construct()
    {
        $this->setOaCallConfig();
        $this->mOaModel->setOaCallConfig($this->oaCallConfig);
        $this->mOaModel->setOaNotifyConfig($this->oaNotifyConfig);
    }

    public function batchTimeOutWorkflowRequestBase($conditions)
    {
        $page = 1;
        $pageSize = 100;
        $result = [];
        !isset($conditions['timeout']) && $conditions['timeout'] = time() - 3600;//默认1小时
        $whereRaw = [];
        $tmp = [];
        foreach ($this->oaCallConfig as $config) {
            if (!empty($conditions['system_flag']) && !in_array($config['system_flag'], (array)$conditions['system_flag'])) {
                continue;
            }
            $tmp[] = "[workflowid] = '{$config['workflowid']}' AND [currentnodeid] = '{$config['currentnodeid']}'";
        }
        !empty($tmp) && $whereRaw[] = '('.implode(' OR ', $tmp).')';
        $where = [
            //过滤已完结的类型
            ['currentnodetype', '<>', 3],
        ];
        if (!empty($conditions['workflowid'])) {
            $where[] = ['workflowid', 'IN', (array)$conditions['workflowid']];
        }
        if (!empty($conditions['requestid'])) {
            $where[] = ['requestid', 'IN', (array)$conditions['requestid']];
        }
        if (!empty($conditions['timeout'])) {
            $where[] = ['lastoperatedate', '<=', date('Y-m-d', $conditions['timeout'])];
            //日期和时间两个字段，需拼接后进行比较
            $whereRaw[] = "[lastoperatedate] + ' ' + [lastoperatetime] <= '".date('Y-m-d H:i:s', $conditions['timeout'])."'";
        }

        $whereRaw = implode(' AND ', $whereRaw);
        $count = $this->mOaModel->getWorkflowRequestBaseCount($where, $whereRaw);
        $totalPage = ceil($count / $pageSize);
        while ($page <= $totalPage) {
            $list = $this->mOaModel->getWorkflowRequestBaseList($where, $whereRaw, $page, $pageSize);
            foreach ($list as $item) {
                try {
                    $result[$item['requestid']] = $this->_rejectOrder($item);
                } catch (\Exception $e) {
                    $msg = sprintf("requestId:%d, exception catch, message: %s, file: %s, line: %d", $item['requestid'], $e->getMessage(), $e->getFile(), $e->getLine());
                    echo $msg . PHP_EOL;
                    \CommonLog::instance()->getDefaultLogger()->error($msg);
                }
            }
            $page++;
        }
        return $result;
    }

    /**
     * 处理OA系统调用资金系统超时未处理的流程
     * @param $workflowRequestBaseData
     * @return bool
     */
    protected function _rejectOrder($workflowRequestBaseData)
    {
        // 获取最后一笔调用记录，取得失败原因
        $data = $this->getPayOrderParams($workflowRequestBaseData);
        $arr = explode('_', $data['out_order_num']);
        $requestId = $arr[0];

        $where = [
            ['system_flag', '=', $data['system_flag']],
            ['request_id', '=', $requestId],
            ['is_delete', '=', 1]
        ];

        $notifyLog = $this->mOaRequestLog->getOne($where, 'request_id, version, timestamp, order_num, resp_code,resp_msg', 'version desc, create_time desc');
        if (empty($notifyLog)) {
            echo "没有查到请求记录, requestId: " . $requestId . PHP_EOL;
            return true;
        }

        if (!in_array($notifyLog['resp_code'], [0, -1])) {
            //通知oa驳回
            $respMsgMap = [
                'collect_bank_name' => '收款银行名称',
                'collect_bank_account' => '收款银行账户',
                'collect_bank_address' => '收款银行地址',
                'collect_bank' => '收款银行字典编码',
                'collect_city_name' => '收款银行城市',
                'collect_province_name' => '收款银行省份',
                'real_pay_type' => '支付方式错误',
            ];
            $dealRemark = str_replace(array_keys($respMsgMap), array_values($respMsgMap), $notifyLog['resp_msg']);
            $notifyData = [
                'request_id' => $requestId,
                'update_time' => date('Y-m-d H:i:s', time()),
                'audit_log' => ['deal_remark' => $dealRemark],
            ];
            $config = $this->mOaModel->getOaNotifyConfig('reject');
            if (!empty($config)) {
                $notifyData = array_merge($notifyData, $config['fields']);
                //OA系统处理付款单通知
                $result = $this->mOaModel->handleNotify($notifyData);
                if (!isset($result['code']) || $result['code'] != '0') {
                    $msg = "通知OA系统失败，入参：" . json_encode($notifyData, JSON_UNESCAPED_UNICODE) . " | 出参：" . json_encode($result, JSON_UNESCAPED_UNICODE);
                    \CommonLog::instance()->getDefaultLogger()->warn($msg);
                    return false;
                } else {
                    $msg = "通知OA系统成功，入参：" . json_encode($notifyData, JSON_UNESCAPED_UNICODE) . " | 出参：" . json_encode($result, JSON_UNESCAPED_UNICODE);
                    \CommonLog::instance()->getDefaultLogger()->info($msg);
                    return true;
                }
            } else {
                \CommonLog::instance()->getDefaultLogger()->warn("获取oa_notify_config失败，type: reject");
                return false;
            }
        }
        return true;
    }

    /**
     * 批量调用付款指令
     * @param array $conditions
     * @return  array
     */
    public function batchCallPayOrder(array $conditions)
    {
        $page = 1;
        $pageSize = 100;
        $result = [];
        !isset($conditions['begin_time']) && $conditions['begin_time'] = time() - 3600;//默认1小时
        $whereRaw = [];
        $tmp = [];
        foreach ($this->oaCallConfig as $config) {
            if (!empty($conditions['system_flag']) && !in_array($config['system_flag'], (array)$conditions['system_flag'])) {
                continue;
            }
            $tmp[] = "[workflowid] = '{$config['workflowid']}' AND [currentnodeid] = '{$config['currentnodeid']}'";
        }
        !empty($tmp) && $whereRaw[] = '('.implode(' OR ', $tmp).')';
        $where = [
            ['currentnodetype', '<>', 3],
        ];
        if (!empty($conditions['workflowid'])) {
            $where[] = ['workflowid', 'IN', (array)$conditions['workflowid']];
        }
        if (!empty($conditions['requestid'])) {
            $where[] = ['requestid', 'IN', (array)$conditions['requestid']];
            unset($conditions['begin_time'], $conditions['end_time']);
        }
        if (!empty($conditions['begin_time'])) {
            $where[] = ['lastoperatedate', '>=', date('Y-m-d', $conditions['begin_time'])];
            //日期和时间两个字段，需拼接后进行比较
            $whereRaw[] = "[lastoperatedate] + ' ' + [lastoperatetime] >= '".date('Y-m-d H:i:s', $conditions['begin_time'])."'";
        }
        if (!empty($conditions['end_time'])) {
            $where[] = ['lastoperatedate', '<=', date('Y-m-d', $conditions['end_time'])];
            $whereRaw[] = "[lastoperatedate] + ' ' + [lastoperatetime] <= '".date('Y-m-d H:i:s', $conditions['end_time'])."'";
        }
        $whereRaw = implode(' AND ', $whereRaw);
        $count = $this->mOaModel->getWorkflowRequestBaseCount($where, $whereRaw);
        $totalPage = ceil($count / $pageSize);
        while ($page <= $totalPage) {
            $list = $this->mOaModel->getWorkflowRequestBaseList($where, $whereRaw, $page, $pageSize);
            foreach ($list as $item) {
                $result[$item['requestid']] = $this->callPayOrder($item);
            }
            $page++;
        }
        return $result;
    }

    /**
     * 批量通知OA付款单状态
     * @param array $conditions
     * @return  array
     */
    public function batchNotifyOa(array $conditions)
    {
        $page = 1;
        $pageSize = 100;
        $result = [];
        $systemFlags = array_column($this->oaCallConfig, 'system_flag');
        !isset($conditions['begin_time']) && $conditions['begin_time'] = time() - 3600;//默认1小时
        $whereRaw = [];
        foreach ($this->oaNotifyConfig as $config) {
            $tmp = [];
            foreach ($config['fields'] as $k => $v) {
                $tmp[] = "`$k` = '$v'";
            }
            $whereRaw[] = implode(' AND ', $tmp);
        }
        $whereRaw = implode(' OR ', $whereRaw);
        $where = [
            ['is_delete', '=', 1],
            ['system_flag', 'IN', $systemFlags],
        ];
        if (!empty($conditions['order_num'])) {
            $where[] = ['order_num', 'IN', (array)$conditions['order_num']];
            unset($conditions['begin_time'], $conditions['end_time']);
        }
        if (!empty($conditions['begin_time'])) {
            $where[] = ['update_time', '>=', date('Y-m-d H:i:s', $conditions['begin_time'])];
        }
        if (!empty($conditions['end_time'])) {
            $where[] = ['update_time', '<=', date('Y-m-d H:i:s', $conditions['end_time'])];
        }
        $count = $this->mOrder->where($where)->whereRaw($whereRaw)->count();
        $totalPage = ceil($count / $pageSize);
        while ($page <= $totalPage) {
            $list = $this->mOrder->where($where)->whereRaw($whereRaw)->page($page, $pageSize)->order('create_time')->select()->toArray();
            foreach ($list as $item) {
                $result[$item['out_order_num']] = $this->notifyOa($item);
            }
            $page++;
        }
        return $result;
    }

    /**
     * 设置OA付款业务配置信息
     */
    protected function setOaCallConfig()
    {
        $this->oaCallConfig = $this->mOaBusiness->getList(['status' => 1, 'is_delete' => 1], '*', null, null);
    }

    /**
     * 调用付款指令接口
     * @param array $requestBaseData
     * @return  array
     */
    protected function callPayOrder($requestBaseData)
    {
        if (empty($requestBaseData)) {
            return packRet(__LINE__, '数据不能为空');
        }
        //外部单号加锁
        if (!$this->mOaModel->acquireLock(self::REDIS_KEY_ORDER_OA_CALL_LOCK.$requestBaseData['requestid'])) {
            return packRet(__LINE__, '请求太频繁');
        }
        //获取付款指令的入参
        try {
            \CommonLog::instance()->getDefaultLogger()->info('获取付款指令的入参：' . json_encode($requestBaseData, JSON_UNESCAPED_UNICODE));
            $data = $this->getPayOrderParams($requestBaseData);
            $this->signParams($data);
        } catch (\Exception $e) {
            \CommonLog::instance()->getDefaultLogger()->warn('获取付款指令的入参失败：' . $e->getMessage());
            return packRet(__LINE__, '获取付款指令的入参失败：'.$e->getMessage());
        }
        //调用付款指令接口
        $result = \JmfUtil::call_Jmf_consumer($this->oaCallService, $data);
        //保存请求日志
        $this->saveRequestLog($data, $result);
        if (!isset($result['code']) || $result['code'] != '0') {//此处有坑，'HY093'为SQL报错，表达式'HY093' != 0，结果false，原因是含有H的字符串跟数字比较，字符串被解析成16进制
            $msg = "调用服务【{$this->oaCallService}】失败，入参：" . json_encode($data, JSON_UNESCAPED_UNICODE) . " | 出参：" . json_encode($result, JSON_UNESCAPED_UNICODE);
            \CommonLog::instance()->getDefaultLogger()->warn($msg);
            //资金系统返回驳回状态
            if ($result['code'] == \ErrMsg::RET_CODE_REPAY_ORDER_OPTING_ERROR) {
                $notifyData = [
                    'request_id' => $requestBaseData['requestid'],
                    'update_time' => date('Y-m-d H:i:s', time()),
                    'audit_log' => ['deal_remark' => $result['msg']],
                ];
                $config = $this->mOaModel->getOaNotifyConfig('reject');
                if (!empty($config)) {
                    $notifyData = array_merge($notifyData, $config['fields']);
                    //OA系统处理付款单通知
                    $result = $this->mOaModel->handleNotify($notifyData);
                    if (!isset($result['code']) || $result['code'] != '0') {
                        $msg = "通知OA系统失败，入参：" . json_encode($data, JSON_UNESCAPED_UNICODE) . " | 出参：" . json_encode($result, JSON_UNESCAPED_UNICODE);
                        \CommonLog::instance()->getDefaultLogger()->warn($msg);
                    } else {
                        $msg = "通知OA系统成功，入参：" . json_encode($data, JSON_UNESCAPED_UNICODE) . " | 出参：" . json_encode($result, JSON_UNESCAPED_UNICODE);
                        \CommonLog::instance()->getDefaultLogger()->info($msg);
                    }
                }
            }
        } else {
            $msg = "调用服务【{$this->oaCallService}】成功，入参：" . json_encode($data, JSON_UNESCAPED_UNICODE) . " | 出参：" . json_encode($result, JSON_UNESCAPED_UNICODE);
            \CommonLog::instance()->getDefaultLogger()->info($msg);
        }
        return $result;
    }

    /**
     * 验证付款指令请求日志
     * @param $data
     * @return mixed
     */
    protected function validateRequestLog(&$data)
    {
        //判断是否有付款请求日志
        $where = [
            ['system_flag', '=', $data['system_flag']],
            ['request_id', '=', $data['out_order_num']],
            ['is_delete', '=', 1],
        ];
        $requestLog = $this->mOaRequestLog->getOne($where, 'request_id, version, timestamp, order_num, resp_code', 'version desc, create_time desc');
        if (empty($requestLog)) {
            return true;
        }
        $requestId = $requestLog['request_id'];
        $version = $requestLog['version'];
        //timestamp为OA流程最后操作时间，同一时刻流程只允许一条成功的记录；不同时刻判断出纳节点是否有驳回记录（资金系统的指令是否为驳回状态）
        if ($requestLog['resp_code'] == '0' && ($requestLog['timestamp'] == $data['timestamp']
                || !$this->mOaModel->hasNodeRejectRecord($requestId, $data['currentnodeid'], $requestLog['timestamp']))) {
            return packRet(__LINE__, '此OA付款指令已存在资金系统');
        }
        unset($data['currentnodeid']);//此参数仅用来验证数据
        //错误码为0或者\ErrMsg::RET_CODE_OUT_ORDER_NUM_DULICATE，版本号加1
        if (in_array($requestLog['resp_code'], ['0', \ErrMsg::RET_CODE_OUT_ORDER_NUM_DULICATE])) {
            ++$version;
        }
        //版本号大于0，需重新生成单号，生成规则：请求编号+下划线+版本号
        if ($version > 0) {
            $data['out_order_num'] = $requestId . '_' . str_pad($version, 2, '0', STR_PAD_LEFT);
        }
        return true;
    }

    /**
     * 保存请求付款指令日志
     * @param $request
     * @param $response
     * @return int|string
     */
    protected function saveRequestLog($request, $response)
    {
        $arr = explode('_', $request['out_order_num']);
        $requestId = $arr[0];
        $version = isset($arr[1]) ? intval($arr[1]) : 0;
        $data = [
            'system_flag' => $request['system_flag'],
            'request_id' => $requestId,
            'version' => $version,
            'timestamp' => $request['timestamp'],
            'order_num' => $response['data']['order_num'] ?? '',
            //'params' => json_encode($request, JSON_UNESCAPED_UNICODE),
            'resp_code' => $response['code'] ?? -1,
            'resp_msg' => $response['msg'] ?? '未知错误',
            'create_time' => date('Y-m-d H:i:s'),
        ];
        return $this->mOaRequestLog->insert($data);
    }

    /**
     * 签名接口参数
     * @param array $data
     */
    protected function signParams(array &$data)
    {
        static $secretKeys = [];
        $systemFlag = $data['system_flag'];
        if (!isset($secretKeys[$systemFlag])) {
            $mInterfacePriv = new InterfacePriv();
            $res = $mInterfacePriv->getOne(['system_flag' => $systemFlag], 'pwd_key');
            $secretKeys[$systemFlag] = isset($res['pwd_key']) ? $res['pwd_key'] : null;
        }

        $data['secret'] = secretGet($data, $secretKeys[$systemFlag]);
    }

    /**
     * 通知OA系统
     * @param array $orderData
     * @return array
     */
    protected function notifyOa($orderData)
    {
        if (empty($orderData)) {
            return packRet(__LINE__, '通知数据不能为空');
        }
        //外部单号加锁
        if (!$this->mOaModel->acquireLock(self::REDIS_KEY_ORDER_OA_NOTIFY_LOCK.$orderData['out_order_num'])) {
            return packRet(__LINE__, '请求太频繁');
        }
        //获取通知OA的入参
        try {
            \CommonLog::instance()->getDefaultLogger()->info('获取通知OA的入参：' . json_encode($orderData, JSON_UNESCAPED_UNICODE));
            $data = $this->getNotifyOaParams($orderData);
        } catch (\Exception $e) {
            \CommonLog::instance()->getDefaultLogger()->warn('获取通知OA的入参失败：' . $e->getMessage());
            return packRet(__LINE__, '获取通知OA的入参失败：'.$e->getMessage());
        }
        //OA系统处理付款单通知
        $result = $this->mOaModel->handleNotify($data);
        //保存通知日志
        $this->saveNotifyLog($data, $result);
        if (!isset($result['code']) || $result['code'] != '0') {
            $msg = "通知OA系统失败，入参：" . json_encode($data, JSON_UNESCAPED_UNICODE) . " | 出参：" . json_encode($result, JSON_UNESCAPED_UNICODE);
            \CommonLog::instance()->getDefaultLogger()->warn($msg);
        } else {
            $msg = "通知OA系统成功，入参：" . json_encode($data, JSON_UNESCAPED_UNICODE) . " | 出参：" . json_encode($result, JSON_UNESCAPED_UNICODE);
            \CommonLog::instance()->getDefaultLogger()->info($msg);
        }
        return $result;
    }

    /**
     * 获取图片链接
     * @param array|string $ids
     * @return string
     */
    protected function getImageLink($ids)
    {
        !is_array($ids) && $ids = explode(',', $ids);
        $list = $this->mSysFile->getList(['uuid' => $ids], 'uuid, origin_name', null, null);
        $str = '';
        foreach ($list as $row) {
            $url = constant('ENV_BASE_URL').'api/download?uuid='.$row['uuid'];
            $str .= "<a href='{$url}' target='_blank'>{$row['origin_name']}</a><br>";
        }
        return $str;
    }

    /**
     * 获取付款，调拨的审批日志
     * @param $uuid
     * @param array $flowCode
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getAuditLog($uuid, array $flowCode = [])
    {
        $flowData = $this->mOrder->getList(['flow_code' => $flowCode, 'is_delete' => 1], 'uuid', null, null, null, 'm_sys_audit_flow');
        $where = ['i.instance_id' => (array)$uuid, 'i.flow_uuid' => array_column($flowData, 'uuid'), 'l.deal_result' => [2, 3, 4]];
        //获取最后一条审批意见即可
        $result = $this->mOrder->table('m_sys_audit_instance i')->field('l.deal_remark')
            ->leftJoin('m_sys_audit_log l', 'i.uuid=l.instance_uuid')
            ->where($where)->order('l.update_time desc')->find();
        return !empty($result) ? $result->toArray() : null;
    }

    /**
     * 验证付款指令通知日志
     * @param $data
     * @return array|bool
     */
    protected function validateNotifyLog(&$data)
    {
        $outOrderNum = $data['out_order_num'];
        //驳回重新发起的单号会在原单号末尾追加数字，格式：xxxxxxx_xx，故需提取出原始单号
        if (strpos($outOrderNum, '_') !== false) {
            list($requestId, $version) = explode('_', $outOrderNum);
        } else {
            $requestId = $outOrderNum;
            $version = 0;
        }
        $version = intval($version);
        $data['request_id'] = $requestId;
        $where = [
            ['system_flag', '=', $data['system_flag']],
            ['request_id', '=', $requestId],
            ['is_delete', '=', 1],
        ];
        //判断是否有发送过通知
        $notifyLog = $this->mOaNotifyLog->getOne($where, 'request_id, version, timestamp, order_num, resp_code', 'version desc, create_time desc');
        if (empty($notifyLog)) {
            return true;
        }
        //通知日志中付款单的最大版本号大于当前付款单的版本号，表示此付款单已失效
        if ($notifyLog['version'] > $version) {
            return packRet(__LINE__, '此付款单已失效');
        } elseif ($notifyLog['version'] == $version) {
            if ($notifyLog['resp_code'] == '0') {
                return packRet(__LINE__, 'OA系统已经处理过此付款单的通知');
            }
            $where[] = ['version', '=', $version];
            if ($this->mOaNotifyLog->getCount($where) > 100) {
                return packRet(__LINE__, '通知OA系统付款状态次数超过限制');
            }
        } else {
            //通知日志中付款单的最大版本号小于当前付款单的版本号，说明某OA付款流程发起过多次付款指令，最新的指令还没有通知过OA
            return true;
        }
        return true;
    }

    /**
     * 保存付款通知日志
     * @param $request
     * @param $response
     * @return int|string
     */
    protected function saveNotifyLog($request, $response)
    {
        $arr = explode('_', $request['out_order_num']);
        $requestId = $arr[0];
        $version = isset($arr[1]) ? intval($arr[1]) : 0;
        $data = [
            'system_flag' => $request['system_flag'],
            'request_id' => $requestId,
            'version' => $version,
            'timestamp' => strtotime($request['update_time']),
            'order_num' => $request['order_num'] ?? '',
            //'params' => json_encode($request, JSON_UNESCAPED_UNICODE),
            'resp_code' => $response['code'] ?? -1,
            'resp_msg' => $response['msg'] ?? '未知错误',
            'create_time' => date('Y-m-d H:i:s'),
        ];
        return $this->mOaNotifyLog->insert($data);
    }

    /**
     * 获取调用指令的入参
     * @param $data
     * @return mixed
     */
    abstract protected function getPayOrderParams($data);

    /**
     * 获取通知OA的入参
     * @param array $data
     * @return mixed
     */
    abstract protected function getNotifyOaParams($data);
}
