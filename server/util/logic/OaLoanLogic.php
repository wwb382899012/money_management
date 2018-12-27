<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/6
 * Time: 9:34
 */

namespace money\logic;

use money\model\LoanOrder;
use money\model\LoanTransfer;
use money\model\OaModel;
use money\model\OaLoanBusiness;
use money\model\OaLoanRequestLog;
use money\model\OaLoanNotifyLog;

class OaLoanLogic extends AbstractOaLogic
{
    protected $oaNotifyConfig = [
        //付款成功
        0 => [
            'type' => 'success',
            'fields' => [
                'order_status' => LoanOrder::ORDER_STATUS_ARCHIVE,
            ],
        ],
        //付款驳回
        1 => [
            'type' => 'reject',
            'fields' => [
                'order_status' => LoanOrder::ORDER_STATUS_REJECT,
            ],
        ],
        //付款拒绝
        2 => [
            'type' => 'refuse',
            'fields' => [
                'order_status' => LoanOrder::ORDER_STATUS_REFUSE,
            ],
        ],
    ];

    protected $oaCallService = 'com.jyblife.logic.bg.loan.LoanOrder';

    const REDIS_KEY_ORDER_OA_CALL_LOCK = 'com.jyblife.logic.bg.loan.oa.call:';
    const REDIS_KEY_ORDER_OA_NOTIFY_LOCK = 'com.jyblife.logic.bg.loan.oa.notify:';

    public function __construct()
    {
        $this->mOrder = new LoanOrder();
        $this->mTransfer = new LoanTransfer();
        $this->mOaModel = new OaModel();
        $this->mOaBusiness = new OaLoanBusiness();
        $this->mOaRequestLog = new OaLoanRequestLog();
        $this->mOaNotifyLog = new OaLoanNotifyLog();

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
            throw new \Exception('OA借款业务配置有误');
        }

        //获取OA付款业务数据
        $origin = $this->mOaModel->getOne(['requestId' => $requestBaseData['requestid']], '*', null, $config['table']);
        if (empty($origin)) {
            throw new \Exception('OA借款业务主表没有此记录');
        }

        //解析变量
        $data = $this->mOaModel->parseVar($config['fields'], $origin);
        $data['collect_bank_account'] = str_replace(' ', '', $data['collect_bank_account']);//过滤银行账号空格
        $data['amount'] = intval((double)$data['amount'] * 100);//金额使用分做单位
        $data['system_flag'] = $config['system_flag'];
        //使用配置表默认值，1、借款
        $data['loan_type'] = $config['order_pay_type'];
        //时间戳使用OA流程最后操作时间，方便后续的付款驳回场景重试
        $data['timestamp'] = strtotime($requestBaseData['lastoperatedate'].' '.$requestBaseData['lastoperatetime']);
        $data['currentnodeid'] = $config['currentnodeid'];

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
        $fields = ['system_flag', 'order_num', 'out_order_num', 'order_status', 'loan_status', 'update_time'];
        $data = array_filter($orderData, function ($k) use ($fields) {
            return in_array($k, $fields);
        }, ARRAY_FILTER_USE_KEY);

        //验证付款指令通知日志
        if (($result = $this->validateNotifyLog($data)) !== true) {
            throw new \Exception($result[PARAM_MSG]);
        }

        //获取调拨数据
        $transferData = $this->mTransfer->getOne(['loan_order_uuid' => $orderData['uuid'], 'is_delete' => 1], 'uuid');
        if (!empty($transferData)) {
            $uuids = [$orderData['uuid'], $transferData['uuid']];
        } else {
            $uuids = [$orderData['uuid']];
        }

        //获取审批日志
        $flowCode = ['loan_order', 'loan_transfer_pay_type_1_code', 'loan_transfer_pay_type_2_code'];
        $data['audit_log'] = $this->getAuditLog($uuids, $flowCode);

        return $data;
    }
}
