<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/29
 * Time: 11:17
 * @link https://github.com/top-think/think-orm
 */
namespace money\model;

class OaCrmCustomerModel extends Model
{
    protected $connection = 'OA';
    protected $table = 'CRM_CustomerInfo';

    public function saveBankInfo($where, $data)
    {
        try {
            $this->startTrans();
            $customerInfo = $this->getOne($where, 'id');
            $deleted = $data['status'] ? 1 : 0;
            if (!empty($customerInfo)) {
                $data = [
                    'name' => $data['main_body_name'],
                    'engname' => $data['short_main_body_name'] ?? $data['main_body_name'],
                    'bankName' => $data['bank_name'],
                    'accountName' => $data['account_name'],
                    'accounts' => $data['bank_account'],
                    'deleted' => $deleted,
                ];
                if ($this->where($where)->update($data) === false) {
                    throw new \Exception('数据写入失败');
                }
                if ($this->table('CRM_ShareInfo')->where(['relateditemid' => $customerInfo['id']])->update(['deleted' => $deleted]) === false) {
                    throw new \Exception('数据写入失败');
                }
            } else {
                //资金系统注销账户，如果OA中不存在此记录，则直接返回成功
                if ($deleted) {
                    return true;
                }
                $data = [
                    'name' => $data['main_body_name'],
                    'engname' => $data['short_main_body_name'] ?? $data['main_body_name'],
                    'bankName' => $data['bank_name'],
                    'accountName' => $data['account_name'],
                    'accounts' => $data['bank_account'],
                    'deleted' => 0,
                ];
                if ($this->insert($data) === false) {
                    throw new \Exception('数据写入失败');
                }
                $data = [
                    'relateditemid' => $this->getLastInsID(),
                    'sharetype' => 4,
                    'seclevel' => 10,
                    'seclevelMax' => 100,
                    'sharelevel' => 1,
                    'contents' => 1,
                    'deleted' => 0,
                ];
                if ($this->table('CRM_ShareInfo')->insert($data) === false) {
                    throw new \Exception('数据写入失败');
                }
            }
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            return false;
        }
        return true;
    }
}
