<?php
/**
 * 用户信息获取
 */
use money\service\BaseService;
use money\model\SysUser;

class PrivData extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'tablename' => 'require',
    ];

    public function exec(){
        $sessionToken = $this->m_request['sessionToken'];
        $tablename = $this->m_request['tablename'];
        $sessionInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$sessionToken]);
        if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
            $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : ErrMsg::RET_CODE_SERVICE_FAIL;
            $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
            throw new \Exception($msg, $code);
        }
        //写死用户主体权限
        $privTable = [
            'm_pay_order'=>'pay_main_body_uuid',
            'm_pay_transfer' => 'pay_main_body_uuid',
            'm_inner_transfer' => 'main_body_uuid',
            'm_loan_order' => 'pay_main_body_uuid',
            'm_loan_transfer' => 'pay_main_body_uuid',
            'm_money_manager_plan' => 'plan_main_body_uuid'            
        ];
        if(!in_array($tablename, array_keys($privTable))){
            throw new \Exception('无权限数据', ErrMsg::RET_CODE_SERVICE_FAIL);
        }

        $user_id = $sessionInfo['data']['user_id'];
        $userdb = new SysUser();
        $userInfo = $userdb->userDetail($user_id);
        $mainUuids = [];
        foreach($userInfo['main_body'] as $row){
            $mainUuids[] = $row['uuid'];
        }

        $this->packRet(ErrMsg::RET_CODE_SUCCESS, [$privTable[$tablename]=>$mainUuids]);
    }
}