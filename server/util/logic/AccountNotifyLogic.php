<?php
/**
 * Created by PhpStorm.
 * User: xupengpeng
 * Date: 2018/8/17
 * Time: 8:32
 */

namespace money\logic;

use money\model\BankAccount;
use money\model\BankAccountHis;
use money\model\InterfacePriv;
use money\model\MainBody;
use money\model\NotifyConfig;

class AccountNotifyLogic extends NotifyLogic
{
    /**
     * 批量推送通知
     * @param array $data
     * @param bool $force
     * @return bool
     */
    public function batchPush($data, $force = false)
    {
        $result = true;
        //查询银行账户信息
        $mBankAccount = new BankAccount();
        $bankAccount = $mBankAccount->getOne(['uuid' => $data['uuid'], 'is_delete' => BankAccount::DEL_STATUS_NORMAL]);
        if (empty($bankAccount) || $bankAccount['status'] != $data['status'] || $bankAccount['deal_status'] != $data['deal_status']) {
            return -1;
        }
        if (isset($data['has_history']) && !empty($data['has_history'])) {
            //查询银行账户历史信息
            $where = ['uuid' => $data['uuid'], 'is_delete' => BankAccountHis::DEL_STATUS_NORMAL];
            $mBankAccountHis = new BankAccountHis();
            $bankAccountHis = $mBankAccountHis->getOne($where, 'main_body_uuid, bank_account', 'update_time desc');
            if (empty($bankAccountHis)) {
                $mainBodyUuids = [$bankAccount['main_body_uuid']];
            } else {
                $mainBodyUuids = [$bankAccount['main_body_uuid'], $bankAccountHis['main_body_uuid']];
            }
        } else {
            $mainBodyUuids = [$bankAccount['main_body_uuid']];
        }
        //查询主体信息
        $where = ['uuid' => $mainBodyUuids, 'is_delete' => MainBody::DEL_STATUS_NORMAL];
        $mMainBody = new MainBody();
        $mainBody = $mMainBody->getAll($where, 'uuid, short_name, full_name, status, update_time');
        $mainBody = array_column($mainBody, null, 'uuid');
        if (empty($mainBody) || !isset($mainBody[$bankAccount['main_body_uuid']])) {
            return -1;
        }
        //如果MQ消息中有字段old_main_body_name，则是变更主体推送的消息
        $oldMainBodyName = $data['old_main_body_name'] ?? (isset($bankAccountHis['main_body_uuid']) ? $mainBody[$bankAccountHis['main_body_uuid']]['full_name'] : $mainBody[$bankAccount['main_body_uuid']]['full_name']);
        $oldBankAccount = $bankAccountHis['bank_account'] ?? $bankAccount['bank_account'];
        //查询系统权限信息
        $privUuids = explode(',', $bankAccount['interface_priv']);
        $mInterfacePriv = new InterfacePriv();
        $list = $mInterfacePriv->getAll(['uuid' => $privUuids, 'is_delete' => InterfacePriv::DEL_STATUS_NORMAL], 'system_flag');
        if (empty($list)) {
            return -1;
        }
        $systemFlag = array_column($list, 'system_flag');
        $params = [
            'request_id' => $bankAccount['uuid'],
            'old_main_body_name' => $oldMainBodyName,
            'old_bank_account' => $oldBankAccount,
            'short_main_body_name' => $mainBody[$bankAccount['main_body_uuid']]['short_name'],
            'main_body_name' => $mainBody[$bankAccount['main_body_uuid']]['full_name'],
            'short_bank_name' => $bankAccount['short_name'],
            'bank_name' => $bankAccount['bank_name'],
            'bank_account' => $bankAccount['bank_account'],
            'account_name' => $bankAccount['account_name'],
            'timestamp' => isset($data['old_main_body_name']) ? strtotime($mainBody[$bankAccount['main_body_uuid']]['update_time']) : strtotime($bankAccount['update_time']),//主体信息更新，则使用主体更新时间
            'status' => $mainBody[$bankAccount['main_body_uuid']]['status'] == 1 ? $bankAccount['status'] : 2,//主体是注销状态，则银行账户也是注销状态
        ];
        //查询通知配置信息
        $mNotifyConfig = new NotifyConfig();
        $configs = $mNotifyConfig->getAll(['type' => 1, 'status' => 1, 'is_delete' => NotifyConfig::DEL_STATUS_NORMAL]);
        foreach ($configs as $config) {
            //过滤掉没有此账号权限的系统
            if (!in_array($config['system_flag'], $systemFlag)) {
                continue;
            }
            if (!$this->push($config, $params, $force)) {
                $result = false;
            }
        }
        return $result;
    }
}
