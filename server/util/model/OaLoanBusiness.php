<?php

namespace money\model;

class OaLoanBusiness extends BaseModel
{
	protected $table = 'm_oa_loan_business';
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
                'type' => 2,
                'name' => '往来款-付款申请',
                'order_pay_type' => 1,
                'workflowid' => 119,
                'currentnodeid' => 867,
                'success_nodeid' => 868,
                'reject_nodeid' => 864,
                'refuse_nodeid' => 868,
                'table' => 'formtable_main_73',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'loan_main_body' => 'jkf|getWorkflowSelectItemName=7944',
                    'collect_main_body' => 'dkzt|getCrmCustomerName',
                    'collect_bank_name' => 'khh|getBankName',
                    'collect_bank_desc' => 'khh',
                    'collect_account_name' => 'dkzt|getCrmCustomerName',
                    'collect_bank_account' => 'yhzh',
                    'amount' => 'jkjexx',
                    'loan_date' => 'jkrq',
                    'forecast_date' => 'yjhkr',
                    'rate' => 'int|doubleval',
                    'bs_background' => 'ywbj',
                    'plus_require' => 'qtsm|html_entity_decode|strip_tags',
                    'contact_annex' => 'htfj|getAttachmentUrl=.requestId',
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
