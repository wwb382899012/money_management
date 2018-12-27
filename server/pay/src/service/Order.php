<?php
/** 
 * 打款封装
 * @author sun
 *
 */
use money\service\BaseService;
use money\model\SysTradeWater;
use money\base\YqzlUtil;
use money\base\MapUtil;
use money\model\BankAccount;

class Order extends BaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'trade_type' => 'require',
        'order_uuid' => 'require',
        'pay_bank_account' => 'require',
        'collect_bank_account' => 'require',
        'to_name' => 'require',
        'to_bank_desc' => 'require',
        'to_bank' => 'require',
        'to_city_name'=>'require',
        'notice_url' => 'require',
        'jmgUserName' => 'require',
        'jmgPassWord' => 'require',
        'amount' => 'require|number'
    ];

	public function exec(){
		$obj = new SysTradeWater();
		//调用接口不能放到事务里，以防commit失败导致多次提交
		//1 锁定单号，如果锁定失败返回异常
		$params = array();
		$params['order_uuid'] = $this->m_request['order_uuid'];
		$obj->params = $params;
		$lock = $obj->getLock();
		if(isset($lock)&&isset($lock['uuid'])){
			//锁定失败，当前数据已打款或者重复调用中
			$this->packRet(ErrMsg::RET_CODE_SUCCESS, ['uuid'=>$lock['uuid']]);
			return;
		}
		try{
			$obj->startTrans();
			$pay_q = array(
					'bank_account'=>$this->m_request['pay_bank_account']
			);
			$account = new BankAccount();
			$pay_account_info = $account->loadDatas($pay_q);
			$collect_account_info = $account->loadDatas(['bank_account'=>$this->m_request['collect_bank_account']]);
//			$water = new SysTradeWater();
// 			$ret = $water->loadDatas(array('order_uuid'=>$this->m_request['order_uuid']));
			
// 			if(is_array($ret)&&count($ret)>0){
// 				$params['applyId'] = $ret[0]['applyId'];
// 			}else{
// 				$params['applyId'] = SysTradeWater::getApplyId();
// 			}
			//2 保存数据，状态为等待提交
			$params['trade_type'] = $this->m_request['trade_type'];
			$params['order_uuid'] = $this->m_request['order_uuid'];
			//$params['trnuId'] = SysTradeWater::getTrnuId();
			$params['pay_account_uuid'] = $pay_account_info[0]['uuid'];
			$params['pay_bank_key'] = $pay_account_info[0]['bank_dict_key'];
			$params['pay_bank_account'] = $pay_account_info[0]['bank_account'];
			$params['collect_bank_account'] = $this->m_request['collect_bank_account'];
			$params['to_name'] = $this->m_request['to_name'];
			$params['to_bank_desc'] = $this->m_request['to_bank_desc'];
			$params['to_bank'] = $this->m_request['to_bank'];
			$params['to_city_name'] = $this->m_request['to_city_name'];
			
			$params['notice_url'] = $this->m_request['notice_url'];
			$params['amount'] = number_format($this->m_request['amount']/100,2,".", "");
			$params['currency'] = isset($this->m_request['currency'])?$this->m_request['currency']:'CYN';
			$params['is_effective'] = SysTradeWater::STATUS_EFFECT;
			$params['status'] = SysTradeWater::STATUS_PAYING;
// 			$params['jmgUserName'] = $this->m_request['jmgUserName'];
			$uuid = $obj->addWater($params);
 
			
			//调用接口
			$toInterBank = $pay_account_info[0]['bank_dict_key']==$this->m_request['to_bank']?'Y':'N';
			$toLocal = $pay_account_info[0]['city_name']==$this->m_request['to_city_name']?'Y':'N';
			$purPose = (isset($this->m_request['pay_remark']) && !empty($this->m_request['pay_remark'])) ?
                $this->m_request['pay_remark'] : MapUtil::getValByKey('pay_type', $this->m_request['trade_type']);
			
			$payInfo = array(
					'bank'=>$pay_account_info[0]['bank_dict_key'],
					'noticeUrl'=>'com.jyblife.logic.bg.pay.NoticeResult',
					'applyId'=>$params['order_uuid'],
					'fromAcctId'=>$params['pay_bank_account'],
					'fromName'=>$pay_account_info[0]['account_name'],
					'toAcctId'=>$params['collect_bank_account'],
					'toName'=>$params['to_name'],
					'toBankDesc'=>$params['to_bank_desc'],
					'toBankNum'=>$this->m_request['to_bank_num'] ?? '',
					'toInterBank'=>$toInterBank,
					'toLocal'=>$toLocal,
					'cursym'=>$params['currency']=='CYN'?'RMB':'',//等待给其他货币类型列表
					'trnAmt'=>$params['amount'],
					'jmgUserName'=>$this->m_request['jmgUserName'],
					'jmgPassWord'=>$this->m_request['jmgPassWord'],
					'purPose'=>$purPose
			);
			
			if($pay_account_info[0]['bank_dict_key']==2){
				//招行需要付方开户地区代码
				$payInfo['dbtbbk'] = $pay_account_info[0]['area'];
				$payInfo['crtadr'] = $collect_account_info[0]['address'];
			}
				
			$u = new YqzlUtil();
			$ret = $u->pay($payInfo);
			//成功编码待修改===============================
			if($ret && isset($ret['status'])){
			    if ($ret['status']== YqzlUtil::SUBMIT_SUCC_CODE) {
                    $obj = new SysTradeWater();
                    $obj->params['uuid'] = $uuid;
                    //调用结果编号写入===============================
                    $obj->params['trnuId'] = $ret['trnuId'];
                    // 			$obj->parmas['out_water_no'] = $ret['serialId'];
                    $obj->saveOrUpdate();
                    $obj->unLock();

                    $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['uuid' => $uuid]);
                } else {
                    $obj = new SysTradeWater();
                    $obj->params['uuid'] = $uuid;
                    $obj->params['status'] = ($ret['status'] == YqzlUtil::ERROR_APPLY_CODE) ? SysTradeWater::STATUS_WAIT_CONFIRM : SysTradeWater::STATUS_FAIL;
                    $obj->params['err_msg'] = (string) ($ret['err_msg'] ?? ($ret['desc'] ?? ""));
                    $obj->saveOrUpdate();
                    $obj->unLock();

                    $this->packRet(ErrMsg::RET_CODE_SERVICE_FAIL, ['uuid'=>$uuid, 'status' => $obj->params['status'], 'err_msg' => $obj->params['err_msg']]);
                }
			}else{
			    throw new Exception("银企付款请求失败");
			}
			$obj->commit();
			
					
		}catch(Exception $e){
			$obj->rollback();
			$obj->unLock();
			throw new Exception($e->getMessage(),ErrMsg::RET_CODE_SERVICE_FAIL);
		}
		
		
	}
}