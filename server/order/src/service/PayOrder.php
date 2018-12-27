<?php
/**
 * Created by PhpStorm.
 * User: jiaxiao.sun
 * Date: 2018/3/5
 * Desc:
 */
use money\model\InterfacePriv;
use money\base\MapUtil;
use money\model\BankAccount;
use money\model\MainBody;
use money\service\OrderBaseService;
use money\model\EodTradeDb;
use money\base\RSAUtil;
use money\model\SystemInfo;
use money\base\AreaUtil;
use money\model\BankBase;
use think\Validate;
use money\model\PayOrder as PayOrderModel;

class PayOrder extends OrderBaseService
{   
    protected $rule = [
        //'sessionToken' => 'require',
        'system_flag' => 'require',
        'out_order_num' => 'require',
        'order_pay_type' => 'require|integer',
        'pay_main_body' => 'require',
        //'pay_bank_name' => 'require',
        //'pay_bank_account' => 'require',
        'collect_main_body' => 'require',
        'collect_account_name' => 'require',
        'collect_bank_account' => 'require',
//         'collect_bank_desc' => 'require',
//         'collect_bank_name' => 'require',
//         'collect_city_name'=>'require',
        'order_create_people'=>'require',
        'amount' => 'require|integer',
    ];

    public function exec()
    {
        $o_params = $this->m_request;
        foreach($o_params as $key=>$val){
        	$o_params[$key] = trim($val);
        }
        /**
         *	1、付款、收款主体验证
         *	2、付款主体账户如果存在，验证是否和系统账户信息一致、是否在当前主体下，是否有权限对这个账户做操作
         *	       根据付款方式判断是否为个人报销，如果是个人报销则付款主体不为必填。
         *	       如果收款主体为必填，验证是否和系统账户信息一致、是否在当前主体下 (收款主体不需要判断调用系统是否有权限对这个账户操作)
         *	       付款指令、调拨中必须保存所有收款账号相关信息，付款时也用这个数据
         *	3、是否调用系统与权限对付款主体账户操作
         *	4、判断付款账户余额
         *	5、数据加锁
         *	6、保存指令数据，收款账户银行编码转换
         *	7、发起审批，如果审批发起失败，则系统报错回滚
         *	8、数据解锁
         */
        //step 1
        $pay_main_body = MainBody::getByName($o_params['pay_main_body']);
        if(empty($pay_main_body)){
        	throw new Exception('付款主体不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
        }        
        if($pay_main_body['is_internal']==2){
        	throw new Exception('付款主体必须为内部主体',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);	
        }
        
        
        
        //step 2 、3
        $oBankAmount = new BankAccount();
        if(isset($o_params['pay_bank_account'])){
        	
        	$pay_bank_account = $oBankAmount->getByAccount($o_params['pay_bank_account']);
        	if(empty($pay_bank_account)){
        		throw new Exception('付款账户不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
        	}
        	if(!isset($pay_bank_account['main_body_uuid'])||$pay_bank_account['main_body_uuid']!=$pay_main_body['uuid']){
        		throw new Exception('付款账户不在付款主体下',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
        	}
       
        	if(!isset($o_params['pay_account_name'])||$pay_bank_account['account_name']!=$o_params['pay_account_name']){
        		throw new Exception('付款账户户名错误',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
        	}
        	$system_flags = explode(',' , $pay_bank_account['interface_priv']);
        	$v = new InterfacePriv();
        	$priv = $v->loadDatas(['system_flag'=>$o_params['system_flag']]);
        	if(!in_array($priv[0]['uuid'],$system_flags)){
        		throw new Exception('该系统无权限对这个账户进行操作',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
        	}
        }
        
		//报销类账户和主体可以不在系统中存在
        if($o_params['order_pay_type']!=5){
	        $collect_main_body = MainBody::getByName($o_params['collect_main_body']);
	        if(empty($collect_main_body)){
	        	throw new Exception('收款主体不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
	        }
	        $collect_bank_account = $oBankAmount->getByAccount($o_params['collect_bank_account']);
	        if(empty($collect_bank_account)){
	        	throw new Exception('收款账户不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS); 
	        }
	        if(empty($collect_bank_account['main_body_uuid'])||$collect_bank_account['main_body_uuid']!=$collect_main_body['uuid']){
	        	throw new Exception('收款账户不在收款主体下',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
	        }
	    	if(!isset($o_params['collect_account_name'])||$collect_bank_account['account_name']!=$o_params['collect_account_name']){
	        	throw new Exception('收款账户户名错误',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
	        }
        }else{
        	$rule = [
		        'collect_bank' => 'require',  //中文，需要转化为字典值后存入
		        'collect_bank_desc' => 'require',
		        'collect_bank_address' => 'require',
		        'collect_city_name' => 'require',    //中文，需要转化为字典值，
		        'collect_province_name' => 'require'
		    ];
        	$validate = Validate::make($rule, [], []);
        	if (!$validate->check($this->m_request)) {
        		throw new \Exception('参数错误:'.$validate->getError(), \ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
        	}
        	$city = AreaUtil::loadCode($o_params['collect_province_name'], $o_params['collect_city_name']);
        	if($city==-1){
        		throw new Exception('收款银行省市信息错误',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
        	}
        	$bank = MapUtil::getKeyByVal('bank', $o_params['collect_bank']);
        	if($bank==-1){
        		throw new Exception('银行名称信息错误',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
        	}
        }
        
        //step 4
		
        $obj = new PayOrderModel();
        $params['system_flag'] = $o_params['system_flag'];
        $params['out_order_num'] = $o_params['out_order_num'];
        $obj->params = $params;
        if(!$obj->getLock()){
        	throw new Exception("当前下单指令编号重复",ErrMsg::RET_CODE_OUT_ORDER_NUM_DULICATE);
        }

        try{
            $obj->startTrans();
            
            //step 5
            $params['order_num'] = PayOrderModel::getOrderNum($pay_main_body['short_code']);
            
            $params['order_pay_type'] = $o_params['order_pay_type'];
            $params['pay_main_body_uuid'] = $pay_main_body['uuid'];
            if(isset($o_params['pay_bank_account'])){
	            $params['pay_bank_account'] = $o_params['pay_bank_account'];
	            $params['pay_account_uuid'] = $pay_bank_account['uuid'];
	            $params['pay_account_name'] = $o_params['pay_account_name'];
	            $params['pay_bank_name'] = $pay_bank_account['bank_name'];
            }
            
            $params['collect_main_body'] = $o_params['collect_main_body'];
            $params['collect_main_body_uuid'] = !empty($collect_main_body)?$collect_main_body['uuid']:'';
            $params['collect_account_name'] = $o_params['collect_account_name'];
            $params['collect_bank_account'] = $o_params['collect_bank_account'];
            $params['collect_bank_name'] = 	  !empty($collect_bank_account)?$collect_bank_account['bank_name']:$o_params['collect_bank_desc'];
            $params['collect_account_uuid'] = !empty($collect_bank_account)?$collect_bank_account['uuid']:'';
            
            $params['collect_bank'] = $o_params['order_pay_type']!=5?$collect_bank_account['bank_dict_key']:$bank;
            $params['collect_bank_desc'] = $o_params['order_pay_type']!=5?$collect_bank_account['bank_name']:$o_params['collect_bank_desc'];
            $params['collect_bank_address'] = $o_params['order_pay_type']!=5?$collect_bank_account['address']:$o_params['collect_bank_address'];
            $params['collect_city_name'] = $o_params['order_pay_type']!=5?$collect_bank_account['province'].$collect_bank_account['city_name']:$o_params['collect_province_name'].$o_params['collect_city_name'];
            $params['collect_city'] = $o_params['order_pay_type']!=5?$collect_bank_account['city']:$city;
            
            if($o_params['order_pay_type']!=5){
            	$params['collect_bank_link_code'] = $collect_bank_account['bank_link_code'];
            }else{
            	$b = new BankBase();
            	$base = $b->getBankBySubBranchName($o_params['collect_bank_desc'], $o_params['collect_bank']);
            	$params['collect_bank_link_code'] = isset($base['bank_link_code'])?$base['bank_link_code']:'';
            }
            
            $params['amount'] = $o_params['amount'];
            $params['currency'] = isset($o_params['currency'])?$o_params['currency']:'CNY';
            if(isset($o_params['financing_dict_key'])){
            	$params['financing_dict_key'] = $o_params['financing_dict_key'];
            	$params['financing_dict_value'] = MapUtil::getValByKey('financing', $o_params['financing_dict_key']);
            }
            
            $params['bs_background'] = $this->getDataByArray($o_params,'bs_background');
            $params['require_pay_datetime'] = $this->getDataByArray($o_params,'require_pay_datetime');
            $params['order_create_people'] = $this->getDataByArray($o_params,'order_create_people');
            $params['special_require'] = $this->getDataByArray($o_params,'special_require') ;
            $params['plus_require'] = $this->getDataByArray($o_params,'plus_require');
            $params['contact_annex'] = $this->getDataByArray($o_params,'contact_annex');            
            $params['order_status'] = PayOrderModel::ORDER_STATUS_WAITING;
            $params['pay_status'] = PayOrderModel::PAY_STATUS_UNPAID;

            $obj->params = $params;
            $uuid = $obj->saveOrUpdate();
            
            $r = [
	            'out_order_num'=>$this->m_request['out_order_num'],
	            'main_body_uuid'=>$pay_main_body['uuid'],
	            'order_create_time'=>date('Y-m-d H:i:s'),
	            'limit_date'=>$this->m_request['require_pay_datetime'],
	            'opt_uuid'=>$uuid,
	            'trade_type'=>1
            ];
            EodTradeDb::dataCreate($r);
            $obj->commit();
            $obj->unlock();
        }catch(Exception $e){
            $obj->rollback();
            $obj->unlock();
//             throw new Exception("付款指令下单失败|".$e->getMessage(),ErrMsg::RET_CODE_PAY_ORDER_ERROR);
			throw $e;
        }
        
        //审批流发起
        $f = array(
        		"flow_code"=>"pay_order",
        		"instance_id"=>$uuid,
        		"main_body_uuid"=>$pay_main_body['uuid']
        );
        $ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.Start" , $f);
        if(!is_array($ret)||!isset($ret['code'])||$ret['code']!=0){
        	$obj->params = [
	        	'uuid'=>$uuid,
	        	'order_status'=>PayOrderModel::ORDER_STATUS_REFUSE,
	        	'pay_status'=>PayOrderModel::PAY_STATUS_FAIL
        	];
        	$obj->saveOrUpdate();
//         	throw new Exception('审批流发起错误',ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        
        $ret = [
	        'out_order_num'=>$params['out_order_num'],
	        'order_num'=>$params['order_num'],
	        'amount'=>$params['amount']
        ];
        if(isset($this->m_request['version'])&&$this->m_request['version']=='2.0'){
        	$sys_info = SystemInfo::getSystemInfoByFlag($this->m_request['system_flag']);
        	$u = new RSAUtil();
        	$ret = $u->publicEncrypt(json_encode($ret),$sys_info['public_key']);
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);

    }
}
