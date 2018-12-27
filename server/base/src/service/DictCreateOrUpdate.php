<?php
/**
 * 数据字典新增或删除
 * @author sun
 * @since 2018-03-28
 */
use money\service\BaseService;
use money\model\DataDict;
use money\model\DataDictKv;

class DictCreateOrUpdate extends BaseService{
	
	protected $rule = array(
        'sessionToken'=>'require',
        'dict_type'=>'require',
        'dict_desc'=>'require',
        'dict_key'=>'require',
        'dict_value'=>'require',
        'index'=>'require|integer',
	);

	public function exec(){
		//获取用户信息
		$sessionInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$this->m_request['sessionToken']]);
        if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
            $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : ErrMsg::RET_CODE_SERVICE_FAIL;
            $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
            throw new \Exception($msg, $code);
        }
	
		//判断是否存在数据字典
		if(!isset($this->m_request['uuid'])){
			$obj = new DataDict();
			$data = $obj->getDataByType($this->m_request['dict_type']);
			if(empty($data)){
				$params = array(
					'dict_type'=>$this->m_request['dict_type'],
					'dict_desc'=>$this->m_request['dict_desc'],
					'create_user_id'=>$sessionInfo['data']['user_id'],
					'create_user_name'=>$sessionInfo['data']['username']
				);
                $obj->params = $params;
                $dictUuid = $obj->saveOrUpdate();
			} else {
                $dictUuid = $data['uuid'];
            }
		}
		
		$obj = new DataDictKv();
		$params = array(
				'dict_key'=>$this->m_request['dict_key'],
				'dict_value'=>$this->m_request['dict_value'],
				'index'=>$this->m_request['index'],
		);
		if(isset($this->m_request['uuid'])){
			$params['uuid'] = $this->m_request['uuid'];
		}
		if (isset($dictUuid)) {
            $params['dict_uuid'] = $dictUuid;
        }
		$params['create_user_id'] = $sessionInfo['data']['user_id'];
		$params['create_user_name'] = $sessionInfo['data']['username'];
		$obj->params = $params;
		$obj->saveOrUpdate();

		$this->packRet(ErrMsg::RET_CODE_SUCCESS, null);
	}
}