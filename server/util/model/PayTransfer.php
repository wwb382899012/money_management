<?php
/**
*	付款调拨
*	@author sun
*	@since 2018-03-11
*/
namespace money\model;

class PayTransfer extends BaseModel
{
	protected $table = 'm_pay_transfer';
	
	const REDIS_KEY_PAY_TRANSFER_NUM_KEY = 'pay_transfer_num_key';

    const TRANSFER_STATUS_WAITING = 0;
    const TRANSFER_STATUS_OPTED = 1;
    const TRANSFER_STATUS_COMFIRMED = 2;
    const TRANSFER_STATUS_REJECT = 3;
    const TRANSFER_STATUS_REFUSE = 4;
    const TRANSFER_STATUS_WAIT_TICKET_BACK = 19; //待上传回单
    const TRANSFER_STATUS_ARCHIVE = 20;

    const PAY_STATUS_UNPAID = 0;
    const PAY_STATUS_PAYING = 1;
    const PAY_STATUS_PAID = 2;
    const PAY_STATUS_FAIL = 3;
    const PAY_STATUS_UNCONFIRM = 10;

    public $map = array(
        //是否需要融资
        'is_financing'=>array(
            '0'=>'不需要融资',
            '1'=>'需要融资'
        ),
        //指令状态
        'order_status'=>array(
            '0'=>'未处理',
            '1'=>'已处理',
            '2'=>'已驳回',
            '3'=>'归档完结'
        ),
        //付款状态
        'pay_status'=>array(
            '0'=>'未付款',
            '1'=>'付款中',
            '2'=>'已付款'
        ),
        //付款类型
        'order_pay_type'=>array(
            '0'=>'预付款',
            '1'=>'货款',
            '2'=>'物流费用'
        ),
        //实付类型
        'real_pay_type'=>array(
            '1'=>'网银',
            '2'=>'银企直联',
        ),
    );
    
		/**
	 *	获取调拨编号
	*/
	public static function getTransferNum($main_body_code) {
		// return date('Ymd').$redisObj->incr(self::REDIS_KEY_PAY_ORDER_NUM_KEY);
		$util = new \RedisUtil ( ENV_REDIS_BASE_PATH );
		$redis = $util->get_redis ();
		
		$nday = date ( "Ymd" );
		$key = '01-TR-' . $main_body_code . '-' . $nday;
		$seqid = 0;
		if (! ($seqid = $redis->incr ( $key ))) {
			throw new \Exception ( '获取编号失败', \ErrMsg::RET_CODE_LOAD_ORDER_NUM_ERROR );
		}
		
		if ($seqid == 1) {
			$redis->expire ( $key, 86400 );
		}
		
		$t_seq = sprintf ( "%05d", $seqid );
		
		return '01-TR-' . $main_body_code . '-' . $nday . '-' . $t_seq;
	}

    //获取调拨详情列表
    public function details($params , $cols , $page , $pageSize)
    {
        $cols = $cols?$cols:'*';
        $keys = ['transfer_status','order_create_people'
        		,'pay_main_body_uuid','collect_main_body_uuid','is_financing','pay_status'];

        $where = [];
        foreach ($params as $key => $val) {
            if (in_array($key, $keys)) {
                $where[] = [$key, '=', $val];
            }
        }
        if(!empty($params['pay_main_body_uuids'])&&is_array($params['pay_main_body_uuids'])&&count($params['pay_main_body_uuids'])>0){
        	$where[] = ['pay_main_body_uuid','in',$params['pay_main_body_uuids']];
        }
        if(!empty($params['apply_begin_time'])){
            $where[] = ['create_time', '>=', $params['apply_begin_time']];
        }
        if(!empty($params['apply_end_time'])){
            $where[] = ['create_time', '<=', $params['apply_end_time']];
        }
        if(!empty($params['approve_begin_time'])){
            $where[] = ['update_time', '>=', $params['approve_begin_time']];
        }
        if(!empty($params['approve_end_time'])){
            $where[] = ['update_time', '<=', $params['approve_end_time']];
        }
        return $this->getDatasByPage($where, $cols, $page, $pageSize);
    }
    
    public static function refuse($uuid){
	    $obj = new static();
    	$tranData = $obj::getDataById($uuid);

        $obj->table('m_pay_order')->where(['uuid' => $tranData['pay_order_uuid']])->update(['order_status' => PayOrder::ORDER_STATUS_REJECT]);
        $obj->where(['uuid' => $uuid])->update(['transfer_status' => PayTransfer::TRANSFER_STATUS_REJECT]);
    }
}
