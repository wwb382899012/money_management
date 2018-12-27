<?php
use money\service\BaseService;

class Test extends BaseService
{
	//单元测试
	function exec(){
		$type = $this->m_request["type"];
		switch ($type){
			case 1:
				// 1、下一个借款订单
				$ret = $this->Start();
				break;
			case 2:
				// 2、指令状态查询
				$ret = $this->Approve();
				break;
			case 3:
				// 3、审批详情
				$ret = $this->DetailList();
				break;
			case 4:
				// 4、获取用户token
				$ret = $this->getSessionToken();
				break;
			default:
				$ret = ["error,type not exists!"];
				break;
		}

		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
	
	function Start(){
		$params = array(
				"flow_code"=>"pay_order",
				"instance_id"=>'111',
				"info"=>"aabb",
				"sessionToken"=>$this->m_request['sessionToken']
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.Start" , $params);
		return $ret;
	}
	
	function Approve(){
		$params = array(
				"flow_code"=>"pay_order",
				"instance_id"=>time(),
				"info"=>"aabb",
				"approve_type"=>"1",
				"json_params"=>'{"test":"111"}',
				"sessionToken"=>$this->m_request['sessionToken']
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.Approve" , $params);
		return $ret;
	}
	
	function DetailList(){
		$params = array(
				"sessionToken"=>$this->m_request['sessionToken']
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.DetailList" , $params);
		return $ret;
	}
	
	function getSessionToken(){
		$params = array(
				"sessionToken"=>$this->m_request['sessionToken']
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.DetailList" , $params);
	}
}
