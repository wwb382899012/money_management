<?php
use money\service\BaseService;
use money\model\Repay;
use money\model\ReportFullTrade;

class RepayUploadWater extends BaseService {
	protected $rule = [
		'sessionToken' => 'require',
// 		'bank_water'=> 'require',
		'uuid'=>'require'
	];
	public function exec(){
		$r = new Repay();
		$r->params = [
			'id'=>$this->m_request['uuid'],
			'bank_water' => $this->m_request['bank_water'],
			'bank_img_file_uuid'=>$this->m_request['bank_img_file_uuid'],
			'need_repay_ticket_back'=>0
		];
		$r->saveOrUpdate();
		$r = new ReportFullTrade();
		$ret = $r->loadDatas(['trade_uuid'=>$this->m_request['uuid']]);
		$r->saveReport(['bank_water_no'=>$this->m_request['bank_water']],$ret[0]['uuid']);
		
		$this->packRet(ErrMsg::RET_CODE_SUCCESS);
	}
}

?>