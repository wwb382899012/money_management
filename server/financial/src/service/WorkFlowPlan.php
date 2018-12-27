<?php

use money\service\BaseService;
use money\model\MoneyPlan;
use money\model\BankAccount;
use money\model\SysUser;
use money\model\EodTradeDb;
use money\model\ReportFullTrade;
use money\model\SysTradeWater;
use money\model\MainBody;
use money\logic\EventProvider;

class WorkFlowPlan extends BaseService
{
    protected $rule = [
        'sessionToken' => 'require',
        'instance_id' => 'require',
        'node_code' => 'require',
        'node_status' => 'require',
        'flow_code' => 'require'
    ];

    public function exec()
    {
        $flow_code = $this->m_request ['flow_code'];
        if (in_array($flow_code, [
            AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_1,
            AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_2
        ])) {
            $this->financial_audit();
        } else if ($flow_code == REDEM_AUDIT_FLOW_CODE) {
            $this->redem_audit();
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, []);
    }

    /**
     * 理财审核回调
     */
    protected function financial_audit()
    {
        $node_code = $this->m_request ['node_code'];
        $sessionToken = $this->m_request ['sessionToken'];
        $db = new MoneyPlan ();
        $event = new EventProvider ();
        $uuid = $this->m_request ['instance_id'];

        $detailData = $db->detail($uuid);
        if (!$detailData) {
            throw new \Exception ('理财计划数据不存在', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        $eventData ['plan_uuid'] = $detailData ['uuid'];
        $eventData ['money_manager_plan_num'] = $detailData ['money_manager_plan_num'];
        $eventData ['money_manager_product_name'] = $detailData ['money_manager_product_name'];
        $eventData ['amount'] = $detailData ['amount'];
        $eventData ['real_pay_type'] = $detailData ['real_pay_type'];
        $createUserInfo = $this->getuserForId($detailData ['create_user_id'], $sessionToken);
        $eventData ['create_user_id'] = $detailData ['create_user_id'];
        $eventData ['create_user_name'] = $createUserInfo ['name'];
        $eventData ['create_user_email'] = $createUserInfo ['email'];
        $eventData ['cur_audit_user_id'] = $this->m_request ['optor'];
        $eventData ['cur_audit_control_type'] = $this->m_request ['node_status'];
        $eventData ['audit_datetime'] = date('Y-m-d H:i:s');
        $eventData ['node_code'] = $node_code;

        if (in_array($node_code, [
            AUDIT_NODE_PAY_TYPE_1_NO_1,
            AUDIT_NODE_PAY_TYPE_2_NO_1
        ])) {
            $db->auditStep($uuid, MoneyPlan::PLAN_STATUS_WAITING);
            $r = [
                'transfer_num' => $detailData['money_manager_plan_num'],
                'main_body_uuid' => $detailData['plan_main_body_uuid'],
                'transfer_create_time' => date('Y-m-d H:i:s'),
                'limit_date' => $detailData['rate_start_date'],
                'opt_uuid' => $uuid,
                'trade_type' => 8
            ];
            EodTradeDb::dataCreate($r);
        } else if (in_array($node_code, [
            AUDIT_NODE_PAY_TYPE_1_NO_2,
            AUDIT_NODE_PAY_TYPE_2_NO_2
        ])) { // 资金专员审核回调
            if ($this->m_request ['node_status'] == 3) { // 审核拒绝
                $db->auditStep($uuid, MoneyPlan::PLAN_STATUS_REJECT);
                EodTradeDb::dataOpted($uuid, 8);
            } else {
                $db->auditStep($uuid, MoneyPlan::PLAN_STATUS_OPTED);
            }
        } else if (in_array($node_code, [
            AUDIT_NODE_PAY_TYPE_1_NO_3,
            AUDIT_NODE_PAY_TYPE_2_NO_3
        ])) {
            if ($this->m_request ['node_status'] == 2) { // 审核通过
                try {
                    $db->startTrans();
                    if ($detailData ['real_pay_type'] == 2) {
                        // 调用银企接口付款
                        $db->auditStep($uuid, MoneyPlan::PLAN_STATUS_COMFIRMED, MoneyPlan::PAY_STATUS_PAID);
                        $detailData['plan_status'] = MoneyPlan::PLAN_STATUS_COMFIRMED;
                    } else {
                        $db->auditStep($uuid, MoneyPlan::PLAN_STATUS_ARCHIVE, MoneyPlan::PAY_STATUS_PAID);
                        $detailData['plan_status'] = MoneyPlan::PLAN_STATUS_ARCHIVE;
                    }

                    EodTradeDb::dataOpted($uuid, 8);

                    //添加银行流水
                    $this->addWater($detailData, $db);

                    $db->commit();
                } catch (\Exception $e) {
                    $db->rollback();
                    throw $e;
                }
            } else if ($this->m_request ['node_status'] == 3) { // 审核拒绝
                $db->auditStep($uuid, MoneyPlan::PLAN_STATUS_CHECK_REJECT);
                EodTradeDb::dataOpted($this->m_request['instance_id'], 8);
            }
        } else if ($node_code == AUDIT_NODE_PAY_TYPE_1_NO_4) {
            try {
                $db->startTrans();

                //保存银行流水
                $array ['need_ticket_back'] = 0;
                $array ['bank_water'] = $this->m_request ['params'] ['bank_water'];
                $array ['bank_img_file_uuid'] = $this->m_request ['params'] ['bank_img_file_uuid'];
                $db->savePlan($array, $uuid);

                $obj = new ReportFullTrade();
                $obj->where(['trade_uuid' => $uuid])->update(['bank_water_no' => $array ['bank_water']]);

                $db->commit();
            } catch (\Exception $e) {
                $db->rollback();
                throw $e;
            }
        } else {
            \CommonLog::instance()->getDefaultLogger()->warn('理财审核回调报错，入参：'.var_export($this->m_request, true));
            throw new \Exception ('未知审批节点', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        if (!empty ($this->m_request ['next_node_users'])) {
            $next_node_users = explode(",", $this->m_request ['next_node_users']);
            $userInfos = SysUser::getUserInfoByIds($next_node_users);
            $users = array();
            foreach ($userInfos as $u) {
                $users [] = [
                    'name' => $u ['name'],
                    'id' => $u ['user_id'],
                    'email' => $u ['email']
                ];
            }
            $eventData ['next_audit_user_infos'] = $users;
        }
        $event->auditEvent($eventData);
    }

    /**
     * 赎回审核回调
     */
    protected function redem_audit()
    {
        $node_code = $this->m_request ['node_code'];
        $uuid = $this->m_request ['instance_id'];
        $sessionToken = $this->m_request ['sessionToken'];
        $cashFlow = $this->m_request['params']['cash_flow'];

        $db = new MoneyPlan ();
        $planDetail = $db->detail($uuid);
        if (!$planDetail) {
            throw new \Exception ('理财计划信息不存在', ErrMsg::RET_CODE_SERVICE_FAIL);
        }

        $event = new EventProvider ();
//		$detailData = $db->redemDetail ( $uuid );
//		if (! $detailData) {
//			throw new \Exception ( '理财赎回信息不存在', ErrMsg::RET_CODE_SERVICE_FAIL );
//		}
        if ($node_code == REDEM_AUDIT_NODE_NO_1) {
            $db->batchRedemAuditStep($cashFlow, MoneyPlan::PLAN_STATUS_SAVED, MoneyPlan::PLAN_STATUS_OPTED);

            $params = [
                'transfer_num' => $planDetail['money_manager_plan_num'],
                'main_body_uuid' => $planDetail['plan_main_body_uuid'],
                'transfer_create_time' => date('Y-m-d H:i:s'),
                'limit_date' => $planDetail['rate_over_date'],
                'opt_uuid' => $uuid,
                'trade_type' => 9
            ];
            EodTradeDb::dataCreate($params);
        } else if ($node_code == REDEM_AUDIT_NODE_NO_2) {
            if ($this->m_request ['node_status'] == 2) { // 审核通过
                $db->batchRedemAuditStep($cashFlow, MoneyPlan::PLAN_STATUS_OPTED, MoneyPlan::PLAN_STATUS_WAIT_TICKET_BACK);
            } else if ($this->m_request ['node_status'] == 3) {
                $db->batchRedemAuditStep($cashFlow, MoneyPlan::PLAN_STATUS_OPTED, MoneyPlan::PLAN_STATUS_CHECK_REJECT);
                EodTradeDb::dataOpted($uuid, 9);
            }
        } else if ($node_code == REDEM_AUDIT_NODE_NO_4) {
            $db->batchRedemAuditStep($cashFlow, MoneyPlan::PLAN_STATUS_WAIT_TICKET_BACK, MoneyPlan::PLAN_STATUS_ARCHIVE);
            $db->setPayOff($uuid);
            EodTradeDb::dataOpted($uuid, 9);

            //抄送资金负责人，权签人
            $s = new SysUser();
            $this->m_request ['next_node_users'] = $s->getUserIdForMainUuidRoleId($planDetail['plan_main_body_uuid'], ['00cc4afb2f67592ba520e5bfcafc7034', '3c925709e783aefe27b33e095ac168ec']);
        } else {
            \CommonLog::instance()->getDefaultLogger()->warn('赎回审核回调报错，入参：'.var_export($this->m_request, true));
            throw new \Exception ('未知审批节点', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
//		$amount = 0;
//		foreach($detailData as $d){
//			$amount+= $d['amount'];
//		}
        $createUserInfo = $this->getuserForId($planDetail ['create_user_id'], $sessionToken);

        $eventData ['plan_uuid'] = $uuid;
        $eventData ['money_manager_plan_num'] = $planDetail ['money_manager_plan_num'];
        $eventData ['money_manager_product_name'] = $planDetail ['money_manager_product_name'];
        $eventData ['amount'] = $planDetail['amount'];
        $eventData ['create_user_id'] = $planDetail ['create_user_id'];
        $eventData ['create_user_name'] = $createUserInfo ['name'];
        $eventData ['create_user_email'] = $createUserInfo ['email'];
        $eventData ['audit_datetime'] = date('Y-m-d H:i:s');
        $eventData ['node_code'] = $node_code;

// 		$eventData ['cash_flow_data'] = [ 
// 				'repay_date' => $detailData ['repay_date'],
// 				'cash_flow_type' => $detailData ['cash_flow_type'],
// 				'amount' => $detailData ['amount'],
// 				'change_amount' => $detailData ['change_amount'],
// 				'create_user_name' => $createUserInfo ['name'],
// 				'create_user_id' => $createUserInfo ['user_id'],
// 				'create_user_email' => $createUserInfo ['email'] 
// 		];
        $eventData ['cur_audit_user_id'] = $this->m_request ['optor'];
        $eventData ['cur_audit_control_type'] = $this->m_request ['node_status'];

        if (!empty ($this->m_request ['next_node_users'])) {
            $next_node_users = explode(",", $this->m_request ['next_node_users']);
            $userInfos = SysUser::getUserInfoByIds($next_node_users);
            $users = array();
            foreach ($userInfos as $u) {
                $users [] = [
                    'name' => $u ['name'],
                    'id' => $u ['user_id'],
                    'email' => $u ['email']
                ];
            }
            $eventData ['next_audit_user_infos'] = $users;
        }
        $event->redemAuditEvent($eventData);
    }

    /**
     * 添加银行流水
     *
     * @param
     *            $data
     * @param
     *            $sessionToken
     * @param $db MoneyPlan
     * @throws Exception
     */
    protected function addWater($data, $db)
    {
        if (!in_array($data ['plan_status'], [
                MoneyPlan::PLAN_STATUS_COMFIRMED,
                MoneyPlan::PLAN_STATUS_WAIT_TICKET_BACK,
                MoneyPlan::PLAN_STATUS_ARCHIVE
            ]) || $data ['real_pay_type'] != 1) {
            throw new \Exception ('当前状态不允许增加流水', ErrMsg::RET_CODE_SERVICE_FAIL);
        }

        $oBankAmount = new BankAccount ();
        $pay_bank_account = $oBankAmount->getDataById($data ['pay_bank_uuid']);
        // 创建系统交易流水
        $water['order_uuid'] = $data['money_manager_plan_num'];
        $water ['trade_type'] = 19;
        $water ['pay_account_uuid'] = $data ['pay_bank_uuid'];
        $water ['pay_bank_key'] = $pay_bank_account ['bank_dict_key'];
        $water ['pay_bank_account'] = $data ['pay_bank_account'];
        $water ['amount'] = $data ['amount'];
        $water ['currency'] = 'cny';
        $water ['status'] = 3;

        try {
            $db->startTrans();
            $mSysTradeWater = new SysTradeWater();
            $uuid = $mSysTradeWater->addWater($water);
            $planData = [
                'need_ticket_back' => 1,
                'sys_water' => $uuid,
            ];
            $db->savePlan($planData, $data['uuid']);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * 根据角色获取用户信息
     */
    protected function getUserForRole(array $roleIds, $sessionToken)
    {
        if (empty ($roleIds)) {
            return [];
        }
        $data = [
            'sessionToken' => $sessionToken,
            'role_uuid' => $roleIds,
            'page' => 1,
            'limit' => 100
        ];
        $ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.user.UserList', $data);
        if (isset ($ret ['code']) && $ret ['code'] != 0) {
            throw new \Exception ('获取用户数据失败', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        $userInfo = [];
        foreach ($ret ['data'] ['data'] as $row) {
            $userInfo [] = [
                'id' => $row ['user_id'],
                'name' => $row ['name'],
                'email' => $row ['email']
            ];
        }
        return $userInfo;
    }

    /**
     * 获取用户信息
     */
    protected function getuserForId($userId, $sessionToken)
    {
        $createUserInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.user.UserDetail', [
            'sessionToken' => $sessionToken,
            'user_id' => $userId
        ]);
        if (!isset ($createUserInfo ['code']) || $createUserInfo ['code'] != 0) {
            throw new \Exception ('创建用户不存在', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        return $createUserInfo ['data'];
    }
}