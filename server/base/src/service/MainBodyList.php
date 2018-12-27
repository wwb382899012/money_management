<?php
/**
 * 	交易主体列表
 * 	@author sun
 *	@since 2018-03-28
 */
use money\service\BaseService;
use money\model\MainBody;
use money\model\SysUser;

class MainBodyList extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'status' => 'integer',
        'page' => 'integer',
        'limit' => 'integer',
    ];

	public function exec(){
		$params = $this->m_request;

        //获取用户信息
        $sessionInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$this->m_request['sessionToken']]);
        if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
            $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : ErrMsg::RET_CODE_SERVICE_FAIL;
            $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
            throw new \Exception($msg, $code);
        }

		$queryArray = array(
				'name'=>$this->getDataByArray($params, 'name'),
				'status'=>$this->getDataByArray($params, 'status'),
				'uuid'=>$this->getDataByArray($params, 'uuid'),
				'is_internal'=>$this->getDataByArray($params, 'is_internal'),
		);
		$obj = new MainBody();
		$ret = $obj->details($queryArray,' * '
				,$this->getDataByArray($params, 'page'),$this->getDataByArray($params, 'limit'));

		//获取用户的主体
        $userModel = new SysUser();
        $userMainBodyData = $userModel->table("m_sys_user_main_body")
            ->where(['user_id' => $sessionInfo['data']['user_id']])->select();

        $ret['userMainBodyData'] =  empty($userMainBodyData) ? [] : array_column($userMainBodyData->toArray(), "main_body_uuid");

		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}
