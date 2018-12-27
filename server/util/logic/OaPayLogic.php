<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/6
 * Time: 9:34
 */

namespace money\logic;

use money\model\PayOrder;
use money\model\PayTransfer;
use money\model\OaModel;
use money\model\OaPayBusiness;
use money\model\OaPayRequestLog;
use money\model\OaPayNotifyLog;
use money\model\SysFile;

class OaPayLogic extends AbstractOaLogic
{
    protected $oaNotifyConfig = [
        //付款成功
        0 => [
            'type' => 'success',
            'fields' => [
                'order_status' => PayOrder::ORDER_STATUS_OPTED,
                'pay_status' => PayOrder::PAY_STATUS_PAID,
            ],
        ],
        //付款驳回
        1 => [
            'type' => 'reject',
            'fields' => [
                'order_status' => PayOrder::ORDER_STATUS_REJECT,
            ],
        ],
        //付款拒绝
        2 => [
            'type' => 'refuse',
            'fields' => [
                'order_status' => PayOrder::ORDER_STATUS_REFUSE,
            ],
        ],
    ];

    protected $oaCallService = 'com.jyblife.logic.bg.order.PayOrder';

    const REDIS_KEY_ORDER_OA_CALL_LOCK = 'com.jyblife.logic.bg.order.oa.call:';
    const REDIS_KEY_ORDER_OA_NOTIFY_LOCK = 'com.jyblife.logic.bg.order.oa.notify:';

    public function __construct()
    {
        $this->mSysFile = new SysFile();
        $this->mOrder = new PayOrder();
        $this->mTransfer = new PayTransfer();
        $this->mOaModel = new OaModel();
        $this->mOaBusiness = new OaPayBusiness();
        $this->mOaRequestLog = new OaPayRequestLog();
        $this->mOaNotifyLog = new OaPayNotifyLog();

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
            throw new \Exception('OA付款业务配置有误');
        }

        //获取OA付款业务数据
        $origin = $this->mOaModel->getOne(['requestId' => $requestBaseData['requestid']], '*', null, $config['table']);
        if (empty($origin)) {
            throw new \Exception('OA付款业务主表没有此记录');
        }

        //解析变量
        $data = $this->mOaModel->parseVar($config['fields'], $origin);
        $data['collect_bank'] = $data['collect_bank_name'];
        $data['collect_bank_address'] = $data['collect_bank_desc'];
        $data['collect_bank_account'] = str_replace(' ', '', $data['collect_bank_account']);//过滤银行账号空格
        $data['amount'] = intval((double)$data['amount'] * 100);//金额使用分做单位
        $data['system_flag'] = $config['system_flag'];
        //OA付款表中未设置值，则使用配置表默认值，OA选择框配置默认值从0开始
        $data['order_pay_type'] = (isset($data['order_pay_type']) && is_numeric($data['order_pay_type'])) ? $data['order_pay_type'] + 1 : $config['order_pay_type'];
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
        $fields = ['system_flag', 'order_num', 'out_order_num', 'order_status', 'pay_status', 'update_time'];
        $data = array_filter($orderData, function ($k) use ($fields) {
            return in_array($k, $fields);
        }, ARRAY_FILTER_USE_KEY);

        //验证付款指令通知日志
        if (($result = $this->validateNotifyLog($data)) !== true) {
            throw new \Exception($result[PARAM_MSG]);
        }

        //获取调拨数据
        $transferData = $this->mTransfer->getOne(['pay_order_uuid' => $orderData['uuid'], 'is_delete' => 1], 'uuid, bank_water, bank_img_file_uuid');
        if (!empty($transferData)) {
            //银行流水
            if (!empty($transferData['bank_water'])) {
                $data['formtable_main']['lsh'] = $transferData['bank_water'];
            }
            //回单图片URL
            if (!empty($transferData['bank_img_file_uuid'])) {
                $data['formtable_main']['hdlj'] = $this->getImageLink($transferData['bank_img_file_uuid']);
            }
            $uuids = [$orderData['uuid'], $transferData['uuid']];
        } else {
            $uuids = [$orderData['uuid']];
        }

        //获取审批日志
        $flowCode = ['pay_order', 'pay_transfer_pay_type_1_code', 'pay_transfer_pay_type_2_code'];
        $data['audit_log'] = $this->getAuditLog($uuids, $flowCode);

        return $data;
    }
}
