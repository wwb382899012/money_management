<?php
/**
* 错误消息处理类，支持模板配置 
*   
* @author free.gu
* @since 2016-03-31
* 
*/
class ErrMsg
{
    const RET_CODE_SYS_CODE_EMPTY = '';

    // 指令相关
    const RET_CODE_SUCCESS = '0'; 
    const RET_CODE_NOTLOGIN = '5000020001';
    const RET_CODE_DATA_INVAILD = '5000020002';
    const RET_CODE_CMD_INVAILD =  '5000020003';
    const RET_CODE_INVALID_SMSCODE = '5000020004';
    const RET_CODE_METHOD_INVAILD = '5000020005';
    const RET_CODE_LOGIC_ERR = '5000020006';
    const RET_CODE_OVER_REQ_LMT = '5000020007';
    const RET_CODE_CUST_NOTEXISTS = '5000020008';
    const RET_CODE_EXCEPTION = '5000020009';
    const RET_CODE_PWD_ERR = '5000020010';
    const RET_CODE_LOGIN_FAILED = '5000020011';                                         //登录失败
    const RET_CODE_REQ_TIMEOUT = '5000020012';                   //请求超时

    const RET_CODE_SMSCODE_WRONG = '4202020101';
    const RET_CODE_IMGCODE_WRONG = '4202020201';
    const RET_CODE_VOICECODE_WRONG = '4202020401';

    //通用验证错误
    const RET_CODE_GENVERIFY_TYPE_ERROR = '4201020601';                         //验证类型错误
    const RET_CODE_GENVERIFY_KEY_ERROR = '4201020602';                          //验证主键错误
    const RET_CODE_GENVERIFY_BUSI_ERROR = '4201020603';                         //验证业务码错误
    const RET_CODE_GENVERIFY_TOKEN_ERROR = '4201020701';                        //验证token错误
    const RET_CODE_VERSION_VALIDATE_ERROR = '4201020700';                       //接口调用版本号验证错误
    const RET_CODE_SECRET_VALIDATE_ERROR = '4201020702';                       //接口rsa串解析错误
    
    // tarService 不存在
    const RET_CODE_INVALID_TARGET_SERVICE_ERROR = '430000001';      // targetService 错误           
    const RET_CODE_INVALID_PRIV_ERROR = '430000002';      // 无权限

    // 服务调用失败
    const RET_CODE_SERVICE_FAIL = '430000003';
    const RET_CODE_OUT_ORDER_NUM_DULICATE = '6000020002';
    const RET_CODE_LOAD_ORDER_NUM_ERROR = '6000020003';
    const RET_CODE_PAY_ORDER_ERROR = '6000020004';
    const RET_CODE_NUMBER_VALIDATE_ERROR = '6000020005';
    const RET_CODE_MISS_PARAMS = '6000020006';
    const RET_CODE_SECERT_EMPTY = '6000020007';
    const RET_CODE_SECERT_VALIDATE_ERROR = '6000020008';
    const RET_CODE_LOAD_PAY_TRANSFER_NUM_ERROR = '6000020009';
    const RET_CODE_PAY_ORDER_APPROVE_ERROR = '6000020010';
    const RET_CODE_LOAN_ORDER_ERROR = '6000020011';
    const RET_CODE_REPAY_ORDER_OPTING_ERROR = '6000020014';

    const RET_CODE_FLOW_AUTH_VALIDATE_ERROR = '6000020011';
    const RET_CODE_FLOW_INSTANCE_ID_DULICATE = '6000020012';
    const RET_CODE_FLOW_INSTANCE_APPROVED = '6000020013';
    //数据库相关
    const RET_CODE_DATA_NOT_EXISTS = '6000090001';
    const RET_CODE_DATA_MAIN_BODY_AUTH_VALI_ERROR = '6000090002';
    const RET_CODE_DATA_VALIDATE_ERROR = '6000090003';

    protected static $error_Msg = array(
        //指令接口
        "6000020001" => "调用系统业务编码不存在",
        "6000020002" => "下单指令编号重复",
        "6000020003" => "获取下单内部编号失败",
        "6000020004" => "付款指令下单失败",
        "6000020005" => "参数格式验证失败",
        "6000020006" => "参数不能为空",
        "6000020007" => "签名不能为空",
        "6000020008" => "签名验证不通过",
        "6000020009" => "获取付款调拨编号失败",
        //数据库相关
        "6000090001" => "数据不存在",
    	"6000020014"=>"还款数据处理中"
    );


    const REGX = '/#([^#]*)#/';
	private $msg ;
	private $params;

	public function __construct($msg, $params)
	{
		$this->msg = $msg; 
		$this->params = $params;
	}

	public static function get($code, $params=array())
	{
		if( $code != self::RET_CODE_SUCCESS )
        {
        	if(empty($params[PARAM_MSG]) && empty(self::$error_Msg[$code]))
                self::$error_Msg[$code] = "系统繁忙，请稍后重试";
	        else
                self::$error_Msg[$code] = empty($params[PARAM_MSG])?self::$error_Msg[$code]:$params[PARAM_MSG];
        }
		return self::$error_Msg[$code];
		
	}
}