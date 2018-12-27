<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/6
 * Time: 9:34
 */

namespace money\logic;

use money\model\RepayOrder;
use money\model\Repay;
use money\model\OaModel;
use money\model\OaRepayBusiness;
use money\model\OaRepayRequestLog;
use money\model\OaRepayNotifyLog;
use money\model\OaLoanRequestLog;

class OaRepayLogic extends AbstractOaLogic
{
    /**
     * @var OaLoanRequestLog
     */
    protected $mOaLoanRequestLog;

    protected $oaNotifyConfig = [
        //付款成功
        0 => [
            'type' => 'success',
            'fields' => [
                'repay_order_status' => RepayOrder::ORDER_STATUS_ARCHIVE,
            ],
        ],
        //付款驳回
        1 => [
            'type' => 'reject',
            'fields' => [
                'repay_order_status' => RepayOrder::ORDER_STATUS_REJECT,
            ],
        ],
        //付款拒绝
        /*2 => [
            'type' => 'refuse',
            'fields' => [
                'repay_order_status' => RepayOrder::ORDER_STATUS_REFUSE,
            ],
        ],*/
    ];

    protected $oaCallService = 'com.jyblife.logic.bg.loan.RepayOrder';

    const REDIS_KEY_ORDER_OA_CALL_LOCK = 'com.jyblife.logic.bg.repay.oa.call:';
    const REDIS_KEY_ORDER_OA_NOTIFY_LOCK = 'com.jyblife.logic.bg.repay.oa.notify:';

    public function __construct()
    {
        $this->mOrder = new RepayOrder();
        $this->mTransfer = new Repay();
        $this->mOaModel = new OaModel();
        $this->mOaBusiness = new OaRepayBusiness();
        $this->mOaRequestLog = new OaRepayRequestLog();
        $this->mOaNotifyLog = new OaRepayNotifyLog();
        $this->mOaLoanRequestLog = new OaLoanRequestLog();

        parent::__construct();
    }

    /**
     * 获取调用指令的入参
     * @param array $requestBaseData
     * @return  array|bool
     * @throws \Exception
     */
    protected function getPayOrderParams($requestBaseData)
    {
        //获取配置
        $config = $this->mOaModel->getOaCallConfig($requestBaseData['workflowid'], $requestBaseData['currentnodeid']);
        if (empty($config) || empty($config['table']) || empty($config['system_flag']) || empty($config['fields'])) {
            throw new \Exception('OA还款业务配置有误');
        }

        //获取OA付款业务数据
        $origin = $this->mOaModel->getOne(['requestId' => $requestBaseData['requestid']], '*', null, $config['table']);
        if (empty($origin)) {
            throw new \Exception('OA还款业务主表没有此记录');
        }

        //解析变量
        $data = $this->mOaModel->parseVar($config['fields'], $origin);
        $data['collect_bank_account'] = str_replace(' ', '', $data['collect_bank_account']);//过滤银行账号空格
        $data['amount'] = intval((double)$data['amount'] * 100);//金额使用分做单位
        $data['system_flag'] = $config['system_flag'];
        //OA还款表中未设置值，则使用配置表默认值，OA选择框配置默认值从0开始，2、提前还款 3、延期还款
        $data['repay_type'] = (isset($data['repay_type']) && is_numeric($data['repay_type'])) ? $data['repay_type'] + 2 : $config['order_pay_type'];
        //时间戳使用OA流程最后操作时间，方便后续的付款驳回场景重试
        $data['timestamp'] = strtotime($requestBaseData['lastoperatedate'].' '.$requestBaseData['lastoperatetime']);
        $data['currentnodeid'] = $config['currentnodeid'];

        //验证借款指令请求日志，并获取付款系统的借款外部指令编号
        if (($result = $this->validateLoanRequestLog($data)) !== true) {
            throw new \Exception($result[PARAM_MSG]);
        }

        //验证付款指令请求日志
        if (($result = $this->validateRequestLog($data)) !== true) {
            throw new \Exception($result[PARAM_MSG]);
        }

        //获取发起节点操作人
        $operator = $this->mOaModel->getWorkflowNodeOperator($requestBaseData['requestid'], $config['workflowid'], $config['reject_nodeid'], 'id, lastname');
        $data['create_user_id'] = $operator['id'] ?? 1;//接口未接收此参数
        $data['order_create_people'] = $operator['lastname'] ?? '管理员';

        return $data;
    }

    /**
     * 获取通知OA的入参
     * @param array $orderData
     * @return mixed
     * @throws \Exception
     */
    protected function getNotifyOaParams($orderData)
    {
        $fields = ['system_flag', 'order_num', 'out_order_num', 'repay_order_status', 'update_time'];
        $data = array_filter($orderData, function ($k) use ($fields) {
            return in_array($k, $fields);
        }, ARRAY_FILTER_USE_KEY);

        //验证付款指令通知日志
        if (($result = $this->validateNotifyLog($data)) !== true) {
            throw new \Exception($result[PARAM_MSG]);
        }

        //获取调拨数据
        $transferData = $this->mTransfer->getOne(['id' => $orderData['repay_id'], 'is_delete' => 1], 'id');
        if (!empty($transferData)) {
            $uuids = [$orderData['id'], $transferData['id']];
        } else {
            $uuids = [$orderData['id']];
        }

        //获取审批日志
        $flowCode = ['repay_order', 'repay_order_apply', 'repay_apply', 'repay_transfer_pay_type_2_code'];
        $data['audit_log'] = $this->getAuditLog($uuids, $flowCode);

        return $data;
    }

    /**
     * 验证借款指令通知日志
     * @param $data
     * @return array|bool
     */
    protected function validateLoanRequestLog(&$data)
    {
        //获取付款系统的借款外部指令编号
        $where = [
            ['system_flag', '=', $data['system_flag']],
            ['request_id', '=', $data['loan_out_order_num']],
            ['is_delete', '=', 1],
        ];
        $oaLoanRequestLog = $this->mOaLoanRequestLog->getOne($where, 'resp_code, version', 'version desc, create_time desc');
        if (empty($oaLoanRequestLog) || $oaLoanRequestLog['resp_code'] != '0') {
            return packRet(__LINE__, '没有关联的已完结借款记录');
        }
        if ($oaLoanRequestLog['version'] > 0) {
            $data['loan_out_order_num'] .= '_' . str_pad($oaLoanRequestLog['version'], 2, '0', STR_PAD_LEFT);
        }
        return true;
    }
}
