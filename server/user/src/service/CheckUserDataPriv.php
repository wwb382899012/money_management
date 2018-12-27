<?php
/**
 * 判断用户数据权限 
 */
use money\service\BaseService;
use money\model\SysUser;

class CheckUserDataPriv extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'tablename' => 'require',
        'priv_uuid' => 'require',
    ];

    public function exec(){
        $tablename = $this->m_request['tablename'];
        $uuid = $this->m_request['priv_uuid'];
        $user_id = isset($this->m_request['user_id']) ? $this->m_request['user_id'] : null;
        $privTable = [
            'm_pay_order'=>'pay_main_body_uuid',
            'm_pay_transfer' => 'pay_main_body_uuid',
            'm_inner_transfer' => 'main_body_uuid',
            'm_loan_order' => 'pay_main_body_uuid',
            'm_loan_transfer' => 'pay_main_body_uuid',
            'm_money_manager_plan' => 'plan_main_body_uuid'            
        ];
        if(!in_array($tablename, array_keys($privTable))){
            throw new \Exception('tablename参数错误', ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
        }
        $userDb = new SysUser();
        $entityData = $userDb->getEntity($tablename, $uuid);
        if(!$entityData){
            throw new \Exception('获取数据失败', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        $uuid = $entityData[$privTable[$tablename]];
        $result = $userDb->getUserIdForMainUuid($uuid);
        $userIds = [];
        if(!empty($result)){
            $tmpUserIds = [];
            foreach($result as $row){
                $tmpUserIds[] = $row['user_id'];
            }
            if(!$user_id){
                $userIds = array_intersect([$user_id], $tmpUserIds);
            }else{
                $userIds = $tmpUserIds;
            }
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['user_ids'=>$userIds]);
    }
}