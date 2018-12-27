<?php
/**
 * 理财计划列表
 */
use money\service\BaseService;
use money\model\MoneyPlan;
use money\model\MoneyProduct;
use money\model\MainBody;
class ListPlan extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'page' => 'number',
        'limit' => 'number',
    ];

    public function exec(){
        $params = $this->m_request;
        $page = $this->m_request['page'];
        $limit = $this->m_request['limit'];
        $sessionToken = $this->m_request['sessionToken'];

        $ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.user.PrivData', ['sessionToken'=>$sessionToken, 'tablename'=>'m_money_manager_plan']);
        if(!isset($ret['code']) || $ret['code'] != '0'){
            throw new \Exception('获取数据权限数据失败', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        
        $main_body_ids = MainBody::getMainBodys($this->m_request['sessionToken']);
        if(count($main_body_ids)==0){
        	$result = ['page'=>$this->getDataByArray($params, 'page'), 'limit'=>$this->getDataByArray($params, 'limit'), 'count'=>0, 'data'=>[]];
        	$this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
        	return;
        }
        $params['main_body_uuids'] = $main_body_ids;

        $moneyDb = new MoneyPlan();
        $result = $moneyDb->listData($page, $limit, $params);
        $result['data'] = $this->dataToList($result['data']);

        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
    }

    /**
     * 列表数据转换
     */
    protected function dataToList($data){
        $keys = [
            'money_manager_product_uuid', 'money_manager_plan_num','plan_main_body_uuid',
            'plan_main_body_name', 'pay_bank_name', 'currency', 'amount', 'rate_start_date', 'rate_over_date',
            'forecast_annual_income_rate', 'plan_status', 'pay_status', 'is_pay_off', 'create_time','update_time',
            'term_type','audit_step', 'need_ticket_back'
        ];
        $productUuids = [];
        $result = [];
        foreach($data as $k=>$row){
            foreach($row as $key=>$value){
                if(in_array($key, $keys)){
                    $result[$k][$key] = $value;
                }
            }
            if(!in_array($row['money_manager_product_uuid'], $productUuids)){
                $productUuids[] = $row['money_manager_product_uuid'];
            }
            $result[$k]['plan_uuid'] = $row['uuid'];
        }
        $mMoneyPlan = new MoneyPlan();
        $db = new MoneyProduct();
        $prdInfo = $db->getForUuid($productUuids);
        $tmpData = [];
        foreach ($prdInfo as $row) {
            $tmpData[$row['uuid']] = $row;
        }
        foreach ($result as $k => &$row) {
            $row['money_manager_product_name'] = '';
            if(isset($tmpData[$row['money_manager_product_uuid']])){
                $row['money_manager_product_name'] = $tmpData[$row['money_manager_product_uuid']]['product_name'];
            }
            $where = ['money_manager_plan_uuid' => $row['plan_uuid'], 'cash_flow_type' => [2, 3], 'is_delete' => $mMoneyPlan::DEL_STATUS_NORMAL];
            $row['cash_flow'] = $mMoneyPlan->getList($where, 'cash_flow_type, status', null, null, null, 'm_money_manager_cash_flow');
        }
        return $result;
    }
}