<?php

/**
 * 账户表
 * @author sun
 *
 */
namespace money\model;

class BankAccount extends BaseModel
{
	protected $table = 'm_bank_account';
	
	public function details($queryArray , $cols , $page , $pageSize){
		$cols = $cols?$cols:'*';
        $where[] = ['is_delete', '=', self::DEL_STATUS_NORMAL];
        isset($queryArray['real_pay_type']) && $where[] = ['real_pay_type', '=', $queryArray['real_pay_type']];
        isset($queryArray['main_body_uuid']) && $where[] = ['main_body_uuid', '=', $queryArray['main_body_uuid']];
        isset($queryArray['deal_status']) && $where[] = ['deal_status', '=', $queryArray['deal_status']];
        isset($queryArray['status']) && $where[] = ['status', '=', $queryArray['status']];
        if(isset($queryArray['bank_name'])){
            $where[] = ['bank_name', 'like', "%{$queryArray['bank_name']}%"];
        }
        
        if(!empty($queryArray['main_body_uuids'])&&is_array($queryArray['main_body_uuids'])&&count($queryArray['main_body_uuids'])>0){
        	$where[] = ['main_body_uuid','in',$queryArray['main_body_uuids']];
        }
		$list = $this->getDatasByPage($where, $cols, $page, $pageSize, ["update_time" => "desc"]);
        if (!empty($list['data'])) {
            $mSysUser = new SysUser();
            $mSysUser->changeUidToName($list['data']);
        }
        return $list;
	}

    /**
     * @link http://172.16.1.8:9887/yqzl/doc/#api-pay-PostComJyblifeBanklinkActionServiceSearchbalance
     * @param array $list
     */
	public function syncBalance($list = []) {
        foreach ($list as $row) {
            if (!isset($row['uuid']) || !isset($row['bank_dict_key']) || !isset($row['bank_account'])) {
                continue;
            }
            $req = array(
                'bank' => $row['bank_dict_key'],
                'acctId' => $row['bank_account'],
                'bbknbr' => $row['area'],//招行必填
            );
            $ret = \JmfUtil::call_Jmf_consumer("com.jyblife.banklink.action.service.SearchBalance ", $req);
            if (!isset($ret['code']) || $ret['code']!=0 || !isset($ret['data']) || !isset($ret['data']['availbal'])) {
                \CommonLog::instance()->getDefaultLogger()->warn('接口调用异常:'.json_encode($ret, JSON_UNESCAPED_UNICODE));
                continue;
            }
            $data = [
                'balance' => $ret['data']['availbal'] * 100,//外部系统返回金额单位为元
            ];
            $this->where(['uuid' => $row['uuid']])->update($data);
        }
    }

    public function getByAccount($acc, $status = 0, $dealStatus = 1){
        $params = array(
            'bank_account'=>$acc,
            'status' => $status,
            'deal_status' => $dealStatus,
        );
        return $this->getOne($params);
    }
    
    public function validateDulicate($bank_name , $account , $uuid = null){
	    $where = [
            //['bank_name', '=', $bank_name],
            ['bank_account', '=', $account],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
	    if (!empty($uuid)) {
	        $where[] = ['uuid', '<>', $uuid];
        }
        if ($this->getCount($where)) {
	        return true;
        } else {
	        return false;
        }
    }

    /**
     * 判断账号是否使用中
     * @param $uuid
     * @return bool
     */
    public function isInUse($uuid)
    {
        //付款
        $where = [
            ['pay_account_uuid|collect_account_uuid', '=', $uuid],
            ['order_status', 'IN', [0, 1, 2, 3, 5]],
            ['pay_status', 'IN', [0, 1]],
        ];
        if ($this->table('m_pay_order')->where($where)->count()) {
            return true;
        }
        $where = [
            ['pay_account_uuid|collect_account_uuid', '=', $uuid],
            ['transfer_status', 'IN', [0, 1, 2, 3, 5]],
            ['pay_status', 'IN', [0, 1]],
        ];
        if ($this->table('m_pay_transfer')->where($where)->count()) {
            return true;
        }
        //借款
        $where = [
            ['loan_account_uuid|collect_account_uuid', '=', $uuid],
            ['order_status', 'IN', [0, 1, 2, 3, 5]],
            ['loan_status', 'IN', [0, 1]],
        ];
        if ($this->table('m_loan_order')->where($where)->count()) {
            return true;
        }
        $where = [
            ['loan_account_uuid|collect_account_uuid', '=', $uuid],
            ['transfer_status', 'IN', [0, 1, 2, 3, 5]],
            ['loan_status', 'IN', [0, 1]],
        ];
        if ($this->table('m_loan_transfer')->where($where)->count()) {
            return true;
        }
        //还款
        $bankAccount = $this->getOne(['uuid' => $uuid], 'main_body_uuid');
        if ($bankAccount) {
            $where = [
                ['collect_main_body_uuid|repay_main_body_uuid', '=', $bankAccount['main_body_uuid']],
                ['repay_order_status', 'IN', [0, 1, 2, 3, 5]],
            ];
            if ($this->table('m_repay_order')->where($where)->count()) {
                return true;
            }
        }
        $where = [
            ['repay_account_uuid|collect_account_uuid', '=', $uuid],
            ['repay_transfer_status', 'IN', [0, 1, 2, 3, 5]],
            ['repay_status', 'IN', [0, 1]],
        ];
        if ($this->table('m_repay')->where($where)->count()) {
            return true;
        }
        //内部调拨
        $where = [
            ['pay_account_uuid|collect_account_uuid', '=', $uuid],
            ['transfer_status', 'IN', [0, 1, 2, 3, 4, 5]],
            ['pay_status', 'IN', [0, 1]],
        ];
        if ($this->table('m_inner_transfer')->where($where)->count()) {
            return true;
        }
        //理财
        $where = [
            ['pay_bank_uuid', '=', $uuid],
            ['plan_status', 'IN', [0, 1, 2, 3, 4, 5]],
            ['pay_status', 'IN', [0, 1]],
        ];
        if ($this->table('m_money_manager_plan')->where($where)->count()) {
            return true;
        }
        return false;
    }
}