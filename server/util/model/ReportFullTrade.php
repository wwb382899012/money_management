<?php

/**
 * Class ReportFullTrade
 */
namespace money\model;
use money\base\MapUtil;
use money\logic\CommonLogic;

class ReportFullTrade extends BaseModel
{
    protected $table = 'm_report_full_trade';

    /**
     * 列表数据
     */    
    public function listData($page, $pageSize, $params){
        $where = [
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        if(!empty($params['trade_type'])){
            $where[] = ['trade_type', '=', $params['trade_type']];
        }
        if(!empty($params['apply_begin_time'])){
            $where[] = ['order_create_datetime', '>=', $params['apply_begin_time']];
        }
        if(!empty($params['apply_end_time'])){
            $where[] = ['order_create_datetime', '<=', $params['apply_end_time']];
        }
        if(!empty($params['approve_begin_time'])){
            $where[] = ['trade_receive_datetime', '>=', $params['approve_begin_time']];
        }
        if(!empty($params['approve_end_time'])){
            $where[] = ['trade_receive_datetime', '<=', $params['approve_end_time']];
        }
        if(!empty($params['audit_begin_time'])){
            $where[] = ['audit_datetime_3', '>=', $params['audit_begin_time']];
        }
        if(!empty($params['audit_end_time'])){
            $where[] = ['audit_datetime_3', '<=', $params['audit_end_time']];
        }
        if(!empty($params['pay_main_body_uuids'])&&is_array($params['pay_main_body_uuids'])&&count($params['pay_main_body_uuids'])>0){
        	$where[] = ['pay_main_body_uuid','in',$params['pay_main_body_uuids']];
        }
        
        $list = $this->getDatasByPage($where, '*,order_create_datetime as trade_date', $page, $pageSize, 'order_create_datetime desc');
        //时间只显示日期
        foreach ($list['data'] as &$item) {
            $item['trade_date'] = $item['trade_date']? date('Y-m-d', strtotime($item['trade_date'])):null;
            $item['pay_date'] = $item['pay_date']? date('Y-m-d', strtotime($item['pay_date'])):null;
            $item['mature_date'] = $item['mature_date'] ? date('Y-m-d', strtotime($item['mature_date'])):null;
        }
        //细类显示名称
        $list['data'] = MapUtil::getMapdArrayByParams($list['data'] , 'trade_son_type' , 'pay_type');
        return $list;
    }

    /**
     * 根据系统流水获取统计报表数据
     */
    public function getDataForSysWater($sysWater){
        return $this->getOne(['sys_water_uuid' => $sysWater, 'is_delete' => self::DEL_STATUS_NORMAL]);
    }

    /**
     * 新增数据
     */
    public function saveReport($data, $uuid=''){
        if($uuid){
            $res = $this->where(['uuid' => $uuid])->update($data);
        }else{
            $uuid = $data['uuid'] = md5(uuid_create());
            $data['create_time'] = date('Y-m-d H:i:s');
            $res = $this->insert($data);
        }
        return $res ? $uuid : null;
    }

    public function getWaterCount($lastTime)
    {
        $where = [
            ['create_time', '>', $lastTime],
        ];
        return $this->getCount($where, 'm_sys_trade_water');
    }

    /**
     * 获取交易流水的全量数据
     */
    public function waterData($lastTime){
        $where = [
            ['create_time', '>', $lastTime],
            ['status','=',3]
        ];
        $list = $this->getList($where, '*', null, null, null, 'm_sys_trade_water');
        $accounts = array_merge(array_column($list, 'pay_bank_account'), array_column($list, 'collect_bank_account'));

        $bankAccount = $this->table('m_bank_account')->where(['bank_account' => $accounts])->column('uuid, main_body_uuid, bank_name', 'bank_account');
        $mainBodyIds = array_column($bankAccount, 'main_body_uuid');
        $mainBody = $this->table('m_main_body')->where(['uuid' => $mainBodyIds])->column('full_name', 'uuid');
        foreach ($list as &$row) {
            $row['pay_bank_uuid'] = $row['pay_account_uuid'];
            $row['pay_bank_name'] = isset($bankAccount[$row['pay_bank_account']]['bank_name']) ? $bankAccount[$row['pay_bank_account']]['bank_name'] : '';
            $row['collect_bank_uuid'] = isset($bankAccount[$row['collect_bank_account']]['uuid']) ? $bankAccount[$row['collect_bank_account']]['uuid'] : '';
            $row['collect_bank_name'] = isset($bankAccount[$row['collect_bank_account']]['bank_name']) ? $bankAccount[$row['collect_bank_account']]['bank_name'] : '';

            $row['pay_main_body_uuid'] = isset($bankAccount[$row['pay_bank_account']]['main_body_uuid']) ? $bankAccount[$row['pay_bank_account']]['main_body_uuid'] : '';
            $row['pay_main_body_name'] = isset($mainBody[$row['pay_main_body_uuid']]) ? $mainBody[$row['pay_main_body_uuid']] : '';
            $row['collect_main_body_uuid'] = isset($bankAccount[$row['collect_bank_account']]['main_body_uuid']) ? $bankAccount[$row['collect_bank_account']]['main_body_uuid'] : '';
            $row['collect_main_body_name'] = isset($mainBody[$row['collect_main_body_uuid']]) ? $mainBody[$row['collect_main_body_uuid']] : '';
        }
        return $list;
    }
    
    /**
     * 通过流水获取付款指令
     */    
    public function getPayOrder(array $sysWaters){
        $result = [];
        $cLogic = new CommonLogic();
        $where = ['water_uuid' => $sysWaters, 'pay_status' => 2, 'is_delete' => self::DEL_STATUS_NORMAL,'transfer_status'=>20];
        $data = $this->getList($where, '*', null, null, null, 'm_pay_transfer');
        foreach($data as $row){
            $where = ['uuid' => $row['pay_order_uuid'], 'is_delete' => self::DEL_STATUS_NORMAL];
            $row['order_data'] = $this->getOne($where, '*', null, 'm_pay_order');
            $row['order_data']['audit_log'] = $cLogic->getAuditLog($row['order_data']['uuid'], ['pay_order']);
            $row['audit_log'] = $cLogic->getAuditLog($row['uuid'], ['pay_transfer_pay_type_1_code', 'pay_transfer_pay_type_2_code']);
            $result[$row['water_uuid']] = $row;
        }
        return $result;
    }

    /**
     * 通过流水获取内部调拨
     */
    public function getInnerTransfer(array $sysWaters){
        $result = [];
        $cLogic = new CommonLogic();
        $where = ['water_uuid' => $sysWaters, 'is_delete' => self::DEL_STATUS_NORMAL];
        $data = $this->getList($where, '*', null, null, null, 'm_inner_transfer');
        foreach($data as $row){
            $row['audit_log'] = $cLogic->getAuditLog($row['uuid'], ['inner_transfer_pay_type_1_code', 'inner_transfer_pay_type_2_code']);
            $result[$row['water_uuid']] = $row;
        }
        return $result;
    }

    /**
     * 通过流水获取借款数据
     */
    public function getLoan(array $sysWaters){
        $result = [];
        $cLogic = new CommonLogic();
        $where = ['water_uuid' => $sysWaters, 'is_delete' => self::DEL_STATUS_NORMAL];
        $data = $this->getList($where, '*', null, null, null, 'm_loan_transfer');
        foreach ($data as $row) {
            $where = ['uuid' => $row['loan_order_uuid'], 'is_delete' => self::DEL_STATUS_NORMAL];
            $row['order_data'] = $this->getOne($where, '*', null, 'm_loan_order');
            $row['order_data']['audit_log'] = $cLogic->getAuditLog($row['order_data']['uuid'], ['loan_order']);
            $row['audit_log'] = $cLogic->getAuditLog($row['uuid'], ['loan_transfer_pay_type_1_code', 'loan_transfer_pay_type_2_code']);
            $result[$row['water_uuid']] = $row;
        }
//         $list = MainBody::changeUuidToName($list , 'loan_main_body_uuid' , 'loan_main_body');
        return $result;        
    }
    
    /**
     * 通过流水获取借款数据
     */
    public function getRepay(array $sysWaters){
    	$result = [];
    	$cLogic = new CommonLogic();
    	$where = ['repay_water_uuid' => $sysWaters];
    	$data = $this->getList($where, '*', null, null, null, 'm_repay');
    	foreach ($data as $row) {
    		$where = ['uuid' => $row['loan_transfer_uuid'], 'is_delete' => self::DEL_STATUS_NORMAL];
    		$row['order_data'] = $this->getOne($where, '*', null, 'm_loan_transfer');
    		

    		$cash = new LoanCashFlow();
    		$row['cashs'] = $cash->loadDatas(['loan_transfer_uuid'=>$row['loan_transfer_uuid'],'index'=>$row['index']]);
//     		$row['order_data']['audit_log'] = $cLogic->getAuditLog($row['order_data']['uuid'], ['loan_order']);
    		$row['audit_log'] = $cLogic->getAuditLog($row['id'], ['repay_apply']);
    		$result[$row['repay_water_uuid']] = $row;
    	}
    	return $result;
    }

    /**
     * 通过流水获取理财数据
     */
    public function getPlan(array $sysWaters){
        $result = [];
        $cLogic = new CommonLogic();
        $where = ['sys_water' => $sysWaters, 'is_delete' => self::DEL_STATUS_NORMAL, 'plan_status' => 20];
        $data = $this->getList($where, '*', null, null, null, 'm_money_manager_plan');
        foreach($data as $row){
            $row['audit_log'] = $cLogic->getAuditLog($row['uuid'], ['financial_audit_pay_type_1_code', 'financial_audit_pay_type_2_code', 'redemption_audit_code_1_node']);
            $result[$row['sys_water']] = $row;
        }
        return $result;
    }
    
    public function saveData($type , $order_uuid){
    	$obj = new SysTradeWater();
    	$waters = $obj->loadDatas(['order_uuid'=>$order_uuid,'status'=>3]);
    	$waters = $this->optWater($waters);
    	$row = $waters[0];
    	$water_uuid = $row['uuid'];
    	switch($type){
    		case 1:
    			$orderDatas = $this->getPayOrder([$water_uuid]);
    			$data = $this->orderData($row, $orderDatas[$row['uuid']]);
    			break;
    		case 2:
    			$innerDatas = $this->getInnerTransfer([$water_uuid]);
    			$data = $this->innerData($row, $innerDatas[$row['uuid']]);
    			break;
    		case 3:
    			$loanDatas = $this->getLoan([$water_uuid]);
    			$data = $this->loanData($row, $loanDatas[$row['uuid']]);
    			break;
    		case 4:
    			$planDatas = $this->getPlan([$water_uuid]);
    			$data = $this->moneyData($row, $planDatas[$row['uuid']]);
    			break;
    		case 5:
    			$repayDatas = $this->getRepay([$water_uuid]);
    			$data = $this->repayDatas($row, $repayDatas[$row['uuid']]);
    			break;
    		default:
    			throw new \Exception(\ErrMsg::RET_CODE_SERVICE_FAIL);
    	}
    	\CommonLog::instance()->getDefaultLogger()->info('full report save|:'.json_encode($data));
    	$data['sys_water_uuid'] = $row['uuid'];
    	$this->saveReport($data, $row['report_uuid']);
    	
    }
    
    public function optWater($list){
    	$accounts = array_merge(array_column($list, 'pay_bank_account'), array_column($list, 'collect_bank_account'));
    	
    	$bankAccount = $this->table('m_bank_account')->where(['bank_account' => $accounts])->column('uuid, main_body_uuid, bank_name', 'bank_account');
    	$mainBodyIds = array_column($bankAccount, 'main_body_uuid');
    	$mainBody = $this->table('m_main_body')->where(['uuid' => $mainBodyIds])->column('full_name', 'uuid');
    	foreach ($list as &$row) {
    		$row['pay_bank_uuid'] = $row['pay_account_uuid'];
    		$row['pay_bank_name'] = isset($bankAccount[$row['pay_bank_account']]['bank_name']) ? $bankAccount[$row['pay_bank_account']]['bank_name'] : '';
    		$row['collect_bank_uuid'] = isset($bankAccount[$row['collect_bank_account']]['uuid']) ? $bankAccount[$row['collect_bank_account']]['uuid'] : '';
    		$row['collect_bank_name'] = isset($bankAccount[$row['collect_bank_account']]['bank_name']) ? $bankAccount[$row['collect_bank_account']]['bank_name'] : '';
    	
    		$row['pay_main_body_uuid'] = isset($bankAccount[$row['pay_bank_account']]['main_body_uuid']) ? $bankAccount[$row['pay_bank_account']]['main_body_uuid'] : '';
    		$row['pay_main_body_name'] = isset($mainBody[$row['pay_main_body_uuid']]) ? $mainBody[$row['pay_main_body_uuid']] : '';
    		$row['collect_main_body_uuid'] = isset($bankAccount[$row['collect_bank_account']]['main_body_uuid']) ? $bankAccount[$row['collect_bank_account']]['main_body_uuid'] : '';
    		$row['collect_main_body_name'] = isset($mainBody[$row['collect_main_body_uuid']]) ? $mainBody[$row['collect_main_body_uuid']] : '';
    	}
    	return $list;
    }
    
    /**
     * 付款数据
     */
    protected function orderData($water, $orderData=[]){
    	$result['out_order_num'] = $orderData['order_data']['order_num'];
    	$result['trade_order_num'] = $orderData['transfer_num'];
    	$result['pay_date'] = $orderData['require_pay_datetime'];
    	$result['trade_uuid'] = $orderData['uuid'];
    	$result['trade_type'] = 1;
    	$result['trade_son_type'] = $orderData['order_data']['order_pay_type'];
    	$result['amount'] = $orderData['order_data']['amount'];
    	$result['pay_bank_uuid'] = $water['pay_bank_uuid'];
    	$result['pay_bank_name'] = $water['pay_bank_name'];
    	$result['pay_bank_account'] = $water['pay_bank_account'];
    	$result['pay_main_body_uuid'] = $water['pay_main_body_uuid'];
    	$result['pay_main_body_name'] = $water['pay_main_body_name'];
    	$result['collect_bank_uuid'] = $water['collect_bank_uuid'];
    	$result['collect_bank_name'] = $water['collect_bank_name'];
    	$result['collect_bank_account'] = $water['collect_bank_account'];
    	$result['collect_main_body_uuid'] = $water['collect_main_body_uuid'];
    	$result['collect_main_body_name'] = $water['collect_main_body_name'];
    	$result['bank_water_no'] = $water['out_water_no'];
    	$result['real_pay_type'] = $orderData['real_pay_type'];
    	$result['is_financing'] = $orderData['order_data']['is_financing'];
    	$result['financing_dict_key'] = $orderData['order_data']['is_financing'];
    	$result['financing_dict_value'] = $orderData['order_data']['financing_dict_value'];
    	//         $result['trade_status'] = $orderData['pay_status'];
    	//         $result['mature_date'] = null;
    	$result['order_create_user_name'] = $orderData['order_data']['order_create_people'];
    	$result['trade_entry_datetime'] = $orderData['order_data']['create_time'];
    	$result['trade_receive_datetime'] = $orderData['order_data']['audit_log'][1]['update_time'] ?? null;
    	$result['order_create_datetime'] = $orderData['audit_log'][1]['update_time'] ?? null;
    	$result['audit_name_1'] = $orderData['audit_log'][0]['create_user_name'] ?? '';
    	$result['audit_name_3'] = $orderData['audit_log'][1]['deal_user_name'] ?? '';
    	$result['audit_datetime_1'] = $orderData['audit_log'][0]['create_time'] ?? null;
    	$result['audit_datetime_3'] = $orderData['audit_log'][1]['update_time'] ?? null;
    	$result['bank_water_no'] = $orderData['bank_water'];
    
    	return $result;
    }
    
    /**
     * 借款数据
     */
    protected function loanData($water, $loanData=[]){
    	if(!isset($loanData['order_data']) || empty($loanData['order_data'])){
    		return null;
    	}
    	$result['out_order_num'] = $loanData['order_data']['order_num'];
    	$result['trade_order_num'] = $loanData['transfer_num'];
    	$result['pay_date'] = $loanData['order_data']['loan_datetime'];
    	$result['trade_uuid'] = $loanData['uuid'];
    	$result['trade_type'] = 2;
    	$result['trade_son_type'] = 15;
    	$result['amount'] = $loanData['order_data']['amount'];
    	$result['pay_bank_uuid'] = $water['pay_bank_uuid'];
    	$result['pay_bank_name'] = $water['pay_bank_name'];
    	$result['pay_bank_account'] = $water['pay_bank_account'];
    	$result['pay_main_body_uuid'] = $water['pay_main_body_uuid'];
    	$result['pay_main_body_name'] = $water['pay_main_body_name'];
    	$result['collect_bank_uuid'] = $water['collect_bank_uuid'];
    	$result['collect_bank_name'] = $water['collect_bank_name'];
    	$result['collect_bank_account'] = $water['collect_bank_account'];
    	$result['collect_main_body_uuid'] = $water['collect_main_body_uuid'];
    	$result['collect_main_body_name'] = $water['collect_main_body_name'];
    	$result['bank_water_no'] = $water['out_water_no'];
    	$result['real_pay_type'] = $loanData['real_pay_type'];
    	$result['is_financing'] = 0;
    	$result['financing_dict_key'] = '';
    	$result['financing_dict_value'] = '';
    	$result['trade_status'] = $loanData['is_pay_off'];
    	$result['mature_date'] = $loanData['forecast_datetime'];
    	$result['interest_rate'] = $loanData['rate'];
    	$result['order_create_user_name'] = $loanData['order_data']['order_create_people'];
    	$result['trade_entry_datetime'] = $loanData['order_data']['create_time'];
    	$result['trade_receive_datetime'] = $loanData['order_data']['audit_log'][1]['update_time'] ?? null;
    	$result['order_create_datetime'] = $loanData['audit_log'][1]['update_time'] ?? null;
    	$result['audit_name_1'] = $loanData['audit_log'][0]['deal_user_name'] ?? '';
    	$result['audit_name_3'] = $loanData['audit_log'][2]['deal_user_name'] ?? '';
    	$result['audit_datetime_1'] = $loanData['audit_log'][0]['create_time'] ?? null;
    	$result['audit_datetime_2'] = $loanData['audit_log'][1]['update_time'] ?? null;
    	$result['audit_datetime_3'] = $loanData['audit_log'][2]['update_time'] ?? null;
    	$result['bank_water_no'] = $loanData['bank_water'];
    	return $result;
    }
    
    /**
     * 内部调拨
     */
    protected function innerData($water, $innerData){
    	$result['out_order_num'] = '';
    	$result['trade_order_num'] = $innerData['order_num'];
    	$result['pay_date'] = $innerData['hope_deal_date'];
    	$result['trade_uuid'] = $innerData['uuid'];
    	$result['trade_type'] = 3;
    	$result['trade_son_type'] = 18;
    	$result['amount'] = $innerData['amount'];
    	$result['pay_bank_uuid'] = $water['pay_bank_uuid'];
    	$result['pay_bank_name'] = $water['pay_bank_name'];
    	$result['pay_bank_account'] = $water['pay_bank_account'];
    	$result['pay_main_body_uuid'] = $water['pay_main_body_uuid'];
    	$result['pay_main_body_name'] = $water['pay_main_body_name'];
    	$result['collect_bank_uuid'] = $water['collect_bank_uuid'];
    	$result['collect_bank_name'] = $water['collect_bank_name'];
    	$result['collect_bank_account'] = $water['collect_bank_account'];
    	$result['collect_main_body_uuid'] = $water['collect_main_body_uuid'];
    	$result['collect_main_body_name'] = $water['collect_main_body_name'];
    	$result['bank_water_no'] = $water['out_water_no'];
    	$result['real_pay_type'] = $innerData['real_pay_type'];
    	$result['is_financing'] = 0;
    	$result['financing_dict_key'] = '';
    	$result['financing_dict_value'] = '';
    	$result['trade_status'] = $innerData['pay_status'];
//     	$result['interest_rate'] = '';
    	$result['order_create_user_name'] = $innerData['audit_log'][0]['deal_user_name'] ?? '';
    	$result['order_create_datetime'] = $innerData['create_time'];
    	$result['trade_receive_datetime'] = null;
    	$result['trade_entry_datetime'] = null;
    	$result['audit_name_1'] = $innerData['audit_log'][0]['create_user_name'] ?? '';
    	$result['audit_name_3'] = $innerData['audit_log'][1]['deal_user_name'] ?? '';
    	$result['audit_datetime_1'] = $innerData['audit_log'][0]['create_time'] ?? null;
    	$result['audit_datetime_3'] = $innerData['audit_log'][1]['update_time'] ?? null;
    	$result['bank_water_no'] = $innerData['bank_water'];
    	
    	return $result;
    }
    
    /**
     * 理财数据
     */
    protected function moneyData($water, $planData){
    	$result['out_order_num'] = '';
    	$result['trade_order_num'] = $planData['money_manager_plan_num'];
    	$result['pay_date'] = $planData['rate_start_date'];
    	$result['trade_uuid'] = $planData['uuid'];
    	$result['trade_type'] = 4;
    	$result['trade_son_type'] = '';
    	$result['amount'] = $planData['amount'];
    	$result['pay_bank_uuid'] = $water['pay_bank_uuid'];
    	$result['pay_bank_name'] = $water['pay_bank_name'];
    	$result['pay_bank_account'] = $water['pay_bank_account'];
    	$result['pay_main_body_uuid'] = $water['pay_main_body_uuid'];
    	$result['pay_main_body_name'] = $water['pay_main_body_name'];
    	$result['collect_bank_uuid'] = $water['collect_bank_uuid'];
    	$result['collect_bank_name'] = $water['collect_bank_name'];
    	$result['collect_bank_account'] = $water['collect_bank_account'];
    	$result['collect_main_body_uuid'] = $water['collect_main_body_uuid'];
    	$result['collect_main_body_name'] = $water['collect_main_body_name'];
    	$result['bank_water_no'] = $water['out_water_no'];
    	$result['real_pay_type'] = $planData['real_pay_type'];
    	$result['is_financing'] = 0;
    	$result['financing_dict_key'] = '';
    	$result['financing_dict_value'] = '';
    	$result['trade_status'] = $planData['is_pay_off'];
    	$result['mature_date'] = $planData['rate_over_date'] ?? null;
    	$result['interest_rate'] = $planData['forecast_annual_income_rate'];
    	$result['order_create_user_name'] = $planData['create_user_name'];
    	$result['order_create_datetime'] = $planData['create_time'];
    	$result['trade_receive_datetime'] = null;
    	$result['trade_entry_datetime'] = null;
    	$result['audit_name_1'] = $planData['audit_log'][0]['deal_user_name'] ?? '';
    	$result['audit_name_2'] = $planData['audit_log'][1]['deal_user_name'] ?? '';
    	$result['audit_name_3'] = $planData['audit_log'][2]['deal_user_name'] ?? '';
    	$result['audit_datetime_1'] = $planData['audit_log'][0]['create_time'] ?? null;
    	$result['audit_datetime_2'] = $planData['audit_log'][1]['update_time'] ?? null;
    	$result['audit_datetime_3'] = $planData['audit_log'][2]['update_time'] ?? null;
    
    	return $result;
    }
    
    protected function repayDatas($water , $repayData){
    	$result['out_order_num'] = '';
    	$result['trade_order_num'] = $repayData['repay_transfer_num'];
    	$result['pay_date'] = date('Y-m-d');
    	$result['trade_uuid'] = $repayData['id'];
    	$result['trade_type'] = 5;
    	$result['trade_son_type'] = 16;
    	$result['amount'] = $repayData['amount'];
    	$result['pay_bank_uuid'] = $water['pay_bank_uuid'];
    	$result['pay_bank_name'] = $water['pay_bank_name'];
    	$result['pay_bank_account'] = $water['pay_bank_account'];
    	$result['pay_main_body_uuid'] = $water['pay_main_body_uuid'];
    	$result['pay_main_body_name'] = $water['pay_main_body_name'];
    	$result['collect_bank_uuid'] = $water['collect_bank_uuid'];
    	$result['collect_bank_name'] = $water['collect_bank_name'];
    	$result['collect_bank_account'] = $water['collect_bank_account'];
    	$result['collect_main_body_uuid'] = $water['collect_main_body_uuid'];
    	$result['collect_main_body_name'] = $water['collect_main_body_name'];
    	$result['bank_water_no'] = $water['out_water_no'];
    	$result['real_pay_type'] = $repayData['real_pay_type'];
    	$result['is_financing'] = 0;
    	$result['financing_dict_key'] = '';
    	$result['financing_dict_value'] = '';
    	$result['trade_status'] = $repayData['is_pay_off'];
    	$result['mature_date'] = $repayData['forecast_date'];
    	$result['interest_rate'] = $repayData['order_data']['rate'];
    	$result['bank_water_no'] = $repayData['bank_water'];
    	$result['order_create_datetime'] = $repayData['create_time'];
    	$result['order_create_user_name'] = $repayData['audit_log'][0]['deal_user_name'] ?? '';
    	
    	//     	$result['trade_entry_datetime'] =
    	$result['audit_name_1'] = $repayData['audit_log'][0]['deal_user_name'] ?? '';
    	$result['audit_name_3'] = $repayData['audit_log'][1]['deal_user_name'] ?? '';
    	$result['audit_datetime_1'] = $repayData['audit_log'][0]['create_time'] ?? null;
    	$result['audit_datetime_3'] = $repayData['audit_log'][1]['update_time'] ?? null;
    	
    	return $result;
    }
}