<?php
/**
 * 	交易主体新增或更新
 * 	@author sun
 *	@since 2018-03-28
 */
use money\service\BaseService;
use money\model\BankAccount;
use money\model\MainBody;

class MainBodyCreateOrUpdate  extends BaseService{
	
	protected $rule = [
        'sessionToken'=>'require',
        'short_name'=>'require',
        'full_name'=>'require',
        'is_internal'=>'require|integer'
    ];

	public function exec(){
		//获取用户信息
		$sessionInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$this->m_request['sessionToken']]);
        if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
            $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : ErrMsg::RET_CODE_SERVICE_FAIL;
            $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
            throw new \Exception($msg, $code);
        }

        if($this->m_request['is_internal']==1&&!isset($this->m_request['short_code'])){
            throw new Exception('内部主体简码不能为空！', ErrMsg::RET_CODE_DATA_NOT_EXISTS);
        }

        $obj = new MainBody();
        if($obj->validateDulicate($this->m_request['full_name'],$this->m_request['short_name'],$this->m_request['short_code'],
        		isset($this->m_request['uuid'])?$this->m_request['uuid']:null)){
        	throw new Exception('主体全称、简称和简码不能重复！', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
        }
        
		$params = array(
			'short_name'=>$this->m_request['short_name'],
			'full_name'=>$this->m_request['full_name'],
			'short_code'=>$this->m_request['short_code'],
			'is_internal'=>$this->m_request['is_internal']
		);
		if(isset($this->m_request['uuid'])){
			$params['uuid'] = $this->m_request['uuid'];
            $obj->pushAccountMsgToMq($this->m_request['uuid']);
		}
		$params['create_user_id'] = $sessionInfo['data']['user_id'];
		$params['create_user_name'] = $sessionInfo['data']['username'];
		$obj->params = $params;
		$ret = $obj->saveOrUpdate();

        // 新增成功
		if ($ret && !isset($this->m_request['uuid'])) {
		    // 外部主体
            if (MainBody::TYPE_OUTSIDE == $params['is_internal']) {
                // 发布新增外部主体消息
                $amqpUtil = new \AmqpUtil();
                $ex = $amqpUtil->exchange(MQ_EXCHANGE_MAINBODY);
                $ex->publish(json_encode(['uuid' => $ret]), MQ_ROUT_MAINBODY_ADD);
            }
        }
		
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, null);
	}
}