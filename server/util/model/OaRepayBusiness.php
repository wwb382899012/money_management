<?php

namespace money\model;

class OaRepayBusiness extends BaseModel
{
	protected $table = 'm_oa_repay_business';
    protected $pk = 'id';
	
	public function getList($where = [], $fields = '*', $page = 1, $pageSize = 20, $order = ['create_time' => 'desc'], $table = '')
    {
        $list = parent::getList($where, $fields, $page, $pageSize, $order, $table);
        array_walk($list, function (&$v) {
            isset($v['fields']) && $v['fields'] = json_decode($v['fields'], true);
        });
        return $list;
    }

    public function initData()
    {
        $maps = [
            1 => [
                'system_flag' => 'oa',
                'type' => 3,
                'name' => '往来款-还款申请',
                'order_pay_type' => 2,
                'workflowid' => 123,
                'currentnodeid' => 899,
                'success_nodeid' => 900,
                'reject_nodeid' => 896,
                'refuse_nodeid' => 900,
                'table' => 'formtable_main_77',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'loan_out_order_num' => 'glfklc',
                    'loan_main_body' => 'dkf',
                    'collect_main_body' => 'jkztbox',
                    'collect_bank_name' => 'khh|getBankName',
                    'collect_bank_desc' => 'khh',
                    'collect_account_name' => 'jkztbox',
                    'collect_bank_account' => 'yhzh',
                    'amount' => 'bjhk',
                    //'forecast_date' => 'yjhkr',
                    'require_repay_date' => 'yqhkr',
                    //'rate' => 'lxl|doubleval',
                    //'background' => 'ywbj',
                    'repay_type' => 'repay_type|getWorkflowSelectItemName=7976',
                    'repay_desc' => 'hksm',
                    'repay_annex' => 'fj|getAttachmentUrl=.requestId',
                ],
            ],
        ];

        try {
            $this->startTrans();
            $createTime = date('Y-m-d H:i:s');
            foreach ($maps as &$item) {
                if (empty($item)) {
                    continue;
                }
                $item['fields'] = json_encode($item['fields'], JSON_UNESCAPED_UNICODE);
                $item['create_user_id'] = 1;
                $item['create_time'] = $createTime;
                if ($this->insert($item) === false) {
                    throw new \Exception('写入数据失败');
                }
            }
            if ($this->where([['create_time', '<', $createTime]])->update(['is_delete' => 2]) === false) {
                throw new \Exception('更新数据失败');
            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            return false;
        }
    }
}
