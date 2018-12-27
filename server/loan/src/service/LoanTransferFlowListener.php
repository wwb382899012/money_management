<?php
/**
 *	借款调拨审批流结果监听
 *	@author sun
 *	@since 2018-03-11
 */
use money\service\BaseService;
use money\model\SysTradeWater;
use money\model\SysUser;
use money\model\LoanTransfer;
use money\model\BankAccount;
use money\model\EodTradeDb;
use money\model\MainBody;
use money\model\ReportFullTrade;
use money\model\LoanCashFlow;
use money\model\LoanOrder;
use money\model\Repay;

class LoanTransferFlowListener extends BaseService {
	protected $rule = [ 
			// 'sessionToken' => 'require',
			'instance_id' => 'require',
			'flow_code' => 'require',
			'node_code' => 'require',
			'node_status' => 'require|integer' 
	];
	public function exec() {
		$obj = new LoanTransfer();
		try {
			$obj->startTrans();
			if ($this->m_request ['flow_code'] == 'loan_transfer_pay_type_1_code') {
				switch ($this->m_request ['node_code']) {
					case "Loan_transfer_begin_wy" :
						// 资金专员
						$params = array ();
						$params ['uuid'] = $this->m_request ['instance_id'];
						if (! isset ( $this->m_request ['params'] ['real_pay_type'] )) {
							throw new \Exception ( '实付类型不能为空', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
						}
						$params ['real_pay_type'] = $this->m_request ['params'] ['real_pay_type'];
						
						if (isset ( $this->m_request ['params'] ['annex_uuids'] )) {
							$params ['annex_uuids'] = $this->m_request ['params'] ['annex_uuids'];
						}
						
						if (! isset ( $this->m_request ['params'] ['loan_account_uuid'] )) {
							throw new Exception ( '打款账户不能为空', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
						}
						
						$oBankAmount = new BankAccount ();
						$params ['loan_account_uuid'] = $this->m_request ['params'] ['loan_account_uuid'];
						$loan_bank_account = $oBankAmount->getDataById ( $params ['loan_account_uuid'] );
						$params ['loan_bank_account'] = $loan_bank_account ['bank_account'];
						$params ['loan_account_name'] = $loan_bank_account ['account_name'];
						$params ['loan_bank_name'] = $loan_bank_account ['bank_name'];
						$params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_WAIT_MATSTER_OPT;
						$params ['forecast_datetime'] = $this->m_request ['params'] ['forecast_datetime'];
						
						$tranInfo = LoanTransfer::getDataById ( $this->m_request ['instance_id'] );
						if (isset ( $this->m_request ['params'] ['collect_account_uuid'] ) && ! empty ( $this->m_request ['params'] ['collect_account_uuid'] )) {
							$collect_account_uuid =  $this->m_request ['params'] ['collect_account_uuid'];
						}else if(isset($tranInfo['collect_account_uuid'])){
							$collect_account_uuid = $tranInfo['collect_account_uuid'];
						}
						if(isset($collect_account_uuid)){
							$collect_bank_account = $oBankAmount->getDataById ( $collect_account_uuid );
							$params ['collect_account_uuid'] = $collect_bank_account ['uuid'];
							$params ['collect_account_name'] = $collect_bank_account ['account_name'];
							$params ['collect_bank_account'] = $collect_bank_account ['bank_account'];
							// $params['collect_bank_desc'] = $collect_bank_account['bank_name'];
							$params ['collect_bank_name'] = $collect_bank_account ['bank_name'];
							// $params['collect_bank'] = $collect_bank_account['bank_dict_key'];
							// $params['collect_city_name'] = $collect_bank_account['city_name'];
						}
						
						$tran = new LoanTransfer ();
						$tran->params = $params;
						$tran->saveOrUpdate ();						
						
						
						//添加前清除所有历史数据
						$f = new LoanCashFlow();
						$ret = $f->loadDatas(['loan_transfer_uuid'=>$this->m_request['instance_id']],'uuid');
						if(is_array($ret)&&count($ret)>0){
							$ids = array_column($ret,'uuid');
							$f->where([['uuid','in',$ids]])->delete();
						}
						
						if (isset ( $this->m_request ['params'] ['cashDetail'] )) {
							$cashs = $this->m_request ['params'] ['cashDetail'];
							if (is_array ( $cashs )) {
								foreach( $cashs as $cash ){
									if($cash['index']==1){
										$loan_date = $params['repay_date'];
									}
								}
								foreach ( $cashs as $cash ) {
									if($cash['index']>1&&(strtotime($cash['repay_date'])<=strtotime($loan_date))){
										throw new Exception('还款日期不能早于借款日期！', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
									}
									$c = new LoanCashFlow ();
									$params = array ();
									$params ['loan_transfer_uuid'] = $this->m_request ['instance_id'];
									$params ['index'] = $cash ['index'];
									$params ['repay_date'] = $cash ['repay_date'];
									$params ['cash_flow_type'] = $cash ['cash_flow_type'];
									$params ['amount'] = $cash ['amount'];
									$params ['real_amount'] = $cash ['real_amount'];
									$params ['info'] = $cash ['info'];
									$c->params = $params;
									$c->saveOrUpdate();
								}
							}
						}
						EodTradeDb::dataUpdate($this->m_request['instance_id'], 4
						, ['limit_date'=>$tranInfo['loan_datetime'],'transfer_create_time'=>date('Y-m-d H:i:s')]);
						break;
					case "Loan_transfer_approve_wy_1" :
						$params = array ();
						$params ['uuid'] = $this->m_request ['instance_id'];
						$tran = new LoanTransfer ();
						if ($this->m_request ['node_status'] == 3) {
							$params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_WAITING;
							$tran->params = $params;
							$tran->saveOrUpdate ();
							
						}else if ($this->m_request ['node_status'] == 2) {
							$params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_OPTED;
							$tran->params = $params;
							$tran->saveOrUpdate ();
						}
						break;
					case "Loan_transfer_approve_wy_2" :
						// 权签人
						$params = array ();
						$params ['uuid'] = $this->m_request ['instance_id'];
						$tran = new LoanTransfer ();
						if ($this->m_request ['node_status'] == 3) {
							$params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_WAITING;
							$tran->params = $params;
							$tran->saveOrUpdate ();
							//删除现金流表
							
						} else if ($this->m_request ['node_status'] == 2) {
							$date = date ( 'Y-m-d' );
							$params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_ARCHIVE;
							$params ['loan_status'] = LoanTransfer::LOAN_STATUS_PAID;
							$params ['real_pay_date'] = $date;
							$params['need_ticket_back'] = 1;
							$tran->params = $params;
							$tran->saveOrUpdate ();
							
							$tranInfo = LoanTransfer::getDataById ( $this->m_request ['instance_id'] );
							$order = new LoanOrder ();
							$params = array ();
							$params ['uuid'] = $tranInfo ['loan_order_uuid'];
							$params ['loan_status'] = LoanOrder::LOAN_STATUS_PAID;
							$params ['order_status'] = LoanOrder::ORDER_STATUS_ARCHIVE;
							$params ['real_pay_date'] = $date;
							$order->params = $params;
							$order->saveOrUpdate();
							
							// 现金流表写入
							$oBankAmount = new BankAccount ();
							$pay_bank_account = $oBankAmount->getDataById ( $tranInfo ['loan_account_uuid'] );
							
							$obj = new SysTradeWater ();
							// 2 保存数据，状态为等待提交
							$params = array ();
							$params ['trade_type'] = 15;
							$params ['order_uuid'] = $tranInfo ['transfer_num'];
							
							// $params['trnuId'] = SysTradeWater::getTrnuId();
							$params ['pay_account_uuid'] = $pay_bank_account ['uuid'];
							$params ['pay_bank_key'] = $pay_bank_account ['bank_dict_key'];
							$params ['pay_bank_account'] = $pay_bank_account ['bank_account'];
							$params ['collect_bank_account'] = $tranInfo ['collect_bank_account'];
							$params ['to_name'] = $tranInfo ['collect_account_name'];
							$params ['to_bank_desc'] = $tranInfo ['collect_bank_account'];
							$params ['to_bank'] = $tranInfo ['collect_bank'];
							$params ['to_city_name'] = $tranInfo ['collect_city_name'];
							
							$params ['amount'] = $tranInfo ['amount'];
							$params ['currency'] = 'CYN';
							$params ['is_effective'] = SysTradeWater::STATUS_EFFECT;
							$params ['status'] = SysTradeWater::STATUS_SUCCESS;
							$uuid = $obj->addWater ( $params );
							
							$tran->params = [ 
									'uuid' => $tranInfo ['uuid'],
									'water_uuid' => $uuid 
							];
							$tran->saveOrUpdate ();
							
							//更新当前现金流表状态
							$cashFlow = new LoanCashFlow();
							$c = $cashFlow->loadLoanCashFlowUuidByTransferUuid($tranInfo['uuid']);
							$cashFlow->params = [
								'uuid'=>$c['uuid'],
								'cash_status'=>LoanCashFlow::STATUS_PAID
							];
							$cashFlow->saveOrUpdate();
							
							$c = $cashFlow->field(' * ')->where(['loan_transfer_uuid'=>$tranInfo['uuid']])->order(['index'=>'asc'])->select()->toArray();
							if(!isset($c[0]['uuid'])){
								throw new Exception('现金流表数据错误',ErrMsg::RET_CODE_SERVICE_FAIL);
							}
							$amount = 0;
							$need_change = false;
							foreach($c as $o){
								//更新本金的实际付款日期
								if($o['cash_flow_type']==1){
									if($o['repay_date']!=$date){
										$need_change = true;
									}
									$cashFlow->params = [
										'uuid'=>$o['uuid'],
										'real_repay_date'=>$date
									];
									$cashFlow->saveOrUpdate();
								}else if($need_change&&$o['cash_flow_type']==3){
									$diff_date = ceil((strtotime($o['repay_date']) - strtotime($date))/86400);
									$real_amount = $diff_date<0?0:round ($tranInfo['amount']*$tranInfo['rate']* $diff_date / 365);
									$cashFlow->params = [
										'uuid'=>$o['uuid'],
										'real_amount'=>$real_amount,
										'amount'=>$real_amount
									];
									$cashFlow->saveOrUpdate();
									$o['real_amount'] = $real_amount;
								}
								
								if($o['cash_flow_type']!=1){
									$amount+= $o['real_amount'];
								}
							}
							
							//插入还款数据
							$repay = new Repay();
							$mainBody = MainBody::getDataById($tranInfo['collect_main_body_uuid']);
							$r_params = [
								'repay_status'=>Repay::CODE_REPAY_STATUS_WATING,
								'repay_transfer_status'=>Repay::CODE_REPAY_TRANSFER_STATUS_WAITING,
								'loan_transfer_uuid'=>$this->m_request['instance_id'],
								'repay_transfer_num'=>Repay::getOrderNum($mainBody['short_code']),
								'repay_main_body_uuid'=>$tranInfo['collect_main_body_uuid'],
								'collect_main_body_uuid'=>$tranInfo['loan_main_body_uuid'],
								'repay_account_uuid'=>$tranInfo['collect_account_uuid'],
								'collect_account_uuid'=>$tranInfo['loan_account_uuid'],
								'index'=>2,
								'currency'=>$tranInfo['currency'],
								'amount'=>$amount,
								'forecast_date'=>$tranInfo['forecast_datetime']
							];
							$repay->params = $r_params;
							$id = $repay->saveOrUpdate();
							
							$r = [
								'main_body_uuid'=>$mainBody['uuid'],
								'transfer_create_time'=>date('Y-m-d H:i:s'),
								'limit_date'=>$tranInfo['forecast_datetime'],
								'opt_uuid'=>$id,
								'trade_type'=>6
							];
							EodTradeDb::dataCreate($r);
							
							$tran->params = [
								'uuid'=>$tranInfo['uuid'],
								'cur_repay_id'=>$id
							] ;
							$tran->saveOrUpdate();
							
							EodTradeDb::dataOpted( $tranInfo ['uuid'], 4);
							$obj = new ReportFullTrade();
							$obj->saveData(3,$tranInfo['transfer_num']);
						} else if ($this->m_request ['node_status'] == 4) {
							// 审批拒绝
							// 更新指令、调拨的状态为拒绝，打款状态为打款拒绝
								$tran = new LoanTransfer();
								$tran->params = $params;
								$tran->params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_REFUSE;
								$tran->params ['loan_status'] = LoanTransfer::LOAN_STATUS_FAIL;
								$tran->saveOrUpdate ();
								EodTradeDb::dataOpted( $this->m_request ['instance_id'], 4);
								
								$tranInfo = LoanTransfer::getDataById ( $this->m_request ['instance_id'] );
								$order = new LoanOrder ();
								$order->params = [ 
										'uuid' => $tranInfo ['loan_order_uuid'],
										'loan_status' => LoanOrder::LOAN_STATUS_FAIL,
										'order_status' => LoanOrder::ORDER_STATUS_REFUSE
								];
								$order->saveOrUpdate ();
						}
						break;
					case "Loan_transfer_approve_wy_3" :
						// 付款
						$params = array ();
						$params ['uuid'] = $this->m_request ['instance_id'];
						$params ['bank_water'] = isset ( $this->m_request ['params'] ['bank_water'] ) ? $this->m_request ['params'] ['bank_water'] : '';
						$params ['bank_img_file_uuid'] = isset ( $this->m_request ['params'] ['bank_img_file_uuid'] ) ? $this->m_request ['params'] ['bank_img_file_uuid'] : '';
						$params['need_ticket_back'] = 0;
						$tran = new LoanTransfer ();
						$tran->params = $params;
						// if($this->m_request['node_status']==3){
						// $tranData = LoanTransfer::getDataById($this->m_request['instance_id']);
						
						// $order = new LoanOrder();
						// $order->params['uuid'] = $tranData['loan_order_uuid'];
						// $order->params['order_status'] = LoanOrder::ORDER_STATUS_REJECT;
						// $order->params['loan_status'] = LoanOrder::LOAN_STATUS_REFUSE;
						// $order->saveOrUpdate();
						
						// $tran = new LoanTransfer();
						// $tran->params['uuid'] = $this->m_request['instance_id'];
						// $tran->params['transfer_status'] = LoanTransfer::TRANSFER_STATUS_REJECT;
						// $tran->params['loan_status'] = LoanOrder::LOAN_STATUS_REFUSE;
						// $tran->saveOrUpdate();
						
						// $status = 4;
						// }else if($this->m_request['node_status']==4){
						// $tranData = LoanTransfer::getDataById($this->m_request['instance_id']);
						
						// $order = new LoanOrder();
						// $order->params['uuid'] = $tranData['loan_order_uuid'];
						// $order->params['order_status'] = LoanOrder::ORDER_STATUS_REFUSE;
						// $order->params['loan_status'] = LoanOrder::LOAN_STATUS_REFUSE;
						// $order->saveOrUpdate();
						
						// $tran = new LoanTransfer();
						// $tran->params['uuid'] = $this->m_request['instance_id'];
						// $tran->params['transfer_status'] = LoanTransfer::TRANSFER_STATUS_REFUSE;
						// $tran->params['loan_status'] = LoanOrder::LOAN_STATUS_REFUSE;
						// $tran->saveOrUpdate();
						
						// $status = 4;
						
						// }else if($this->m_request['node_status']==2){
						$tran->params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_ARCHIVE;
						$tran->saveOrUpdate ();
						
						$r = new ReportFullTrade();
						$reportInfo = $r->loadDatas(['trade_uuid'=>$this->m_request['instance_id']]);
						if(is_array($reportInfo)&&count($reportInfo)>0){
							$params = [
							'uuid'=>$reportInfo[0]['uuid'],
							'bank_water_no'=>isset($this->m_request['params']['bank_water']) ? $this->m_request['params']['bank_water'] : ''
							];
							$r->params = $params;
							$r->saveOrUpdate();
						}
						break;
					default :
						break;
				}
			} else if ($this->m_request ['flow_code'] == 'loan_transfer_pay_type_2_code') {
				// 付款银企
				switch ($this->m_request ['node_code']) {
					case "Loan_transfer_begin_yq" :
						// 资金专员
						$params = array ();
						$params ['uuid'] = $this->m_request ['instance_id'];
						if (! isset ( $this->m_request ['params'] ['real_pay_type'] )) {
							throw new \Exception ( '实付类型不能为空', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
						}
						$params ['real_pay_type'] = $this->m_request ['params'] ['real_pay_type'];
						$params ['pay_remark'] = $this->m_request ['params'] ['pay_remark'];

						if (isset ( $this->m_request ['params'] ['annex_uuids'] )) {
							$params ['annex_uuids'] = $this->m_request ['params'] ['annex_uuids'];
						}
						
						if (! isset ( $this->m_request ['params'] ['loan_account_uuid'] )) {
							throw new Exception ( '打款账户不能为空', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
						}
						
						$oBankAmount = new BankAccount ();
						$params ['loan_account_uuid'] = $this->m_request ['params'] ['loan_account_uuid'];
						$loan_bank_account = $oBankAmount->getDataById ( $params ['loan_account_uuid'] );
						$params ['loan_bank_account'] = $loan_bank_account ['bank_account'];
						$params ['loan_account_name'] = $loan_bank_account ['account_name'];
						$params ['loan_bank_name'] = $loan_bank_account ['bank_name'];
						$params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_WAIT_MATSTER_OPT;
						$params ['forecast_datetime'] = $this->m_request ['params'] ['forecast_datetime'];
						
						$tranInfo = LoanTransfer::getDataById ( $this->m_request ['instance_id'] );
						if (isset ( $this->m_request ['params'] ['collect_account_uuid'] ) && ! empty ( $this->m_request ['params'] ['collect_account_uuid'] )) {
							$collect_account_uuid =  $this->m_request ['params'] ['collect_account_uuid'];
						}else if(isset($tranInfo['collect_account_uuid'])){
							$collect_account_uuid = $tranInfo['collect_account_uuid'];
						}
						if(isset($collect_account_uuid)){
							$collect_bank_account = $oBankAmount->getDataById ( $collect_account_uuid );
							$params ['collect_account_uuid'] = $collect_bank_account ['uuid'];
							$params ['collect_account_name'] = $collect_bank_account ['account_name'];
							$params ['collect_bank_account'] = $collect_bank_account ['bank_account'];
							// $params['collect_bank_desc'] = $collect_bank_account['bank_name'];
							$params ['collect_bank_name'] = $collect_bank_account ['bank_name'];
							// $params['collect_bank'] = $collect_bank_account['bank_dict_key'];
							// $params['collect_city_name'] = $collect_bank_account['city_name'];
						}
						
						$tran = new LoanTransfer ();
						$tran->params = $params;
						$tran->saveOrUpdate ();
						
						if(in_array($loan_bank_account['bank_dict_key'],[4,5])){
							//平安、农行收款账号行号不能为空
							$tranInfo = LoanTransfer::getDataById ( $this->m_request ['instance_id'] );
							$collect_bank = $oBankAmount->getDataById($tranInfo['collect_account_uuid']);
							if(empty($collect_bank['bank_link_code'])){
								throw new Exception ( '付款银行为平安或农行，收款账号行号不能为空，请维护账号后再提交', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
							}
						}
						
						//添加前清除所有历史数据
						$f = new LoanCashFlow();
						$ret = $f->loadDatas(['loan_transfer_uuid'=>$this->m_request['instance_id']],'uuid');
						if(is_array($ret)&&count($ret)>0){
							$ids = array_column($ret,'uuid');
							$f->where([['uuid','in',$ids]])->delete();
						}
						if (isset ( $this->m_request ['params'] ['cashDetail'] )) {
							$cashs = $this->m_request ['params'] ['cashDetail'];
							if (is_array ( $cashs )) {
								foreach ( $cashs as $cash ) {
									$c = new LoanCashFlow ();
									$params = array ();
									$params ['loan_transfer_uuid'] = $this->m_request ['instance_id'];
									$params ['index'] = $cash ['index'];
									$params ['repay_date'] = $cash ['repay_date'];
									$params ['cash_flow_type'] = $cash ['cash_flow_type'];
									$params ['amount'] = $cash ['amount'];
									$params ['real_amount'] = $cash ['real_amount'];
									$params ['info'] = $cash ['info'];
									$c->params = $params;
									$c->saveOrUpdate ();
								}
							}
						}
						EodTradeDb::dataUpdate($this->m_request['instance_id'], 4
						, ['limit_date'=>$tranInfo['loan_datetime'],'transfer_create_time'=>date('Y-m-d H:i:s')]);
						break;
					case "Loan_transfer_approve_yq_1" :
						$params = array ();
						$params ['uuid'] = $this->m_request ['instance_id'];
						$tran = new LoanTransfer ();
						if ($this->m_request ['node_status'] == 3) {
							$params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_WAITING;
							$tran->params = $params;
							$tran->saveOrUpdate ();
							//删除现金流表
						}else if ($this->m_request ['node_status'] == 2) {
							$params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_OPTED;
							$tran->params = $params;
							$tran->saveOrUpdate ();
						}
						break;
					case "Loan_transfer_approve_yq_2" :
						// 权签人
						$params = array ();
						$params ['uuid'] = $this->m_request ['instance_id'];
						$tran = new LoanTransfer ();
						
						if ($this->m_request ['node_status'] == 3) {
							$params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_WAITING;
							$tran->params = $params;
							$tran->saveOrUpdate ();
						} else if ($this->m_request ['node_status'] == 2) {
							$this->transferOpt ( $this->m_request );
						} else if ($this->m_request ['node_status'] == 4) {
							// 审批拒绝
							// 更新指令、调拨的状态为拒绝，打款状态为打款拒绝
							$tran->params = $params;
							$tran->params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_REFUSE;
							$tran->params ['loan_status'] = LoanTransfer::LOAN_STATUS_FAIL;
							$tran->saveOrUpdate ();
							
							$tranInfo = LoanTransfer::getDataById ( $this->m_request ['instance_id'] );
							$order = new LoanOrder ();
							$order->params = [ 
									'uuid' => $tranInfo ['loan_order_uuid'],
									'loan_status' => LoanOrder::LOAN_STATUS_FAIL,
									'order_status' => LoanOrder::ORDER_STATUS_REFUSE
							];
							$order->saveOrUpdate ();
							
							EodTradeDb::dataOpted( $tranInfo ['uuid'], 4);
						}
						break;
					case "Loan_transfer_approve_yq_3" :
// 						// 驳回和拒绝都要调用流程拒绝接口。只是数据修改状态不同。
// 						if ($this->m_request ['node_status'] == 3) {
// 							$tran = new LoanTransfer ();
// 							$tran->params ['uuid'] = $this->m_request ['instance_id'];
// 							$tran->params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_COMFIRMED;
// 							$tran->params ['loan_status'] = LoanTransfer::LOAN_STATUS_UNPAID;
// 							$tran->saveOrUpdate ();
// 						} else if ($this->m_request ['node_status'] == 4) {
// 							$tran = new LoanTransfer ();
// 							$tran->params ['uuid'] = $this->m_request ['instance_id'];
// 							$tran->params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_REFUSE;
// 							$tran->params ['loan_status'] = LoanTransfer::LOAN_STATUS_FAIL;
// 							$tran->saveOrUpdate ();
							
// 							$tranInfo = LoanTransfer::getDataById ( $this->m_request ['instance_id'] );
// 							$order = new LoanOrder ();
// 							$order->params = [ 
// 									'uuid' => $tranInfo ['loan_order_uuid'],
// 									'loan_status' => LoanOrder::LOAN_STATUS_FAIL,
// 									'order_status' => LoanOrder::ORDER_STATUS_REFUSE
// 							];							
// 							$order->saveOrUpdate ();
							
// 							EodTradeDb::dataOpted( $tranInfo ['uuid'], 4);
// 						}
						break;
					default :
						break;
				}
			}
			$obj->commit ();
		} catch ( Exception $e ) {
			$obj->rollback ();
			CommonLog::instance()->getDefaultLogger()->info('loantransfer flow listener error|msg:'.$e->getMessage());
			throw new Exception($e->getMessage(),$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
// 			throw $e;
		}
		$this->packRet ( ErrMsg::RET_CODE_SUCCESS, null );
		$this->msgOpt ();
	}
	public function transferOpt($params) {
		$uuid = $params ['instance_id'];
		$date = date('Y-m-d');
		$tran = new LoanTransfer ();
		$tran->params ['uuid'] = $uuid;
		$tran->params['real_pay_date'] = $date;
		$tran->params ['transfer_status'] = LoanTransfer::TRANSFER_STATUS_COMFIRMED;
		$tran->params ['loan_status'] = LoanTransfer::LOAN_STATUS_PAYING;
		$tran->saveOrUpdate ();
		
		$tranInfo = $tran->getDataById ( $uuid );
		$order = new LoanOrder();
		$order->params = ['uuid'=>$tranInfo['loan_order_uuid'],'real_pay_date'=>$date];
		$order->saveOrUpdate();
		

		$cashFlow = new LoanCashFlow();
		$c = $cashFlow->field(' * ')->where(['loan_transfer_uuid'=>$tranInfo['uuid']])->order(['index'=>'asc'])->select()->toArray();
		if(!isset($c[0]['uuid'])){
			throw new Exception('现金流表数据错误',ErrMsg::RET_CODE_SERVICE_FAIL);
		}
		$amount = 0;
		$need_change = false;
		
		foreach($c as $o){
			//更新本金的实际付款日期
			if($o['cash_flow_type']==1){
				$cashFlow->params = [
					'uuid'=>$o['uuid'],
					'real_repay_date'=>$date
				];
				$cashFlow->saveOrUpdate();
				if($o['repay_date']!=$date){
					$need_change = true;
				}
			}else if($need_change&&$o['cash_flow_type']==3){
				$diff_date = ceil((strtotime($o['repay_date']) - strtotime($date))/86400);
				$real_amount = $diff_date<0?0:round ($tranInfo['amount']*$tranInfo['rate']* $diff_date / 365);
				$cashFlow->params = [
					'uuid'=>$o['uuid'],
					'real_amount'=>$real_amount,
					'amount'=>$real_amount
				];
				$cashFlow->saveOrUpdate();
				$o['real_amount'] = $real_amount;
			}
		}
		
		
// 		$order = new LoanOrder ();
// 		$order->params ['uuid'] = $tranInfo ['loan_order_uuid'];
// 		$order->params ['loan_status'] = LoanOrder::LOAN_STATUS_PAYING;
// 		$order->saveOrUpdate ();
		
		if (! isset ( $params ['params'] ['jmgPassWord'] )) {
			throw new Exception ( 'u盾密码不能为空', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
		}
		
		$ret = JmfUtil::call_Jmf_consumer ( "com.jyblife.logic.bg.layer.SessionGet", array (
				'sessionToken' => $params ['sessionToken'] 
		) );
		if (! isset ( $ret ['code'] ) || $ret ['code'] != 0 || empty ( $ret ['data'] )) {
			throw new Exception ( '无法获取用户信息', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
		}
		$userInfo = $ret ['data'];
		
		$obj = new BankAccount ();
		$pay_account = $obj->getDataById ( $tranInfo ['loan_account_uuid'] );
		$collect_account = $obj->getDataById ( $tranInfo ['collect_account_uuid'] );
		
		$payInfo = array (
				'trade_type' => 15,
				'pay_remark' => $tranInfo['pay_remark'],
				'order_uuid' => $tranInfo ['transfer_num'],
				'pay_bank_account' => $pay_account ['bank_account'],
				'collect_bank_account' => $collect_account ['bank_account'],
				'to_name' => $collect_account ['account_name'],
				'to_bank' => $collect_account ['bank_dict_key'],
				'to_bank_desc' => $collect_account ['bank_name'],
				'to_city_name' => $collect_account ['city'],
				'to_bank_num'=>$collect_account['bank_link_code'],
				'notice_url' => 'com.jyblife.logic.bg.loan.NoticeResult',
				'jmgUserName' => $userInfo ['username'],
				'jmgPassWord' => $params ['params'] ['jmgPassWord'],
				'amount' => $tranInfo ['amount']
		);
		
		$ret = JmfUtil::call_Jmf_consumer ( "com.jyblife.logic.bg.pay.Order", $payInfo );
		if (isset ( $ret ['code'] ) && $ret ['code'] != 0) {
			$tran = new LoanTransfer ();
			$tran->params = [
				'uuid'=>$uuid,
				'water_uuid' => $ret ['data'] ['uuid'],
				'loan_status'=>LoanTransfer::LOAN_STATUS_UNCONFIRM
			];
			$tran->saveOrUpdate ();
		}else{
			$tran->params = [
				'uuid' => $tranInfo ['uuid'],
				'water_uuid' => $ret ['data'] ['uuid']
			];
			$tran->saveOrUpdate ();
		}
	}
	private function msgOpt() {
		if (empty ( $this->m_request ['next_node_users'] )) {
			return;
		}
		
		$next_node_users = explode ( ",", $this->m_request ['next_node_users'] );
		$userInfos = SysUser::getUserInfoByIds ( $next_node_users );
		$users = array ();
		foreach ( $userInfos as $u ) {
			$users [] = [ 
					'name' => $u ['name'],
					'id' => $u ['user_id'],
					'email' => $u ['email'] 
			];
		}
		
		$order_info = LoanTransfer::getDataById ( $this->m_request ['instance_id'] );
		$req = [ 
				'next_audit_user_infos' => $users,
				'create_user_name' => $this->m_request ['create_user_name'],
				'transfer_num' => $order_info ['transfer_num'],
				'collect_main_body' => $order_info ['collect_main_body'],
				'amount' => $order_info ['amount'],
				'node_code' => $this->m_request ['node_code'],
				'cur_audit_control_type' => $this->m_request ['node_status'],
				'transfer_uuid' => $this->m_request ['instance_id'] 
		];
		$amqpUtil = new AmqpUtil ();
		$ex = $amqpUtil->exchange ( LOAN_EXCHANGE_NAME );
		return $ex->publish ( json_encode ( $req ), LOAN_ROUT_AUDIT_TRANSFER );
	}
}