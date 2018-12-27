<?php
/**
 * 内部调拨事件消费+内部调拨消息添加
 */

namespace money\logic;

use money\model\SysWebNews;
use money\model\SysMailNews;

class InnerConsumer{
    /**
     * @var SysWebNews
     */
    private $webDb;
    /**
     * @var SysMailNews
     */
    private $mailDb;

    public function start($key, $data){
    	\CommonLog::instance()->getDefaultLogger()->info('inner news opt|msg:'.json_encode($data));
        $this->getDb();
        if($key == INNER_ROUT_AUDIT){
            $this->inner($data);
        }
    }

    /**
     * 内部调拨审核
     */
    protected function inner($data){
        switch ($data['node_code']) {
            case 'Inner_transfer_begin_wy'://内部调拨交易（网银）
            	$businessSonType = 'audit2';
            	$title = '接收到内部调拨交易';
            	$content = 'deal_user_name您好，你刚接收到一条内部调拨交易（order_num），收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
            	$copy_content = 'copy_user_name您好，内部调拨交易（order_num）已提交，收款方为collect_main_body，付款金额为amount，请登录系统进行查看';
            	break;
            case 'Inner_transfer_begin_yq'://内部调拨交易（银企）
                $businessSonType = 'audit2';
                $title = '接收到内部调拨交易';
                $content = 'deal_user_name您好，你刚接收到一条内部调拨交易（order_num），收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
                $copy_content = 'copy_user_name您好，内部调拨交易（order_num）已提交，收款方为collect_main_body，付款金额为amount，请登录系统进行查看';
                break;
//             case 'Inner_transfer_approve_wy_1'://内部调拨初审（网银）
//             case 'Inner_transfer_approve_yq_1'://内部调拨初审（银企）
//                 if($data['cur_audit_control_type'] == 2){
//                     $businessSonType = 'audit2';
//                     $title = '内部调拨交易审核';
//                     $content = 'deal_user_name您好，你刚接收到一条内部调拨交易（order_num），收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
                    
//                 } elseif ($data['cur_audit_control_type'] == 3) {
//                     $title = '内部调拨交易审核已被驳回';
//                     $content = 'deal_user_name您好，内部调拨交易（order_num）已被驳回，收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
//                 } elseif ($data['cur_audit_control_type'] == 4) {
//                     $title = '内部调拨交易审核已被拒绝';
//                     $content = 'deal_user_name您好，内部调拨交易（order_num）已被拒绝，收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
//                 } else {
//                     return;
//                 }
//                 break;
            case 'Inner_transfer_approve_wy_2'://内部调拨复审（网银）
                if ($data['cur_audit_control_type'] == 2) {
                    $businessSonType = 'receipt';
                    $title = '内部调拨交易审核通过';
                    $content = 'deal_user_name您好，内部调拨交易（order_num）已审核通过，收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
                    $copy_content = 'copy_user_name您好，内部调拨交易（order_num）已审核通过，收款方为collect_main_body，付款金额为amount，请登录系统进行查看';
                } elseif ($data['cur_audit_control_type'] == 3) {
                    $title = '内部调拨交易审核已被驳回';
                    $content = 'deal_user_name您好，内部调拨交易（order_num）已被驳回，收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
                    $copy_content = 'copy_user_name您好，内部调拨交易（order_num）已被驳回，收款方为collect_main_body，付款金额为amount，请登录系统进行查看';
                } elseif ($data['cur_audit_control_type'] == 4) {
                    $title = '内部调拨交易审核已被拒绝';
                    $content = 'deal_user_name您好，内部调拨交易（order_num）已被拒绝，收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
                    $copy_content = 'copy_user_name您好，内部调拨交易（order_num）已被拒绝，收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            case 'Inner_transfer_approve_yq_2'://内部调拨复审（银企）
                if ($data['cur_audit_control_type'] == 2) {
                    $title = '内部调拨交易审核通过';
                    $content = 'deal_user_name您好，内部调拨交易（order_num）已审核通过，收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
                    $copy_content = 'copy_user_name您好，内部调拨交易（order_num）已审核通过，收款方为collect_main_body，付款金额为amount，请登录系统进行查看';
                } elseif ($data['cur_audit_control_type'] == 3) {
                    $title = '内部调拨交易审核已被驳回';
                    $content = 'deal_user_name您好，内部调拨交易（order_num）已被驳回，收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
                    $copy_content = 'copy_user_name您好，内部调拨交易（order_num）已被驳回，收款方为collect_main_body，付款金额为amount，请登录系统进行查看';
                } elseif ($data['cur_audit_control_type'] == 4) {
                    $title = '内部调拨交易审核已被拒绝';
                    $content = 'deal_user_name您好，内部调拨交易（order_num）已被拒绝，收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
                    $copy_content = 'copy_user_name您好，内部调拨交易（order_num）已被拒绝，收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            case 'Inner_transfer_approve_yq_3':
            	if ($data['cur_audit_control_type'] == 3) {
            		$businessSonType = 'audit2';
            		$title = '内部调拨交易打款失败';
            		$content = 'deal_user_name您好，内部调拨交易（order_num）银企付款失败，收款方为collect_main_body，付款金额为amount，请登录系统进行处理';
       
            	}
            	if ($data['cur_audit_control_type'] == 2) {
            		$title = '内部调拨交易打款成功';
            		$content = 'deal_user_name您好，内部调拨交易（order_num）银企付款成功，收款方为collect_main_body，付款金额为amount，请登录系统进行查看';
            		$copy_content = 'copy_user_name您好，内部调拨交易（order_num）银企付款成功，收款方为collect_main_body，付款金额为amount，请登录系统进行查看';
            	}
            	break;
            default:
                return;
        }
        $array['business_type'] = 'inner';
        $array['business_son_type'] = $businessSonType ?? 'detail';
        $array['business_uuid'] = $data['inner_uuid'];
        $array['send_datetime'] = date('Y-m-d H:i:s');
        $array['create_time'] = date('Y-m-d H:i:s');
        $replace = [
            'deal_user_name' => $data['create_user_name'],
            'order_num' => $data['order_num'],
            'collect_main_body' => $data['collect_main_body'],
            'amount' => round($data['amount']/100, 2)
        ];
        if(!empty($data['next_audit_user_infos'])&&!empty($content)){
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
        if(!empty($data['approve_users'])&&isset($copy_content)){
        	foreach($data['approve_users'] as $dvalue){
        		if (empty($dvalue['id'])) {
        			continue;
        		}
        		$array['business_son_type'] = 'detail';
        		$replace['copy_user_name'] = $dvalue['name'];
        		$array['content'] = str_replace(array_keys($replace), array_values($replace), $copy_content);
        		$array['deal_user_id'] = $dvalue['id'];
        	
        		$mail = $array;
        		$mail['title'] = $title;
        		$mail['deal_user_name'] = $dvalue['name'];
        		$mail['email_address'] = $dvalue['email'];
        	
        		$this->webDb->addMsg($array);
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