<?php
namespace money\model;
class LoanCashFlow extends BaseModel{
	protected $table = 'm_loan_cash_flow';

	const STATUS_WAITING = 1;
	const STATUS_PAYING = 2;
	const STATUS_PAID = 3;
	const STATUS_FAIL = 4;

	//通过借款调拨uuid，获取当前要还的现金流表uuid
	public function loadNewCashFlowByTransferUuid($transfer_uuid){
		$where = ['loan_transfer_uuid'=>$transfer_uuid,'cash_status'=>'1'];
		$ret =  $this->field(' * ')->where($where)->order(['repay_date'=>'asc'])->page(1, 1)->select()->toArray();
		return $ret;
	}
	
	//获取现金流表借出数据uuid
	public function loadLoanCashFlowUuidByTransferUuid($transfer_uuid){
		$where = ['loan_transfer_uuid'=>$transfer_uuid,'cash_flow_type'=>'1'];
		$ret =  $this->field(' * ')->where($where)->order(['repay_date'=>'asc'])->page(1, 1)->select()->toArray();
		return $ret[0];
	}
}