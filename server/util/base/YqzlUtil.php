<?php
/**
 * 银企直联相关调用相关
 * @author sun
 *
 */
namespace money\base;
class YqzlUtil {
	
	const BASE_URL = '/yqzl/findBasedata'; 
	const BALANCE_QUERY_URL = '/yqzl/searchBalance';
	const PAY_RESULT_SEARCH_URL = '/yqzl/searchResult';
	const NOTICE_URL = '';

	//银企提交到银行失败
	const SUBMIT_FAIL_CODE = 'C001';
	//银企提交到银行成功
	const SUBMIT_SUCC_CODE = 'C002';

	const SUCCESS_CODE = 'C003';
	const FAIL_CODE = 'C004';
	const ERROR_APPLY_CODE = 'C005';
	public $m_logger = null;
	
	public function __construct(){
		$this->m_logger  = $this->getLogger();
	}
	
	protected function getLogger(){
		if(empty($this->m_logger)){
			$this->m_logger = \CommonLog::instance()->getDefaultLogger();
			$this->m_acc_logger = \CommonLog::instance()->getAccLogger();
		}
		return $this->m_logger;
	}
	
	public function call($url , $params){
		$ret = \JmfUtil::call_Jmf_consumer($url, $params);
		$obj = json_decode($ret);
		if(empty($obj)||!is_array($obj)){
			throw new \Exception('error response|ret:'.$ret);
		}
		if(!isset($obj['isSucceed'])||$obj['isSucceed']!=0){
			throw new \Exception('error response'.isset($obj['msg'])?'|msg:'.$obj['msg']:'');
		}
		
		return isset($obj['result'])?$ret['result']:'';
	}
	
	public function getBaseInfo(){
		$ret = $this->call(self::BASE_URL, array());
	}
	
	public function balanceQuery($acctid , $bankType , $trnuId , $bbknbr){
		if(empty($acctid)||empty($bankType)||empty($trnuId)||($acctid=='2'&&empty($bbknbr))){
			throw new \Exception('params empty error');
		}
		$params = array(
			'acctid'=>$acctid,
			'bankType'=>$bankType,
			'trnuId'=>$trnuId,
		);
		if($bbknbr){
			$params['bbknbr'] = $bbknbr;
		}
		$ret = $this->call(self::BALANCE_QUERY_URL , $params);
		return $ret;
	}
	
	public function payResultSearch($bank , $trnuId , $bgndat , $enddat){
		if(empty($bank)||empty($trnuId)||empty($bgndat)||empty($enddat)){
			throw new \Exception('params empty error');
			}
		
		$params = array(
			'bank'=>$bank,
			'trnuId'=>$trnuId,
			'bgndat'=>$bgndat,
			'enddat'=>$enddat
		);
		$ret = $this->call(self::PAY_RESULT_SEARCH_URL , $params);
		return $ret;
	}
	
	public function pay($payInfo){
		$keys = ['bank','noticeUrl','applyId','fromAcctId','toAcctId','toName', 
			'toBankDesc','toInterBank','toLocal','cursym',
			'trnAmt','jmgUserName',
			'jmgPassWord','purPose'];
		if(ParamsUtil::validateParams($payInfo,$keys)!=0){
			throw new \Exception('params empty error',\ErrMsg::RET_CODE_MISS_PARAMS);
		}
		
		$signStr = $payInfo['bank'].$payInfo['applyId'].$payInfo['fromAcctId'].$payInfo['toAcctId']
			.$payInfo['trnAmt'];
		$payInfo['sign'] = RSAUtil::publicEncrypt($signStr); 
		$this->m_logger->info(json_encode($payInfo));
		
		$obj = \JmfUtil::call_Jmf_consumer("com.jyblife.banklink.action.service.Pay", $payInfo);

		if(false == $obj || !isset($obj['code'])||$obj['code']!=0){
			return ['status'=>YqzlUtil::SUBMIT_FAIL_CODE,'err_msg'=> (string) $obj['data']['errorMes']];
		}
		
		return isset($obj['data'])?$obj['data'] : [];
	}
}

?>