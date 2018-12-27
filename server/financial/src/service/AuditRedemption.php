<?php
/**
 * 发起理财赎回审核
 */

use money\logic\EventProvider;
use money\model\SysUser;
use money\service\BaseService;
use money\model\MoneyPlan;
use money\model\EodTradeDb;
use money\logic\CommonLogic;

class AuditRedemption extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'plan_uuid' => 'require',
        'cash_flow' => 'require|array',
    ];

    public function exec(){
        $plan_uuid = $this->m_request['plan_uuid'];
        $cashFlow = $this->m_request['cash_flow'];
        $sessionToken = $this->m_request['sessionToken'];
        $db = new MoneyPlan();
        $commonLogic = new CommonLogic($db);
        $dataDetail = $commonLogic->uuidPriv($plan_uuid, $sessionToken);

        $cashUuids = array_column($cashFlow, 'uuid');
        $count=0;
        foreach($dataDetail['cash_flow'] as $row){
            if(in_array($row['uuid'], $cashUuids)){
                $count++;
                if(!in_array($row['status'], [MoneyPlan::PLAN_STATUS_WAITING, MoneyPlan::PLAN_STATUS_OPTED, MoneyPlan::PLAN_STATUS_COMFIRMED, MoneyPlan::PLAN_STATUS_WAIT_TICKET_BACK])){
                    throw new \Exception('不能重复审核'.$row['uuid'], ErrMsg::RET_CODE_SERVICE_FAIL);
                }
            }
        }
        if(count($cashUuids) != $count){
            throw new \Exception('存在非法的cash_flow_uuid', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        //过滤字段
        foreach ($cashFlow as &$item) {
            $item = array_filter($item, function ($v, $k) {
                return in_array($k, ['uuid', 'change_amount', 'info', 'bank_water', 'bank_img_file_uuid']);
            }, ARRAY_FILTER_USE_BOTH);
        }
        if ($dataDetail['term_type'] == 1) {//开放式
            $flow['flow_code'] = REDEM_AUDIT_FLOW_CODE;
            $flow['instance_id'] = $plan_uuid;
            $flow['sessionToken'] = $sessionToken;
            $flow['params'] = ['cash_flow' => $cashFlow];
            $flow['approve_type'] = isset($this->m_request['approve_type'])?intval($this->m_request['approve_type']):1;
            $flow['info'] = isset($this->m_request['info'])?$this->m_request['info']:'';
            $ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve', $flow, 5);
            if(!isset($ret['code']) || $ret['code'] != '0'){
                throw new \Exception('提交审核失败:'.$ret['msg'], ErrMsg::RET_CODE_SERVICE_FAIL);
            }
        } else {//封闭式
            if (!$db->finishRedemption($cashFlow, $dataDetail['uuid'])) {
                throw new \Exception ( '现金流数据保存失败', ErrMsg::RET_CODE_SERVICE_FAIL );
            }
            EodTradeDb::dataOpted($plan_uuid, 9);

            //抄送资金负责人，权签人
            $event = new EventProvider ();
            $s = new SysUser();
            $next_node_users = $s->getUserIdForMainUuidRoleId($dataDetail['plan_main_body_uuid'], ['00cc4afb2f67592ba520e5bfcafc7034', '3c925709e783aefe27b33e095ac168ec']);
            $createUserInfo = $this->getuserForId($dataDetail ['create_user_id'], $sessionToken);

            $eventData ['plan_uuid'] = $dataDetail['uuid'];
            $eventData ['money_manager_plan_num'] = $dataDetail ['money_manager_plan_num'];
            $eventData ['money_manager_product_name'] = $dataDetail ['money_manager_product_name'];
            $eventData ['amount'] = $dataDetail['amount'];
            $eventData ['create_user_id'] = $dataDetail ['create_user_id'];
            $eventData ['create_user_name'] = $createUserInfo ['name'];
            $eventData ['create_user_email'] = $createUserInfo ['email'];
            $eventData ['audit_datetime'] = date('Y-m-d H:i:s');
            $eventData ['node_code'] = 'redemption_audit_node_no_4';

            $eventData ['cur_audit_user_id'] = $dataDetail ['create_user_id'];
            $eventData ['cur_audit_control_type'] = 2;

            if (!empty ($next_node_users)) {
                $next_node_users = explode(",", $next_node_users);
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
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, []);
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