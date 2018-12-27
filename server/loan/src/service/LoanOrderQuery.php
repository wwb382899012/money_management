<?php
/**
*	订单查询(接口)
*	@author sun
*	@since 2018-03-10
*/

use money\model\LoanOrder;

class LoanOrderQuery extends OrderBaseService
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
    	$obj = new LoanOrder();
        $params = $this->m_request;
        $queryArray = array(
            'system_flag'=>$this->getDataByArray($params,'system_flag'),
            'order_num'=>$this->getDataByArray($params,'order_num'),
            'out_order_num'=>$this->getDataByArray($params,'out_order_num'),
            'order_status'=>$this->getDataByArray($params,'order_status'),
            'apply_begin_time'=>$this->getDataByArray($params,'apply_begin_time'),
            'apply_end_time'=>$this->getDataByArray($params,'apply_end_time')
        );
        $cols = "out_order_num,order_num,loan_type,"
            ."order_status";
    	$ret = $obj->orderDetails($queryArray , $cols , $this->getDataByArray($params,'page') , $this->getDataByArray($params,'limit'));

    	if(count($ret)==0)
    	{
    		throw new Exception("查询结果为空" , ErrMsg::RET_CODE_SERVICE_FAIL);
    	}

    	$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
    }
}
