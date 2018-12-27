<?php
/**
 * 账户同步余额
 */
use money\service\BaseService;
use money\model\BankAccount;

class AccountSyncBalance extends BaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'uuid' => 'require',
        'bank_account' => 'requireIf:uuid,',
    ];

	public function exec()
	{
	    $uuids = isset($this->m_request['uuid']) ? (is_string($this->m_request['uuid']) ? explode(',', $this->m_request['uuid']) : $this->m_request['uuid']) : [];
        $bankAccounts = isset($this->m_request['bank_account']) ? (is_string($this->m_request['bank_account']) ? explode(',', $this->m_request['bank_account']) : $this->m_request['bank_account']) : [];

        $mBankAccount = new BankAccount();
        $where = [];
        !empty($uuids) && $where['uuid'] = $uuids;
        !empty($bankAccounts) && $where['bank_account'] = $bankAccounts;
        $list = $mBankAccount->getList($where, 'uuid, bank_account, bank_dict_key, area', 1, 100);
        $mBankAccount->syncBalance($list);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS);
	}
}