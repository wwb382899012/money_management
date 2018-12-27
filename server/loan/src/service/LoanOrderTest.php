<?php
use money\service\BaseService;
use money\model\LoanOrder;
use money\model\BankAccount;

class LoanOrderTest extends BaseService{

	//单元测试
	function exec(){
		
		
		
		
		$type = $this->m_request["type"];
		switch ($type){
			case 1:
				// 1、下一个借款订单
				$ret = $this->PayOrder();
				break;
			case 2:
				// 2、指令状态查询
				$ret = $this->PayOrderQuery();
				break;
			case 3:
				// 3、借款订单列表
				$ret = $this->PayOrderList();
				break;
			case 4:
				// 4、借款订单查询
				$ret = $this->PayOrderDetail();
				break;
			case 5:
				// 5、调拨列表
				$ret = $this->PayTransferList();
				break;
			case 6:
				// 6、调拨详情
				$ret = $this->PayTransferDetail();
				break;
			case 7:
				$ret = $this->RepayOrder();
				break;
			case 8:
				$ret =  $this->fdateCreate();
				break;
			default:
				$ret = ["error,type not exists!"];
				break;
		}

		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
	
	function PayOrder(){
        $params = $this->m_request;
        !isset($params["system_flag"]) && $params["system_flag"] = "test";
        !isset($params["out_order_num"]) && $params["out_order_num"] = time();
        !isset($params["loan_type"]) && $params["loan_type"] = 1;
        !isset($params["loan_main_body"]) && $params["loan_main_body"] = "深圳云加科技有限公司";
        !isset($params["collect_main_body"]) && $params["collect_main_body"] = "深圳云加科技有限公司";
        !isset($params["collect_account_name"]) && $params["collect_account_name"] = "公司九八";
        !isset($params["collect_bank_account"]) && $params["collect_bank_account"] = "44201501100052565007";
        !isset($params["amount"]) && $params["amount"] = 111;
        !isset($params["loan_date"]) && $params["loan_date"] = date('Y-m-d H:i:s');
        !isset($params["forecast_date"]) && $params["forecast_date"] = date('Y-m-d H:i:s');
        !isset($params["rate"]) && $params["rate"] = 0.1;
        !isset($params["timestamp"]) && $params["timestamp"] = time();
        !isset($params["order_create_people"]) && $params["order_create_people"] = "aabb";
		$key = "aabb";
		$secret = secretGet($params , $key );
		$params["secret"] = $secret;
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.loan.LoanOrder" , $params);
		return $ret;
	}
	
	function fdateCreate(){
		$bodys = ["深圳前海卓越融资租赁有限公司","博实（深圳）商业保理有限公司","博实（深圳）商业保理有限公司","嘉佑生活（深圳）电子商务有限公司","中智诚科技产业发展有限公司","深圳前海泰丰能源有限公司","中优国聚能源科技有限公司","加油宝金融科技服务（深圳）有限公司"];
		$sql = 'select m.full_name,a.bank_account,a.account_name from m_main_body m join m_bank_account a on m.uuid = a.main_body_uuid where m.full_name in ("'.implode('","',$bodys).'")';
		$obj = new BankAccount();
		$ret = $obj->query($sql);
		$i = 0;
		foreach($ret as $r){
			foreach($bodys as $b){
				$params = [
				"system_flag"=>"test",
				"out_order_num"=>time()+$i,
				"loan_type"=>1,
				"loan_main_body"=>$b,
				"collect_main_body"=>$r['full_name'],
				"collect_account_name"=>$r['account_name'],
				"collect_bank_account"=>$r['bank_account'],
				"amount"=>rand(1,10000),
				'loan_datetime'=>date('Y-m-d'),
				'forecast_datetime'=>date('Y-m-d'),
				'rate'=>0.1,
				"timestamp"=>time(),
				"order_create_people"=>"aabb"
						];
				$key = "aabb";
				$secret = secretGet($params , $key );
				$params["secret"] = $secret;
				$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.loan.LoanOrder" , $params);
				$i++;
			}
		}
	}
	
	function RepayOrder(){
		$params = $this->m_request;
		!isset($params["system_flag"]) && $params["system_flag"] = "test";
		!isset($params["loan_out_order_num"]) && $params["loan_out_order_num"] = "1535541857";
		!isset($params["out_order_num"]) && $params["out_order_num"] = time();
		!isset($params["amount"]) && $params["amount"] = 1;
		!isset($params["order_create_people"]) && $params["order_create_people"] = "sun";
		!isset($params["amount"]) && $params["amount"] = 1;
		!isset($params["require_repay_date"]) && $params["require_repay_date"] = date('Y-m-d');
		!isset($params["repay_type"]) && $params["repay_type"] = 2;
		!isset($params["timestamp"]) && $params["timestamp"] = time();
		
		$key = "aabb";
		$secret = secretGet($params , $key );
		$params["secret"] = $secret;
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.loan.RepayOrder" , $params);
		return $ret;
	}
	
	function PayOrderQuery(){
		$instance_id = $this->m_request['instance_id'];
		$params = array(
				"system_flag" => "test",
				"timestamp" => time()
		);
		$key = "aabb";
		$secret = $this->secretGet($params , $key );
		$params["secret"] = $secret;
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.order.PayOrderQuery" , $params);
		return $ret;
	}
	
	function PayOrderList(){
		$params = array(
				"system_flag" => "test",
				"timestamp" => time()
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.order.PayOrderList" , $params);
		return $ret;
	}
	
	function PayOrderDetail(){

		$instance_id = $this->m_request['instance_id'];
		$params = array(
				"uuid" => $instance_id
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.order.PayOrderList" , $params);
		return $ret;
	}
	
	function PayTransferList(){
	
		$params = array(
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.order.PayTransferList" , $params);
		return $ret;
	}
	
	function PayTransferDetail(){
		$uuid = $this->m_request['uuid'];
		$params = array(
				'uuid' => $uuid
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.order.PayTransferDetail" , $params);
		return $ret;
	}
	
	
	//加密验证
	function secretGet($params , $secretKey = 'aabb') {
	    ksort($params);
	    foreach($params as $key => $value) {
	        if ($key == 'secret') {
	            continue;
	        }
	        $strs[] = $key . '=' . $value;
	    }
	    $str = implode('&' , $strs).$secretKey;
	    return sha1($str);
	}
}