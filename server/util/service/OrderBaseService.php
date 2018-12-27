<?php
namespace money\service;
use money\service\BaseService;
use money\model\SystemInfo;
use money\base\RSAUtil;

class OrderBaseService extends BaseService
{
	function CheckIn()
	{
// 	    parent::CheckIn();
	    //根据版本号判断使用哪个接口调用验证
	    //version=1.0则使用旧逻辑
	    //version=2.0使用rsa加密相关逻辑
		\CommonLog::instance()->getDefaultLogger()->info(json_encode($this->m_request));
	    if(isset($this->m_request['version'])){
	    	if($this->m_request['version']!='2.0'){
	    		throw new \Exception('版本号错误',\ErrMsg::RET_CODE_VERSION_VALIDATE_ERROR);
	    	}
	    	if(!isset($this->m_request['system_flag']))
	    	{
	    		throw new \Exception('业务系统标示不能为空！',\ErrMsg::RET_CODE_MISS_PARAMS);
	    	}
	    	$sys = $this->getSysInfo($this->m_request['system_flag']);
	    	
	    	$rsa_private_key = $sys['private_key'];
	    	if(!isset($this->m_request['secret'])){
	    		throw new \Exception('加密串不能为空！',\ErrMsg::RET_CODE_MISS_PARAMS);
	    	}
	    	$u = new RSAUtil();
	    	$params = json_decode($u->privateDecrypt($this->m_request['secret'] , $rsa_private_key),true);
	    	if(empty($params)){
	    		throw new \Exception('加密串解析错误',\ErrMsg::RET_CODE_SECRET_VALIDATE_ERROR);
	    	}
	    	
	    	$this->m_request = array_merge($this->m_request , $params);
	    	$this->secretValidate($params);
	    }
	    else{
	    	$this->secretValidate($this->m_request);
	    }
	    parent::CheckIn();
	}

	function getSysInfo($flag)
	{
		
		$sys = SystemInfo::getSystemInfoByFlag($flag);
		if(!$sys){
			throw new \Exception("调用系统业务编码不存在" ,\ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		return $sys;
	}

	function secretValidate($params)
	{
		$secret = $params['secret'];
		if(!$secret){
			throw new \Exception('签名值不能为空！',\ErrMsg::RET_CODE_SECERT_EMPTY);
		}
		if(!$params['timestamp'])
		{
			throw new \Exception('时间戳不能为空！',\ErrMsg::RET_CODE_MISS_PARAMS);
		}
		if(!$params['system_flag'])
		{
			throw new \Exception('业务系统标示不能为空！',\ErrMsg::RET_CODE_MISS_PARAMS);
		}

		$sys = $this->getSysInfo($params['system_flag']);
		$n_secret = secretGet($params, $sys['pwd_key']);

		if($secret!=$n_secret)
		{
			throw new \Exception('签名验证不通过！',\ErrMsg::RET_CODE_SECERT_VALIDATE_ERROR);
		}
		return;
	}
}
