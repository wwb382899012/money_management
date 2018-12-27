<?php
/**
 * 全量交易处理逻辑
 */

namespace money\logic;

use money\model\ReportFullTrade;

class FullTradeLogic{
    public function start($lastTime){
    	\CommonLog::instance()->getDefaultLogger()->info('full report begin|lastTime:'.$lastTime);
//         $page = 1;
//         $pageSize = 20;
        $db = new ReportFullTrade();
        $count = $db->getWaterCount($lastTime);
        \CommonLog::instance()->getDefaultLogger()->info('full report begin|totalCount:'.$count);
//         $page++;
//         while($page <= ceil($count/$pageSize)){
//             $page++;
        $this->run($lastTime);
//         }
    }
    protected function run($lastTime){
        $db = new ReportFullTrade();
        //$cLogic = new CommonLogic();
        $result = $db->waterData($lastTime);
        $waterUuids = [];
        foreach($result as $row){
        	\CommonLog::instance()->getDefaultLogger()->info('full report opt|uuid:'.$row['uuid']);
            $reportData = $db->getDataForSysWater($row['uuid']);
            if(isset($reportData['uuid'])){
            	\CommonLog::instance()->getDefaultLogger()->info('full report opt|uuid cancel:'.$row['uuid']);
            	continue;
            }
//             $row['report_uuid'] = $reportData ? $reportData['uuid']:'';
//             if($reportData && $row['is_effective'] == 2){
//                 $data['is_delete'] = 2;
//                 $db->saveReport($data, $row['uuid']);
//                 continue;
//             }
            $waterUuids[] = $row['uuid'];
        }
        
        $orderDatas = $db->getPayOrder($waterUuids);
        $innerDatas = $db->getInnerTransfer($waterUuids);
        $loanDatas = $db->getLoan($waterUuids);
        $planDatas = $db->getPlan($waterUuids);
        $repayDatas = $db->getRepay($waterUuids);
        
        foreach($result as $row){
        	\CommonLog::instance()->getDefaultLogger()->info('full report loop|:'.json_encode($row));
            if(in_array($row['trade_type'] , [1,2,3,4,5,6,7,8,9,10,11,12,13,14])){
                if(!isset($orderDatas[$row['uuid']]) || empty($orderDatas[$row['uuid']])){
                    continue;
                }
                $data = $this->orderData($row, $orderDatas[$row['uuid']]);
            }else if($row['trade_type'] == 15){
                if(!isset($loanDatas[$row['uuid']]) || empty($loanDatas[$row['uuid']])){
                    continue;
                }
                $data = $this->loanData($row, $loanDatas[$row['uuid']]);
            }else if($row['trade_type'] == 17){
                if(!isset($innerDatas[$row['uuid']]) || empty($innerDatas[$row['uuid']])){
                    continue;
                }
                $data = $this->innerData($row, $innerDatas[$row['uuid']]);
            }else if($row['trade_type'] == 18){
                if(!isset($planDatas[$row['uuid']]) || empty($planDatas[$row['uuid']])){
                    continue;
                }
                $data = $this->moneyData($row, $planDatas[$row['uuid']]);
            }else if($row['trade_type'] == 16){
                if(!isset($repayDatas[$row['uuid']]) || empty($repayDatas[$row['uuid']])){
                    continue;
                }
                $data = $this->repayDatas($row, $repayDatas[$row['uuid']]);
            }else{
                continue;
            }
            \CommonLog::instance()->getDefaultLogger()->info('full report save|:'.json_encode($data));
            $data['sys_water_uuid'] = $row['uuid'];
            $db->saveReport($data, $row['report_uuid']);
        }

        return $result;
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
    	$result['interest_rate'] = '';
    	$result['order_create_user_name'] = $orderData['order_data']['order_create_people'];
    	$result['trade_entry_datetime'] = $orderData['order_data']['create_time'];
    	$result['trade_receive_datetime'] = $orderData['order_data']['audit_log'][1]['create_time'] ?? null;
    	$result['order_create_datetime'] = $orderData['create_time'];
    	$result['audit_name_1'] = $orderData['audit_log'][0]['create_user_name'] ?? '';
    	$result['audit_name_3'] = $orderData['audit_log'][1]['deal_user_name'] ?? '';
    	$result['audit_datetime_1'] = $orderData['audit_log'][0]['create_time'] ?? null;
    	$result['audit_datetime_3'] = $orderData['audit_log'][1]['update_time'] ?? null;

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
    	$result['trade_receive_datetime'] = $loanData['order_data']['audit_log'][1]['create_time'] ?? null;
    	$result['order_create_datetime'] = $loanData['create_time'];
    	$result['audit_name_1'] = $loanData['audit_log'][0]['create_user_name'] ?? '';
    	$result['audit_name_3'] = $loanData['audit_log'][2]['deal_user_name'] ?? '';
    	$result['audit_datetime_1'] = $loanData['audit_log'][0]['create_time'] ?? null;
    	$result['audit_datetime_2'] = $loanData['audit_log'][1]['create_time'] ?? null;
    	$result['audit_datetime_3'] = $loanData['audit_log'][2]['update_time'] ?? null;

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
        $result['trade_son_type'] = 17;
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
        $result['interest_rate'] = '';            
        $result['order_create_user_name'] = $innerData['audit_log'][0]['deal_user_name'] ?? '';  
        $result['order_create_datetime'] = $innerData['create_time'];            
        $result['trade_receive_datetime'] = null;
        $result['trade_entry_datetime'] = null;
        $result['audit_name_1'] = $innerData['audit_log'][0]['deal_user_name'] ?? '';
        $result['audit_name_3'] = $innerData['audit_log'][1]['deal_user_name'] ?? '';
        $result['audit_datetime_1'] = $innerData['audit_log'][0]['update_time'] ?? null;
        $result['audit_datetime_3'] = $innerData['audit_log'][1]['update_time'] ?? null;
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
        $result['mature_date'] = $planData['rate_over_date'];
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
    	$result['real_pay_type'] = $repayData['cashs'][0]['real_pay_type'];
    	$result['is_financing'] = 0;
    	$result['financing_dict_key'] = '';
    	$result['financing_dict_value'] = '';
    	$result['trade_status'] = $repayData['is_pay_off'];
//     	$result['mature_date'] = $planData['rate_over_date'];
//     	$result['interest_rate'] = $planData['forecast_annual_income_rate'];<>?        e	
    	$result['order_create_datetime'] = $repayData['audit_log'][0]['create_time'] ?? null;
    	$result['trade_receive_datetime'] = null;
//     	$result['trade_entry_datetime'] = 
    	$result['audit_name_1'] = $repayData['audit_log'][0]['deal_user_name'] ?? '';
    	$result['audit_name_2'] = $repayData['audit_log'][1]['deal_user_name'] ?? '';
    	$result['audit_name_3'] = $repayData['audit_log'][2]['deal_user_name'] ?? '';
    	$result['audit_datetime_1'] = $repayData['audit_log'][0]['create_time'] ?? null;
    	$result['audit_datetime_2'] = $repayData['audit_log'][1]['update_time'] ?? null;
    	$result['audit_datetime_3'] = $repayData['audit_log'][2]['update_time'] ?? null;
    	
    	return $result;
    }
}