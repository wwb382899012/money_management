<?php
/**
 * 账户余额通知结果
 */
use money\service\BaseService;
use money\model\BankAccount;

class AccountBalanceNoticeResult extends BaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'bank_account' => 'require',
        'bank_dict_key' => 'require|number',
        'balance' => 'require|number',
    ];

	public function exec()
	{
        $where = [
            'bank_account' => $this->m_request['bank_account'],
            'bank_dict_key' => $this->m_request['bank_dict_key'],
            'is_delete' => 1,
        ];
        $data = [
            'balance' => $this->m_request['balance'] * 100,//外部系统返回金额单位为元
        ];
        $mBankAccount = new BankAccount();
        $mBankAccount->where($where)->update($data);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS);
	}
}