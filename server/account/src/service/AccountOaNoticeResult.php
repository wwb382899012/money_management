<?php
/**
 * OA系统处理账户变更通知结果
 */
use \money\model\OaCrmCustomerModel;
use money\service\BaseService;


class AccountOaNoticeResult extends BaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'old_main_body_name' => 'require',
        'old_bank_account' => 'require',
        'main_body_name' => 'require',
        'bank_name' => 'require',
        'bank_account' => 'require',
        'account_name' => 'require',
    ];

	public function exec()
	{
        $crm = new OaCrmCustomerModel();
        $where = [
            'name' => $this->m_request['old_main_body_name'],
            'accounts' => $this->m_request['old_bank_account'],
        ];
        if ($crm->saveBankInfo($where, $this->m_request)) {
            $this->packRet(ErrMsg::RET_CODE_SUCCESS);
        } else {
            $this->packRet(ErrMsg::RET_CODE_SERVICE_FAIL, null, '数据保存失败');
        }
	}
}
