<?php
/**
 * 借款事件消费+借款消息添加
 */

namespace money\logic;

use money\model\SysWebNews;
use money\model\SysMailNews;

class LoanConsumer{
    /**
     * @var SysWebNews
     */
    private $webDb;
    /**
     * @var SysMailNews
     */
    private $mailDb;

    public function start($key, $data){
    	\CommonLog::instance()->getDefaultLogger()->info('loan news opt|msg:'.json_encode($data));
        $this->getDb();
        if($key == LOAN_ROUT_AUDIT){
            $this->loan($data);
        }else if($key == LOAN_ROUT_AUDIT_TRANSFER){
            $this->transfer($data);
        }else if($key==REPAY_ROUT_AUDIT_CASH_FLOW){
        	$this->cashFlow($data);
        }
    }

    /**
     * 借款审核
     */
    protected function loan($data){
        switch ($data['node_code']) {
            case 'Loan_order_begin'://发起指令
                $businessSonType = 'audit';
                $title = '接收到借款指令';
                $content = 'deal_user_name您好，你刚接收到一条借款指令（order_num），收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                break;
            case 'Loan_order_approve'://借款指令审核
                if ($data['cur_audit_control_type'] == 2) {
                	$businessSonType = 'transfer.audit';
                	$business_uuid = $data['transfer_uuid'];
                    $title = '借款指令审核通过';
                    $content = 'deal_user_name您好，你刚接收到一条借款调拨交易（transfer_num），收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } elseif ($data['cur_audit_control_type'] == 3) {
                	$businessSonType = 'detail';
                    $title = '借款指令审核已被驳回';
                    $content = 'deal_user_name您好，借款指令（order_num）已被驳回，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            case 'Loan_order_approve_2'://借款指令审核
            	if ($data['cur_audit_control_type'] == 3) {
            		$businessSonType = 'audit';
            		$title = '借款指令审核已被驳回';
            		$content = 'deal_user_name您好，借款调拨交易（order_num）已被驳回，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
            	}
            	break;
            case 'Repay_order_begin'://发起指令
                $businessSonType = 'repay.audit';
                $title = '接收到借款还款指令';
                $content = 'deal_user_name您好，你刚接收到一条借款还款指令（order_num），还款方为collect_main_body，还款金额为amount，请登录系统进行处理';
                break;
//             case 'Repay_order_approve'://借款指令审核
//                 $businessSonType = 'repay.detail';
//                 if ($data['cur_audit_control_type'] == 2) {
//                     $title = '借款还款指令审核通过';
//                     $content = 'deal_user_name您好，借款还款指令（order_num）审核通过，还款方为collect_main_body，付款金额为amount，请登录系统进行处理';
//                 } elseif ($data['cur_audit_control_type'] == 3) {
//                     $title = '借款还款指令审核已被驳回';
//                     $content = 'deal_user_name您好，借款还款指令（order_num）已被驳回，还款方为collect_main_body，付款金额为amount，请登录系统进行处理';
//                 } else {
//                     return;
//                 }
//                 break;
            case 'Repay_order_approve'://发起指令
                $businessSonType = 'repay.change';
                $title = '接收到借款还款指令';
                $content = 'deal_user_name您好，你刚接收到一条借款还款计划修改（order_num），还款方为collect_main_body，借款金额为amount元，请登录系统进行处理';
                break;
            case 'Repay_order_wait_edit'://发起指令
                $businessSonType = 'repay.audit';
                $title = '接收到借款还款指令';
                $content = 'deal_user_name您好，借款还款计划修改（order_num）已被驳回，还款方为collect_main_body，借款金额为amount元，请登录系统进行处理';
                break;
            default:
                return;
        }
        $array['business_type'] = 'loan';
        $array['business_son_type'] = $businessSonType ?? 'detail';
        $array['business_uuid'] = $business_uuid ?? $data['loan_uuid'];
        $array['send_datetime'] = date('Y-m-d H:i:s');
        $array['create_time'] = date('Y-m-d H:i:s');
        $replace = [
            'deal_user_name' => $data['create_user_name'],
            'order_num' => $data['order_num'],
            'collect_main_body' => $data['collect_main_body'],
            'amount' => round($data['amount']/100, 2)
        ];
        if(isset($data['transfer_num'])){
        	$replace['transfer_num'] = $data['transfer_num'];
        }
        if(!empty($data['next_audit_user_infos'])){
            foreach($data['next_audit_user_infos'] as $dvalue){
                if (empty($dvalue['id'])) {
                    continue;
                }
                $replace['deal_user_name'] = $dvalue['name'];
                $array['content'] = str_replace(array_keys($replace), array_values($replace), $content);
                $array['deal_user_id'] = $dvalue['id'];

                $mail = $array;
                $mail['title'] = $title;
                $mail['deal_user_name'] = $dvalue['name'];
                $mail['email_address'] = $dvalue['email'];

                $this->webDb->addMsg($array);
                $this->mailDb->addMsg($mail);
            }
        } else {
            $array['content'] = str_replace(array_keys($replace), array_values($replace), $content);
            $array['deal_user_id'] = $data['create_user_id'];

            $mail = $array;
            $mail['title'] = $title;
            $mail['deal_user_name'] = $data['create_user_name'];
            $mail['email_address'] = $data['create_user_email'];
			
            $this->webDb->addMsg($array);
            if(isset($mail['email_address'])){
            	$this->mailDb->addMsg($mail);
            }
        }
    }

    /**
     * 调拨审核
     */
    protected function transfer($data){
        switch ($data['node_code']) {
            case 'Loan_transfer_begin_wy'://借款调拨交易（网银）
            case 'Loan_transfer_begin_yq'://借款调拨交易（银企）
                $businessSonType = 'transfer.audit2';
                $title = '接收到借款调拨交易';
                $content = 'deal_user_name您好，你刚接收到一条借款调拨交易（transfer_num），收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                break;
            case 'Loan_transfer_approve_wy_1'://借款调拨初审（网银）
                if($data['cur_audit_control_type'] == 2){
                    $businessSonType = 'transfer.audit3';
                    $title = '借款调拨交易审核';
                    $content = 'deal_user_name您好，你刚接收到一条借款调拨交易（transfer_num），收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } elseif ($data['cur_audit_control_type'] == 3) {
                	$businessSonType = 'transfer.audit';
                    $title = '借款调拨交易审核已被驳回';
                    $content = 'deal_user_name您好，借款调拨交易（transfer_num）已被驳回，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } elseif ($data['cur_audit_control_type'] == 4) {
                	$businessSonType = 'transfer.detail';
                    $title = '借款调拨交易审核已被拒绝';
                    $content = 'deal_user_name您好，借款调拨交易（transfer_num）已被拒绝，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } else {
                    return;
                }
                break;

            case 'Loan_transfer_approve_yq_1'://借款调拨初审（银企）
            	if($data['cur_audit_control_type'] == 2){
            		$businessSonType = 'transfer.audit3';
            		$title = '借款调拨交易审核';
            		$content = 'deal_user_name您好，你刚接收到一条借款调拨交易（transfer_num），收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
// 					return;	
            	} elseif ($data['cur_audit_control_type'] == 3) {
            		$businessSonType = 'transfer.audit';
            		$title = '借款调拨交易审核已被驳回';
            		$content = 'deal_user_name您好，借款调拨交易（transfer_num）已被驳回，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
            	} elseif ($data['cur_audit_control_type'] == 4) {
            		$businessSonType = 'transfer.detail';
            		$title = '借款调拨交易审核已被拒绝';
            		$content = 'deal_user_name您好，借款调拨交易（transfer_num）已被拒绝，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
            	} else {
            		return;
            	}
            	break;
            case 'Loan_transfer_approve_wy_2'://借款调拨复审（网银）
                if ($data['cur_audit_control_type'] == 2) {
                    $businessSonType = 'transfer.receipt';
                    $title = '借款调拨交易审核通过';
                    $content = 'deal_user_name您好，借款调拨交易（transfer_num）已审核通过，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } elseif ($data['cur_audit_control_type'] == 3) {
                	$businessSonType = 'transfer.audit';
                    $title = '借款调拨交易审核已被驳回';
                    $content = 'deal_user_name您好，借款调拨交易（transfer_num）已被驳回，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } elseif ($data['cur_audit_control_type'] == 4) {
                    $title = '借款调拨交易审核已被拒绝';
                    $content = 'deal_user_name您好，借款调拨交易（transfer_num）已被拒绝，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            case 'Loan_transfer_approve_yq_2'://借款调拨复审（银企）
                if ($data['cur_audit_control_type'] == 2) {
//                 	$businessSonType = 'transfer.receipt';
//                 	$title = '借款调拨交易审核通过';
//                 	$content = 'deal_user_name您好，借款调拨交易（transfer_num）已审核通过，收款方为collect_main_body，付款金额为amount，请登录系统进行处理，并上传回单';
					return;
                } elseif ($data['cur_audit_control_type'] == 3) {
                	$businessSonType = 'transfer.audit';
                	$title = '借款调拨交易审核已被驳回';
                	$content = 'deal_user_name您好，借款调拨交易（transfer_num）已被驳回，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } elseif ($data['cur_audit_control_type'] == 4) {
                	$title = '借款调拨交易审核已被拒绝';
                	$content = 'deal_user_name您好，借款调拨交易（transfer_num）已被拒绝，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } else {
                	return;
                }
                break;
            case 'Loan_transfer_approve_yq_3'://借款调拨复审（银企）
                if ($data['cur_audit_control_type'] == 2) {
                	$businessSonType = 'transfer.detail';
                    $title = '借款调拨交易打款成功';
                    $content = 'deal_user_name您好，借款调拨交易（transfer_num）银企付款成功，收款方为collect_main_body，付款金额为amount元，请登录系统进行查看';
// 					return;
                } elseif ($data['cur_audit_control_type'] == 3) {
                	$businessSonType = 'transfer.audit3';
                    $title = '借款调拨交易审核失败';
                    $content = 'deal_user_name您好，借款调拨交易（transfer_num）银企付款失败，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } elseif ($data['cur_audit_control_type'] == 4) {
                	$businessSonType = 'transfer.detail';
                    $title = '借款调拨交易审核已被拒绝';
                    $content = 'deal_user_name您好，借款调拨交易（transfer_num）已被拒绝，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            case 'Repay_transfer_begin'://借款还款调拨交易
            	$businessSonType = 'repay.transfer.audit2';
            	$title = '接收到借款还款调拨交易';
            	$content = 'deal_user_name您好，你刚接收到一条借款还款调拨交易（transfer_num），还款方为collect_main_body，还款金额为amount元，请登录系统进行处理';
            	break;
            case 'Repay_transfer_approve_1'://借款还款调拨交易
            	if($data['cur_audit_control_type'] == 2){
            		if($data['real_pay_type']==1){
	                	$businessSonType = 'repay.transfer.receipt';
            		}else{
            			$businessSonType = 'repay.transfer.detail';
            		}
	                $title = '借款还款调拨交易审核通过';
	                $content = 'deal_user_name您好，借款还款调拨交易（transfer_num）已审核通过，还款方为collect_main_body，还款金额为amount元，请登录系统进行处理';
            	}else if($data['cur_audit_control_type'] == 3){
            		$businessSonType = 'repay.transfer.audit';
            		$title = '借款还款调拨交易审核已被驳回';
            		$content = 'deal_user_name您好，借款还款调拨交易（transfer_num）已被驳回，还款方为collect_main_body，还款金额为amount元，请登录系统进行处理';
            	}
                break;
            case 'Repay_transfer_approve_2'://借款还款调拨初审
                $businessSonType = 'repay.transfer.detail';
                if($data['cur_audit_control_type'] == 2){
                    
                    $title = '借款还款调拨交易审核';
                    if($data['real_pay_type']==1){
                   		$businessSonType = 'repay.transfer.receipt';
                    	$content = 'deal_user_name您好，借款还款调拨交易（transfer_num）已审核通过，还款方为collect_main_body，还款金额为amount元，请登录系统进行处理';
                    }else{
                    	$businessSonType = 'repay.transfer.detail';
                    	$content = 'deal_user_name您好，借款还款调拨交易（transfer_num）已审核通过，还款方为collect_main_body，还款金额为amount元，请登录系统进行查看';
                    }
                } elseif ($data['cur_audit_control_type'] == 3) {
                	$businessSonType = 'repay.transfer.audit';
                    $title = '借款还款调拨交易审核已被驳回';
                    $content = 'deal_user_name您好，借款还款调拨交易（transfer_num）已被驳回，还款方为collect_main_body，还款金额为amount元，请登录系统进行处理';
                } elseif ($data['cur_audit_control_type'] == 4) {
                	$businessSonType = 'repay.transfer.detail';	
                    $title = '借款还款调拨交易审核已被拒绝';
                    $content = 'deal_user_name您好，借款还款调拨交易（transfer_num）已被拒绝，还款方为collect_main_body，还款金额为amount元，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            case 'Repay_transfer_approve_3':
                $businessSonType = 'repay.transfer.detail';
                if ($data['cur_audit_control_type'] == 2) {
					$businessSonType = 'repay.transfer.detail';
                    $title = '借款还款调拨交易打款成功';
                    $content = 'deal_user_name您好，借款还款调拨交易（transfer_num）银企付款成功，还款方为collect_main_body，付款金额为amount元，请登录系统进行查看';
                } elseif ($data['cur_audit_control_type'] == 3) {
                	$businessSonType = 'repay.transfer.audit2';
                    $title = '借款还款调拨交易审核打款失败';
                    $content = 'deal_user_name您好，借款还款调拨交易（transfer_num）银企付款失败，还款方为collect_main_body，还款金额为amount元，请登录系统进行处理';
                } elseif ($data['cur_audit_control_type'] == 4) {
                    $title = '借款还款调拨交易审核已被拒绝';
                    $content = 'deal_user_name您好，借款还款调拨交易（transfer_num）已被拒绝，还款方为collect_main_body，还款金额为amount元，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            default:
                return;
        }
        $array['business_type'] = 'loan';
        $array['business_son_type'] = $businessSonType ?? 'transfer.detail';
        $array['business_uuid'] = $data['transfer_uuid'];
        $array['send_datetime'] = date('Y-m-d H:i:s');
        $array['create_time'] = date('Y-m-d H:i:s');
        $replace = [
            'deal_user_name' => $data['create_user_name'],
            'transfer_num' => $data['transfer_num'],
            'collect_main_body' => $data['collect_main_body'],
            'amount' => round($data['amount']/100, 2)
        ];
        if(!empty($data['next_audit_user_infos'])){
            foreach($data['next_audit_user_infos'] as $dvalue){
                if (empty($dvalue['id'])) {
                    continue;
                }
                $replace['deal_user_name'] = $dvalue['name'];
                $array['content'] = str_replace(array_keys($replace), array_values($replace), $content);
                $array['deal_user_id'] = $dvalue['id'];

                $mail = $array;
                $mail['title'] = $title;
                $mail['deal_user_name'] = $dvalue['name'];
                $mail['email_address'] = $dvalue['email'];

                $this->webDb->addMsg($array);
                $this->mailDb->addMsg($mail);
            }
        } else {
            $array['content'] = str_replace(array_keys($replace), array_values($replace), $content);
            $array['deal_user_id'] = $data['create_user_id'];

            $mail = $array;
            $mail['title'] = $title;
            $mail['deal_user_name'] = $data['create_user_name'];
            $mail['email_address'] = $data['create_user_email'];

            $this->webDb->addMsg($array);
            $this->mailDb->addMsg($mail);
        }
    }

    protected function cashFlow($data){
    	switch ($data['node_code']) {
    		case 'Repay_flow_cash_edit_begin'://发起指令
    			$businessSonType = 'repay.change.audit';
    			$title = '接收到借款还款计划修改';
    			$content = 'deal_user_name您好，你刚接收到一条借款还款计划修改（order_num），还款方为collect_main_body，借款金额为amount元，请登录系统进行处理';
    			break;
    		case 'Repay_flow_cash_edit_approve'://借款指令审核
    			if ($data['cur_audit_control_type'] == 2) {
    				$businessSonType = 'repay.transfer.detail';
    				$business_uuid = $data['transfer_uuid'];
    				$title = '借款还款计划修改审核通过';
    				$content = 'deal_user_name您好，借款还款计划修改（order_num）已审核通过，还款方为collect_main_body，借款金额为amount元，请登录系统进行处理';
    			} elseif ($data['cur_audit_control_type'] == 3) {
    				$businessSonType = 'repay.change';
    				$title = '借款还款计划修改审核已被驳回';
    				$content = 'deal_user_name您好，借款还款计划修改（order_num）已被驳回，还款方为collect_main_body，借款金额为amount元，请登录系统进行处理';
    			} else {
    				return;
    			}
    			break;
    		default:
    			return;
    	}
    	$array['business_type'] = 'loan';
    	$array['business_son_type'] = $businessSonType ?? 'detail';
    	$array['business_uuid'] = $business_uuid ?? $data['transfer_uuid'];
    	$array['send_datetime'] = date('Y-m-d H:i:s');
    	$array['create_time'] = date('Y-m-d H:i:s');
    	$replace = [
    	'deal_user_name' => $data['create_user_name'],
    	'order_num' => $data['transfer_num'],
    	'collect_main_body' => $data['collect_main_body'],
    	'amount' => round($data['amount']/100, 2)
    	];
    	if(isset($data['transfer_num'])){
    		$replace['transfer_num'] = $data['transfer_num'];
    	}
    	if(!empty($data['next_audit_user_infos'])){
    		foreach($data['next_audit_user_infos'] as $dvalue){
    			if (empty($dvalue['id'])) {
    				continue;
    			}
    			$replace['deal_user_name'] = $dvalue['name'];
    			$array['content'] = str_replace(array_keys($replace), array_values($replace), $content);
    			$array['deal_user_id'] = $dvalue['id'];
    	
    			$mail = $array;
    			$mail['title'] = $title;
    			$mail['deal_user_name'] = $dvalue['name'];
    			$mail['email_address'] = $dvalue['email'];
    	
    			$this->webDb->addMsg($array);
    			$this->mailDb->addMsg($mail);
    		}
    	} else {
    		$array['content'] = str_replace(array_keys($replace), array_values($replace), $content);
    		$array['deal_user_id'] = $data['create_user_id'];
    	
    		$mail = $array;
    		$mail['title'] = $title;
    		$mail['deal_user_name'] = $data['create_user_name'];
    		$mail['email_address'] = $data['create_user_email'];
    			
    		$this->webDb->addMsg($array);
    		if(isset($mail['email_address'])){
    			$this->mailDb->addMsg($mail);
    		}
    	}
    }
    
    protected function getDb(){
        if(!$this->webDb){
            $this->webDb = new SysWebNews();
        }
        if(!$this->mailDb){
            $this->mailDb = new SysMailNews();
        }
    }        
}