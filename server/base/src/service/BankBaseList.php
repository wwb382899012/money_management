<?php
/**
 * 银行联行号列表
 *
 */
use money\service\BaseService;
use money\model\BankBase;

class BankBaseList extends BaseService{

    protected $rule = [
        'sessionToken'=>'require',
        'page' => 'integer',
        'limit' => 'integer',
        'bank_name' => 'require',
    ];

	public function exec(){
	    $page = $this->m_request['page'] ?? 1;
	    $limit = $this->m_request['limit'] ?? 50;
        $where[] = ['bank_name', 'like', "%{$this->m_request['bank_name']}%"];
	    !empty($this->m_request['province']) && $where[] = ['province', '=', $this->m_request['province']];
	    !empty($this->m_request['city']) && $where[] = ['city', '=', $this->m_request['city']];
	    !empty($this->m_request['sub_branch_name']) && $where[] = ['sub_branch_name', 'like', $this->m_request['sub_branch_name']."%"];
	    $mBankBase = new BankBase();
        $ret = $mBankBase->getDatasByPage($where, '*', $page, $limit, null);

		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}