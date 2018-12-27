<?php

/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/7/20
 * Time: 10:55
 */
abstract class BaseService
{
    public $m_logger = null;
    public $m_acc_logger = null;
    protected $m_service='';

    protected $m_request = null;
    protected $m_ret = null;

    protected $log_switch = true;
    protected $cmd_switch = false;

    public function __construct(){
        $this->m_logger     = $this->getLogger();
        $this->m_service    = get_class($this);
    }

    protected function getLogger(){
        if(empty($this->m_logger)){
            $this->m_logger = CommonLog::instance()->getDefaultLogger();
            $this->m_acc_logger = CommonLog::instance()->getAccLogger();
        }
        return $this->m_logger;
    }

    public function packRet($code, $data=array(), $msg="success")
    {
        /**
         * 为避免组装的返回数据未按格式封装，统一使用本函数进行处理
         */
        $this->m_ret = array(PARAM_CODE=>$code, PARAM_MSG=>$msg, PARAM_DATA=>$data);
        /*$ret = array(PARAM_CODE=>$code, PARAM_MSG=>"success", PARAM_DATA=>array());
        if(!empty($data))
        {
            $ret[PARAM_DATA]= $data;
        }
        if(!empty($msg))
        {
            $ret[PARAM_MSG]= $msg;
        }

        $this->m_ret = $ret;
        */
    }

    /**
     * @param $req
     * @return array或json
     */
    public function invoke($req)
    {
        $GLOBALS['seq__'] = '1234567890abcdefghijklmnopqrstuvwxyz';
        $this->m_request = $req;
        $this->m_ret = array(PARAM_CODE=>ERR_UNKNOWN,PARAM_MSG=>'unknown error');

        //是否对INFO信息进行记录
        /*if($this->getIntParam('not_log_info')){
            $this->log_switch = false;
        }*/

        $encodeData = StringUtil::json_encode_ex($this->m_request);

        if($this->log_switch)
        {
            $this->m_logger->info(sprintf("in|%s|%s",$this->m_service,$encodeData));
        }

        $begin_time = microtime(true);//取到微秒
        try{
            $this->CheckIn();
            $this->exec();
            $this->CheckOut();
        }
        catch(\Exception $e)
        {
            $this->packRet($e->getCode(),null, $e->getMessage());

            $this->m_logger->error(sprintf("%d|%s|%s",$this->m_ret[PARAM_CODE],$this->m_ret[PARAM_MSG],$encodeData),$e);
        }

        $end_time = microtime(true);//取到微秒
        $cost_time = (int)(($end_time-$begin_time)*1000000);
        $msg = StringUtil::json_encode_ex($this->m_ret);
        if(mb_strlen($msg,'UTF-8')>=1000){
            $msg = mb_substr($msg, 0,1000,'UTF-8');
        }

        if($this->log_switch){
            $this->m_logger->info(sprintf("out|%s|%s|%dus",$this->m_service,$msg,$cost_time));
        }

        return $this->m_ret;
    }

    protected abstract function CheckIn();
    protected abstract function exec();
    protected abstract function CheckOut();

    protected function getIntParam($key,$min=0,$max=PHP_INT_MAX)
    {
        return StringUtil::getIntParam($this->m_request,$key,$min,$max);
    }
    protected function isParamExist($key){
        return array_key_exists($key,$this->m_request);
    }
}