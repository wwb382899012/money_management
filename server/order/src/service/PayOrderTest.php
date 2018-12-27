<?php
use money\service\BaseService;

class PayOrderTest extends BaseService{
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
				// 7、OA批量调用付款指令
				$ret = $this->batchCallPayOrder();
				break;
			case 8:
				// 8、初始化OA付款业务数据
				$ret = $this->initOaPayBusiness();
				break;
			case 9:
				// 9、财务测试数据生成
				$ret = $this->fdateCreate();
				break;
			case 10:
				// 10、批量通知OA付款状态
				$ret = $this->batchNotifyOa();
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
        !isset($params["order_pay_type"]) && $params["order_pay_type"] = 1;
        !isset($params["pay_main_body"]) && $params["pay_main_body"] = " 深圳云加科技有限公司 ";
        !isset($params["collect_main_body"]) && $params["collect_main_body"] = "深圳云加科技有限公司";
        !isset($params["collect_account_name"]) && $params["collect_account_name"] = "建行收款账号";
        !isset($params["collect_bank_account"]) && $params["collect_bank_account"] = "44201501100052566455";
//         !isset($params["collect_bank_desc"]) && $params["collect_bank_desc"] = "中国建设银行股份有限公司深圳黄贝岭支行";
//         !isset($params["collect_bank_name"]) && $params["collect_bank_name"] = "建设银行";
//         !isset($params["collect_city_name"]) && $params["collect_city_name"] = "中国";
        !isset($params["amount"]) && $params["amount"] = 111;
        !isset($params["timestamp"]) && $params["timestamp"] = time();
        !isset($params["order_create_people"]) && $params["order_create_people"] = "aabb";
		$key = "aabb";
		$secret = secretGet($params , $key );
		$params["secret"] = $secret;
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.order.PayOrder" , $params);
		return $ret;
	}
	
	
	function PayOrderQuery(){
		$instance_id = $this->m_request['instance_id'];
		$params = array(
				"system_flag" => "test",
				"timestamp" => time()
		);
		$key = "aabb";
		$secret = secretGet($params , $key );
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

    protected function batchCallPayOrder(){
        $conditions = $this->m_request;
        !isset($conditions['begin_time']) && $conditions['begin_time'] = 1;
        $model = new \money\logic\OaPayLogic();
        return $model->batchCallPayOrder($conditions);
    }

    protected function initOaPayBusiness()
    {
        $model = new \money\model\OaPayBusiness();
        return $model->initData();
    }

    protected function batchNotifyOa(){
	    $conditions = $this->m_request;
	    !isset($conditions['begin_time']) && $conditions['begin_time'] = 1;
        $model = new \money\logic\OaPayLogic();
        return $model->batchNotifyOa($conditions);
    }

    function fdateCreate(){
    	$bodys = ["深圳前海卓越融资租赁有限公司","博实（深圳）商业保理有限公司","博实（深圳）商业保理有限公司","嘉佑生活（深圳）电子商务有限公司","中智诚科技产业发展有限公司","深圳前海泰丰能源有限公司","中优国聚能源科技有限公司","加油宝金融科技服务（深圳）有限公司"];
    	$sql = 'select m.full_name,a.bank_account,a.account_name from m_main_body m join m_bank_account a on m.uuid = a.main_body_uuid where m.full_name in ("'.implode('","',$bodys).'")';
    	$obj = new \money\model\BankAccount();
    	$ret = $obj->query($sql);
    	$i = 0;
    	foreach($ret as $r){    		
    		foreach($bodys as $b){
    			$params = [
	    			"system_flag"=>"test",
	    			"out_order_num"=>time()+$i,
	    			"order_pay_type"=>rand(1,14),
	    			"pay_main_body"=>$b,
	    			"collect_main_body"=>$r['full_name'],
	    			"collect_account_name"=>$r['account_name'],
	    			"collect_bank_account"=>$r['bank_account'],
	    			"amount"=>rand(1,10000),
	    			"timestamp"=>time(),
	    			"order_create_people"=>"aabb"
    			];
    			$key = "aabb";
    			$secret = secretGet($params , $key );
    			$params["secret"] = $secret;
    			$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.order.PayOrder" , $params);
    			$i++;
    		}
    	}
    	
  		
    	
    }
}