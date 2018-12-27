<?php
/**
 * 理财事件消费+理财消息添加
 */

namespace money\logic;

use money\model\SysWebNews;
use money\model\SysMailNews;

class FinancialConsumer{
    /**
     * @var SysWebNews
     */
    private $webDb;
    /**
     * @var SysMailNews
     */
    private $mailDb;
    public function start($key, $data){
        $this->getDb();
        if($key == FINANCIAL_ROUT_AUDIT){
            $this->audit($data);
        }else if($key == FINANCIAL_ROUT_REDEMPTION_AUDIT){
            $this->flow($data);
        }
    }

    /**
     * 理财产品审核消息
     */
    protected function audit($data){
        switch ($data['node_code']) {
            case 'pay_type_1_node_no_1'://购买理财申请交易指令（网银）
            case 'pay_type_2_node_no_1'://购买理财申请交易指令（银企）
                $businessSonType = 'audit';
                $title = '接收到购买理财交易指令';
                $content = 'deal_user_name您好，你刚接收到一条购买理财申请交易指令（money_manager_plan_num），投资产品为money_manager_product_name，投资金额为amount，请登录系统进行处理';
                break;
            case 'pay_type_1_node_no_2'://初审（网银）
            case 'pay_type_2_node_no_2'://初审（银企）
                if ($data['cur_audit_control_type'] == 2) {
                    $businessSonType = 'audit2';
                    $title = '购买理财交易交易审核';
                    $content = 'deal_user_name您好，你刚接收到一条购买理财交易（money_manager_plan_num），投资产品为money_manager_product_name，投资金额为amount，请登录系统进行处理';
                } elseif($data['cur_audit_control_type'] == 3) {
                    $title = '购买理财交易已被驳回';
                    $content = 'deal_user_name您好，购买理财（money_manager_plan_num）已被驳回，投资产品为money_manager_product_name，投资金额为amount，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            case 'pay_type_1_node_no_3'://复审（网银）
                if ($data['cur_audit_control_type'] == 2) {
                    $businessSonType = 'receipt';
                    $title = '购买理财交易审核通过';
                    $content = 'deal_user_name您好，购买理财（money_manager_plan_num）已审核通过，投资产品为money_manager_product_name，投资金额为amount，请登录系统进行处理';
                } elseif($data['cur_audit_control_type'] == 3) {
                    $title = '购买理财交易已被驳回';
                    $content = 'deal_user_name您好，购买理财（money_manager_plan_num）已被驳回，投资产品为money_manager_product_name，投资金额为amount，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            case 'pay_type_2_node_no_3'://复审（银企）
                if ($data['cur_audit_control_type'] == 2) {
                    $title = '购买理财交易审核通过';
                    $content = 'deal_user_name您好，购买理财（money_manager_plan_num）已审核通过，投资产品为money_manager_product_name，投资金额为amount，请登录系统进行处理';
                } elseif($data['cur_audit_control_type'] == 3) {
                    $title = '购买理财交易已被驳回';
                    $content = 'deal_user_name您好，购买理财（money_manager_plan_num）已被驳回，投资产品为money_manager_product_name，投资金额为amount，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            default:
                return;
        }
        $array['business_type'] = 'financial';
        $array['business_son_type'] = $businessSonType ?? 'detail';
        $array['business_uuid'] = $data['plan_uuid'];
        $array['send_datetime'] = date('Y-m-d H:i:s');
        $array['create_time'] = date('Y-m-d H:i:s');
        $replace = [
            'deal_user_name' => $data['create_user_name'],
            'money_manager_plan_num' => $data['money_manager_plan_num'],
            'money_manager_product_name' => $data['money_manager_product_name'],
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
	            if(isset($mail['email_address'])){
	           		$this->mailDb->addMsg($mail);
	            }
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
     * 现金流的变动
     */    
    protected function flow($data){
        switch ($data['node_code']) {
            case 'redemption_audit_node_no_1'://赎回发起
                $businessSonType = 'redemption.audit';
                $title = '理财赎回交易';
                $content = 'deal_user_name您好，你刚接收到一条理财赎回交易（money_manager_plan_num），投资产品为money_manager_product_name，投资金额为amount，请登录系统进行处理';
                break;
            case 'redemption_audit_node_no_2'://赎回审核
                if ($data['cur_audit_control_type'] == 2) {
                    $businessSonType = 'redemption.receipt';
                    $title = '理财赎回交易审核通过';
                    if ($data['term_type'] == 2) {
                        $content = 'deal_user_name您好，理财赎回交易（money_manager_plan_num）已被赎回，投资产品为money_manager_product_name，投资金额为amount，请登录系统进行查看并上传回单';
                    } else {
                        $content = 'deal_user_name您好，理财赎回交易（money_manager_plan_num）已审核通过，投资产品为money_manager_product_name，投资金额为amount，请登录系统进行查看并上传回单';
                    }
                } elseif($data['cur_audit_control_type'] == 3) {
                    $title = '理财赎回交易审核已被驳回';
                    $content = 'deal_user_name您好，理财赎回交易（money_manager_plan_num）已被驳回，投资产品为money_manager_product_name，投资金额为amount，请登录系统进行处理';
                } else {
                    return;
                }
                break;
            case 'redemption_audit_node_no_4':
                $title = '理财赎回交易已完结';
                $content = 'deal_user_name您好，理财赎回交易（money_manager_plan_num）已完结，投资产品为money_manager_product_name，投资金额为amount，请登录系统进行查看';
                break;
            default:
                return;
        }
        $array['business_type'] = 'financial';
        $array['business_son_type'] = $businessSonType ?? 'detail';
        $array['business_uuid'] = $data['plan_uuid'];
        $array['send_datetime'] = date('Y-m-d H:i:s');
        $array['create_time'] = date('Y-m-d H:i:s');
        $replace = [
            'deal_user_name' => $data['create_user_name'],
            'money_manager_plan_num' => $data['money_manager_plan_num'],
            'money_manager_product_name' => $data['money_manager_product_name'],
            'amount' => round($data['amount']/100, 2)
        ];
        if(!empty($data['next_audit_user_infos'])){
            $this->webDb->deleteCashAuditMsg($data['plan_uuid']);
            $this->mailDb->deleteCashAuditMsg($data['plan_uuid']);

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
            $this->webDb->deleteCashMsg($data['plan_uuid']);
            $this->mailDb->deleteCashMsg($data['plan_uuid']);

            $array['content'] = str_replace(array_keys($replace), array_values($replace), $content);;
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