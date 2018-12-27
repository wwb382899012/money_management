<?php
/**
 * 借款事件消费+借款消息添加
 */

namespace money\logic;

use money\model\SysWebNews;
use money\model\SysMailNews;

class OrderConsumer{
    /**
     * @var SysWebNews
     */
    private $webDb;
    /**
     * @var SysMailNews
     */
    private $mailDb;

    public function start($key, $data){
    	\CommonLog::instance()->getDefaultLogger()->info('order news opt|msg:'.json_encode($data));
        $this->getDb();
        if($key == ORDER_ROUT_AUDIT){
            $this->pay($data);
        }else if($key == ORDER_ROUT_AUDIT_TRANSFER){
            $this->transfer($data);
        }
    }

    /**
     * 一般付款审核
     */
    protected function pay($data){
        switch ($data['node_code']) {
            case 'Pay_order_begin'://付款指令
                $businessSonType = 'audit';
                $title = '接收到付款指令';
                $content = 'deal_user_name您好，你刚接收到一条付款指令（order_num），收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                break;
            case 'Pay_order_approve'://付款指令审核
                if ($data['cur_audit_control_type'] == 2) {
                	$businessSonType = 'transfer.audit';
                	$business_uuid = $data['transfer_uuid'];
                    $title = '付款指令审核通过';
                    $content = 'deal_user_name您好，你刚接收到一条付款调拨交易（transfer_num），收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            case 'Pay_order_approve_2':
            	if($data['cur_audit_control_type'] == 3) {
            		$businessSonType = 'audit';
                    $title = '付款指令审核已被驳回';
                    $content = 'deal_user_name您好，付款调拨交易（transfer_num）已被驳回，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
            	}
            	break;
            default:
                return;
        }
        $array['business_type'] = 'order';
        $array['business_son_type'] = $businessSonType ?? 'detail';
        $array['business_uuid'] = $business_uuid ?? $data['pay_uuid'];
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
            $this->mailDb->addMsg($mail);
        }
    }

    /**
     * 调拨审核
     */
    protected function transfer($data){
        switch ($data['node_code']) {
            case 'Pay_transfer_begin_wy'://付款调拨交易（网银）
            case 'Pay_transfer_begin_yq'://付款调拨交易（银企）
                $businessSonType = 'transfer.audit2';
                $title = '接收到付款调拨交易';
                $content = 'deal_user_name您好，你刚接收到一条付款调拨交易（transfer_num），收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                break;
            case 'Pay_transfer_approve_wy_1'://付款调拨初审（网银）
            	if($data['cur_audit_control_type'] == 2){
            		$businessSonType = 'transfer.receipt';
            		$title = '付款调拨交易审核';
            		if($data['trade_type']==5){
            			$content = 'deal_user_name您好，付款调拨交易（transfer_num）已审核通过，收款方为collect_main_body，付款金额为amount元，请登录系统进行查看';
            		}else{
            			$content = 'deal_user_name您好，付款调拨交易（transfer_num）已审核通过，收款方为collect_main_body，付款金额为amount元，请登录系统进行查看，并上传回单';
            		}
            	} elseif ($data['cur_audit_control_type'] == 3) {
            		$businessSonType = 'transfer.audit';
            		$title = '付款调拨交易审核已被驳回';
            		$content = 'deal_user_name您好，付款调拨交易（transfer_num）已被驳回，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
            	} elseif ($data['cur_audit_control_type'] == 4) {
            		$businessSonType = 'transfer.detail';
            		$title = '付款调拨交易审核已被拒绝';
            		$content = 'deal_user_name您好，付款调拨交易（transfer_num）已被拒绝，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
            	} else {
            		return;
            	}
            	break;
            case 'Pay_transfer_approve_yq_1'://付款调拨初审（银企）
                if($data['cur_audit_control_type'] == 2){
                    $businessSonType = 'transfer.detail';
                    $title = '付款调拨交易审核通过';
                    $content = 'deal_user_name您好，付款调拨交易（transfer_num）已审核通过，收款方为collect_main_body，付款金额为amount元，请登录系统进行查看';
// 					return;
                } elseif ($data['cur_audit_control_type'] == 3) {
                	$businessSonType = 'transfer.audit';
                    $title = '付款调拨交易审核已被驳回';
                    $content = 'deal_user_name您好，付款调拨交易（transfer_num）已被驳回，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } elseif ($data['cur_audit_control_type'] == 4) {
                	$businessSonType = 'transfer.detail';
                    $title = '付款调拨交易审核已被拒绝';
                    $content = 'deal_user_name您好，付款调拨交易（transfer_num）已被拒绝，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            case 'Pay_transfer_approve_wy_2'://付款调拨复审（网银）
//                 if ($data['cur_audit_control_type'] == 2) {
// //                     $businessSonType = 'transfer.receipt';
// //                     $title = '付款调拨交易审核通过';
// //                     $content = 'deal_user_name您好，付款调拨交易（transfer_num）已审核通过，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理，并上传回单';
// 					return;
//                 } elseif ($data['cur_audit_control_type'] == 3) {
//                 	$businessSonType = 'transfer.audit';
//                     $title = '付款调拨交易审核已被驳回';
//                     $content = 'deal_user_name您好，付款调拨交易（transfer_num）已被驳回，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
//                 } elseif ($data['cur_audit_control_type'] == 4) {
//                 	$businessSonType = 'transfer.detail';
//                     $title = '付款调拨交易审核已被拒绝';
//                     $content = 'deal_user_name您好，付款调拨交易（transfer_num）已被拒绝，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
//                 } else {
//                     return;
//                 }
				return;
                
            case 'Pay_transfer_approve_yq_2'://付款调拨复审（银企）
                if ($data['cur_audit_control_type'] == 2) {
                	$businessSonType = 'transfer.detail';
                    $title = '付款调拨交易审核通过';
                    $content = 'deal_user_name您好，付款调拨交易（transfer_num）银企付款成功，收款方为collect_main_body，付款金额为amount元，请登录系统进行查看';
// 					return;
                } elseif ($data['cur_audit_control_type'] == 3) {
                	$businessSonType = 'transfer.audit2';
                    $title = '付款调拨交易打款失败';
                    $content = 'deal_user_name您好，付款调拨交易（transfer_num）银企付款失败，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } elseif ($data['cur_audit_control_type'] == 4) {
                	$businessSonType = 'transfer.detail';
                    $title = '付款调拨交易审核已被拒绝';
                    $content = 'deal_user_name您好，付款调拨交易（transfer_num）已被拒绝，收款方为collect_main_body，付款金额为amount元，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            default:
                return;
        }
        $array['business_type'] = 'order';
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
        	if($data['node_code']=='Pay_transfer_approve_yq_2'&&$data['cur_audit_control_type'] == 2){
        		$businessSonType = 'transfer.detail';
        	}
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

    protected function getDb(){
        if(!$this->webDb){
            $this->webDb = new SysWebNews();
        }
        if(!$this->mailDb){
            $this->mailDb = new SysMailNews();
        }
    }        
}