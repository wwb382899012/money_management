<?php
use money\service\BaseService;
use money\model\SystemInfo;
class OrderBaseService extends BaseService
{
	public $array = array();
	function CheckIn()
	{
	    parent::CheckIn();
		$this->secretValidate($this->m_request);
	}

	function getSysInfo($flag)
	{
		$sys = SystemInfo::getSystemInfoByFlag($flag);
		if(!$sys){
			throw new Exception("调用系统业务编码不存在"
				,ErrMsg::RET_CODE_SYS_CODE_EMPTY);
		}
	}

	function secretValidate($params)
	{
		$secret = $params['secret'];
		if(!$secret){
			throw new Exception('签名值不能为空！',ErrMsg::RET_CODE_SECERT_EMPTY);
		}
		if(!$params['timestamp'])
		{
			throw new Exception('时间戳不能为空！',ErrMsg::RET_CODE_MISS_PARAMS);
		}
		if(!$params['system_flag'])
		{
			throw new Exception('业务系统标示不能为空！',ErrMsg::RET_CODE_MISS_PARAMS);
		}

		$sys = $this->getSysInfo($params['system_flag']);
		$n_secret = secretGet($params, $sys['pwd_key']);

		if($secret!=$n_secret)
		{
			throw new Exception('签名验证不通过！',ErrMsg::RET_CODE_SECERT_VALIDATE_ERROR);
		}
		return;
	}
}
