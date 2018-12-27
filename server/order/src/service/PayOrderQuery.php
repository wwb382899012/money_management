<?php
use money\service\OrderBaseService;
/**
*	订单查询(接口)
*	@author sun
*	@since 2018-03-10
*/
use money\base\RSAUtil;
use money\model\SystemInfo;
use money\model\PayOrder;

class PayOrderQuery extends OrderBaseService
{
    protected $rule = [
        //'sessionToken' => 'require',
        'system_flag' => 'require',
        'order_status' => 'integer',
        'apply_begin_time' => 'date',
        'apply_end_time' => 'date',
    ];

    public function exec()
    {
    	$obj = new PayOrder();
        $params = $this->m_request;
        $queryArray = array(
            'system_flag'=>$this->getDataByArray($params,'system_flag'),
            'order_num'=>$this->getDataByArray($params,'order_num'),
            'out_order_num'=>$this->getDataByArray($params,'out_order_num'),
            'order_status'=>$this->getDataByArray($params,'order_status'),
            'apply_begin_time'=>$this->getDataByArray($params,'apply_begin_time'),
            'apply_end_time'=>$this->getDataByArray($params,'apply_end_time')
        );
        //过滤掉NULL值
        $queryArray = array_filter($queryArray, function ($v) {
            return $v !== null;
        });
        $cols = "out_order_num,order_num,order_pay_type,"
            ."pay_bank_account,collect_main_body,amount,order_status,pay_status,update_time,optor,opt_msg,real_pay_date,(select bank_name from m_bank_account b join m_pay_transfer f on f.pay_account_uuid=b.uuid where f.pay_order_uuid = m_pay_order.uuid) pay_bank_name,(select pay_bank_account from m_pay_transfer f where f.pay_order_uuid = m_pay_order.uuid) pay_bank_account,"
            ."(select bank_water from m_pay_transfer f where f.pay_order_uuid = m_pay_order.uuid) bank_water,(select full_name from m_main_body b where b.uuid = m_pay_order.pay_main_body_uuid) pay_main_body";
    	$ret = $obj->orderDetails($queryArray , $cols , $this->getDataByArray($params,'page') , $this->getDataByArray($params,'limit'));
    	    	
    	if(count($ret)==0)
    	{
    		throw new Exception("查询结果为空" , ErrMsg::RET_CODE_SERVICE_FAIL);	
    	}
    	if(isset($this->m_request['version'])&&$this->m_request['version']=='2.0'){
    		$sys_info = SystemInfo::getSystemInfoByFlag($this->m_request['system_flag']);
    		$u = new RSAUtil();
    		$ret = $u->publicEncrypt(json_encode($ret),$sys_info['public_key']);
    	}
    	$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
    }
}
