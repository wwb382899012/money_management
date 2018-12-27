<?php

namespace money\model;

class OaPayBusiness extends BaseModel
{
	protected $table = 'm_oa_pay_business';
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
                'type' => 1,
                'name' => '保理融资申请',
                'order_pay_type' => 13,
                'workflowid' => 103,
                'currentnodeid' => 760,
                'success_nodeid' => 762,
                'reject_nodeid' => 756,
                'refuse_nodeid' => 762,
                'table' => 'formtable_main_58',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'factoring_company|getWorkflowSelectItemName=7398',
                    'collect_main_body' => 'pay_unit_customer|getCrmCustomerName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'pay_unit_customer|getCrmCustomerAccountName',
                    'collect_bank_account' => 'bank_account',
                    'amount' => 'pay_amount',
                    'bs_background' => 'pay_rqst|stripTags',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            2 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '伯乐奖报销申请',
                'order_pay_type' => 5,
                'workflowid' => 76,
                'currentnodeid' => 477,
                'success_nodeid' => 475,
                'reject_nodeid' => 467,
                'refuse_nodeid' => 475,
                'table' => 'formtable_main_37',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'own_company',
                    'collect_main_body' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_account' => 'bank_account',
                    'collect_province_name' => 'bank_province_name|default=广东省',
                    'collect_city_name' => 'bank_city_name|default=深圳市',
                    'amount' => 'total_bak',
                    'bs_background' => 'remark',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            3 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '付款申请',
                'order_pay_type' => 13,
                'workflowid' => 59,
                'currentnodeid' => 368,
                'success_nodeid' => 370,
                'reject_nodeid' => 359,
                'refuse_nodeid' => 370,
                'table' => 'formtable_main_31',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'own_company',
                    'collect_main_body' => 'pay_unit_customer|getCrmCustomerName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'pay_unit_customer|getCrmCustomerAccountName',
                    'collect_bank_account' => 'bank_account',
                    'amount' => 'pay_amount',
                    'bs_background' => 'pay_reason|stripTags',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            4 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '付款申请-服务费打款',
                'order_pay_type' => 13,
                'workflowid' => 101,
                'currentnodeid' => 740,
                'success_nodeid' => 742,
                'reject_nodeid' => 731,
                'refuse_nodeid' => 742,
                'table' => 'formtable_main_56',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'choose_company|getWorkflowSelectItemName=7296',
                    'collect_main_body' => 'receive_unit|getCrmCustomerName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'receive_unit|getCrmCustomerName',
                    'collect_bank_account' => 'bank_account',
                    'amount' => 'pay_amount',
                    'bs_background' => 'pay_reason|stripTags',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            5 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '付款申请-可选主体',
                'order_pay_type' => 13,
                'workflowid' => 69,
                'currentnodeid' => 434,
                'success_nodeid' => 436,
                'reject_nodeid' => 425,
                'refuse_nodeid' => 436,
                'table' => 'formtable_main_31',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'choose_company|getWorkflowSelectItemName=6614',
                    'collect_main_body' => 'pay_unit_customer|getCrmCustomerName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'pay_unit_customer|getCrmCustomerAccountName',
                    'collect_bank_account' => 'bank_account',
                    'amount' => 'pay_amount',
                    'bs_background' => 'pay_reason|stripTags',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            6 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '付款申请-快速付款',
                'order_pay_type' => 13,
                'workflowid' => 100,
                'currentnodeid' => 728,
                'success_nodeid' => 730,
                'reject_nodeid' => 719,
                'refuse_nodeid' => 730,
                'table' => 'formtable_main_55',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'choose_company_business|getWorkflowSelectItemName=7266',
                    'collect_main_body' => 'pay_unit_customer|getCrmCustomerName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'pay_unit_customer|getCrmCustomerAccountName',
                    'collect_bank_account' => 'bank_account',
                    'amount' => 'pay_amount',
                    'bs_background' => 'pay_reason|stripTags',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            //付款申请-薪酬发放
            7 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '付款申请-薪酬发放',
                'order_pay_type' => 1,
                'workflowid' => 102,
                'currentnodeid' => 752,
                'success_nodeid' => 754,
                'reject_nodeid' => 743,
                'refuse_nodeid' => 754,
                'table' => 'formtable_main_57',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'own_company',
                    'collect_main_body' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_account' => 'bank_account',
                    'amount' => 'pay_amount',
                    'bs_background' => 'pay_rqst|stripTags',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
                'status' => 2,
            ],
            8 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '付款申请-业务付款',
                'order_pay_type' => 13,
                'workflowid' => 82,
                'currentnodeid' => 566,
                'success_nodeid' => 568,
                'reject_nodeid' => 560,
                'refuse_nodeid' => 568,
                'table' => 'formtable_main_39',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'choose_company_business|getWorkflowSelectItemName=6733',
                    'collect_main_body' => 'pay_unit_customer|getCrmCustomerName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'pay_unit_customer|getCrmCustomerAccountName',
                    'collect_bank_account' => 'bank_account',
                    'amount' => 'pay_amount',
                    'bs_background' => 'pay_reason|stripTags',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            9 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '付款申请-异常流',
                'order_pay_type' => 13,
                'workflowid' => 111,
                'currentnodeid' => 812,
                'success_nodeid' => 814,
                'reject_nodeid' => 811,
                'refuse_nodeid' => 814,
                'table' => 'formtable_main_55',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'choose_company_business|getWorkflowSelectItemName=7266',
                    'collect_main_body' => 'skdwwb',
                    'collect_bank_name' => 'khhwb|getBankName',
                    'collect_bank_desc' => 'khhwb',
                    'collect_account_name' => 'skdwwb ',
                    'collect_bank_account' => 'yhzhwb',
                    'amount' => 'pay_amount',
                    'bs_background' => 'pay_rqst|stripTags',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            10 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '员工借款申请',
                'order_pay_type' => 5,
                'workflowid' => 61,
                'currentnodeid' => 380,
                'success_nodeid' => 382,
                'reject_nodeid' => 371,
                'refuse_nodeid' => 382,
                'table' => 'formtable_main_32',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'own_company',
                    'collect_main_body' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_account' => 'bank_account',
                    'collect_province_name' => 'bank_province_name|default=广东省',
                    'collect_city_name' => 'bank_city_name|default=深圳市',
                    'amount' => 'loan_amount',
                    'bs_background' => 'loan_reason',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            //开票申请
            11 => [
            ],
            12 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '其他报销',
                'order_pay_type' => 5,
                'workflowid' => 99,
                'currentnodeid' => 715,
                'success_nodeid' => 713,
                'reject_nodeid' => 705,
                'refuse_nodeid' => 713,
                'table' => 'formtable_main_54',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'own_company',
                    'collect_main_body' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_account' => 'bank_account',
                    'collect_province_name' => 'bank_province_name|default=广东省',
                    'collect_city_name' => 'bank_city_name|default=深圳市',
                    'amount' => 'ap',
                    'bs_background' => 'remark',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            13 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '日常费用报销',
                'order_pay_type' => 5,
                'workflowid' => 54,
                'currentnodeid' => 309,
                'success_nodeid' => 301,
                'reject_nodeid' => 293,
                'refuse_nodeid' => 301,
                'table' => 'formtable_main_29',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'own_company',
                    'collect_main_body' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_account' => 'bank_account',
                    'collect_province_name' => 'bank_province_name|default=广东省',
                    'collect_city_name' => 'bank_city_name|default=深圳市',
                    'amount' => 'ap',
                    'bs_background' => 'remark',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            14 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '日常费用报销-可选主体',
                'order_pay_type' => 5,
                'workflowid' => 78,
                'currentnodeid' => 501,
                'success_nodeid' => 499,
                'reject_nodeid' => 491,
                'refuse_nodeid' => 499,
                'table' => 'formtable_main_29',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'choose_company|getWorkflowSelectItemName=6694',
                    'collect_main_body' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_account' => 'bank_account',
                    'collect_province_name' => 'bank_province_name|default=广东省',
                    'collect_city_name' => 'bank_city_name|default=深圳市',
                    'amount' => 'total_bak',
                    'bs_background' => 'remark',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            //收票申请
            15 => [
            ],
            16 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '团建费报销申请',
                'order_pay_type' => 5,
                'workflowid' => 77,
                'currentnodeid' => 488,
                'success_nodeid' => 486,
                'reject_nodeid' => 479,
                'refuse_nodeid' => 486,
                'table' => 'formtable_main_38',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'own_company',
                    'collect_main_body' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'resp_user|getHrmResourceLastName',
                    'collect_bank_account' => 'bank_account',
                    'collect_province_name' => 'bank_province_name|default=广东省',
                    'collect_city_name' => 'bank_city_name|default=深圳市',
                    'amount' => 'total_bak',
                    'bs_background' => 'remark',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
                ],
            ],
            17 => [
                'system_flag' => 'oa',
                'type' => 1,
                'name' => '猎头费付款申请',
                'order_pay_type' => 13,
                'workflowid' => 128,
                'currentnodeid' => 953,
                'success_nodeid' => 955,
                'reject_nodeid' => 946,
                'refuse_nodeid' => 955,
                'table' => 'formtable_main_80',
                'fields' => [
                    'out_order_num' => 'requestId',
                    'order_pay_type' => 'order_pay_type',
                    'pay_main_body' => 'choose_company|getWorkflowSelectItemName=8135',
                    'collect_main_body' => 'pay_unit_customer|getCrmCustomerName',
                    'collect_bank_name' => 'bank_type|getBankName',
                    'collect_bank_desc' => 'bank_type',
                    'collect_account_name' => 'pay_unit_customer|getCrmCustomerAccountName',
                    'collect_bank_account' => 'bank_account',
                    'amount' => 'pay_amount',
                    'bs_background' => 'pay_rqst|stripTags',
                    'contact_annex' => 'attachment1|getAttachmentUrl=.requestId',
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
