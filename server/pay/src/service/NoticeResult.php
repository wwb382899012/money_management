<?php
/**
 * 打款结果通知
 * @author sun
 *
 */
use money\service\BaseService;
use money\model\SysTradeWater;
use money\base\YqzlUtil;
class NoticeResult extends BaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'status' => 'require',
        'trnuId' => 'require',
        //'serialId' => 'require',
        'applyId' => 'require'
    ];

	public function exec(){
		$obj = new SysTradeWater();
		$params = array(
			'order_uuid'=>$this->m_request['applyId'],
			'trnuId'=>$this->m_request['trnuId']
		);
		$rets = $obj->loadDatas($params);
		$waterData = $rets[0];

		if($waterData['status']==SysTradeWater::STATUS_WAIT_PAY||$waterData['status']==SysTradeWater::STATUS_PAYING){
			if($this->m_request['status']==YqzlUtil::SUCCESS_CODE){
				$status = SysTradeWater::STATUS_SUCCESS;
			}else if($this->m_request['status']==YqzlUtil::FAIL_CODE){
				$status = SysTradeWater::STATUS_FAIL;
			}else if($this->m_request['status']==YqzlUtil::ERROR_APPLY_CODE){
				$status = SysTradeWater::STATUS_WAIT_CONFIRM;
			}else{
				$this->packRet(ErrMsg::RET_CODE_SUCCESS);
				return;
			}		
			$params = array();
			$params['uuid'] = $waterData['uuid'];
			$params['status'] = $status;
			$parmas['out_water_no'] = isset($this->m_request['serialId'])?$this->m_request['serialId']:'';
			if(isset($this->m_request['desc'])){
				$params['err_msg'] = $this->m_request['desc'];
			}
			$params['update_time'] = date('Y-m-d H:i:s');
			$obj->params = $params;
			$obj->saveOrUpdate();
		}else{
            $this->packRet(ErrMsg::RET_CODE_SUCCESS);
            return;
        }
        if(!empty($waterData['notice_url'])){
			$req = array(
					'order_uuid'=>$waterData['order_uuid'],
					'status'=>$status,
					'err_msg'=>isset($this->m_request['msg'])?$this->m_request['msg']:'',
					'serialId'=>isset($this->m_request['serialId'])?$this->m_request['serialId']:''
			);
				
			$ret = JmfUtil::call_Jmf_consumer($waterData['notice_url'], $req);
			if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
				$params = array();
				$params['uuid'] = $waterData['uuid'];
				$params['status'] = SysTradeWater::STATUS_PAYING;
				$obj->params = $params;
				$obj->saveOrUpdate();
				throw new Exception('回调失败',ErrMsg::RET_CODE_SERVICE_FAIL);
			}
		}

		$this->packRet(ErrMsg::RET_CODE_SUCCESS);
	}
}