<?php
require_once 'fluent/Fluent/Autoloader.php';

use Fluent\Logger\FluentLogger;
Fluent\Autoloader::register();
class MonitorUtil{
    private static $logger=null;
    private static $log_tag="";
    private static $log_tag_interface="inf";
    private static $log_tag_server_log="log";
    private static $log_tag_component="com";
    private static $log_tag_biz="biz";
    private static $pid=0;

    static private function td_agent_init()
    {
        self::$logger= new FluentLogger("unix:///var/run/td-agent/td-agent.sock");
    }
    public function __construct()
    {
        self::$pid=posix_getpid();
    }
    static private function set_tag($module_id=null)
    {
        if($module_id===null) {
            throw new Exception("缺少参数[module_id]");
        }
        else{
            self::$log_tag =$module_id;
        }
        self::$log_tag_interface=self::$log_tag.".inf";
        self::$log_tag_server_log=self::$log_tag.".log";
        self::$log_tag_component=self::$log_tag.".com";
        self::$log_tag_biz=self::$log_tag.".biz";
    }
    static public function log_init($module_id=null)
    {
        self::set_tag($module_id);
        self::$pid=posix_getpid();
        self::td_agent_init();
    }
    //发送日志到td-agent
    static public function post($tag,$message){
        if(!self::$logger)
            self::td_agent_init();
        $ret=self::$logger->post("monitor.".$tag,$message);
        if(!$ret)
        {
            self::td_agent_init();
        }
        return $ret;
    }
    static public function get_sys_info($base_frame=0)
    {
        $array =debug_backtrace(3+$base_frame);
        $arg=array("pid" => self::$pid);
        if(count($array) >= $base_frame+1)
        {
            $arg["line"] = $array[$base_frame]["line"];
            $arg["file"] = basename($array[$base_frame]["file"]);
        }
        if(count($array) >= $base_frame+2) {
            $class_name="";
            if(isset($array[$base_frame+1]["class"]))
                $class_name=$array[$base_frame+1]["class"];
            $arg["func"] =  $class_name. "::" . $array[$base_frame+1]["function"];
        }
        return $arg;
    }
    static public function log_info()
    {
        $arg_arr=func_get_args();
        $info_arr=array();
        $last_key=null;
        $begin_idx=0;
        foreach($arg_arr as $i => $v)
        {
            if($i < $begin_idx)
                continue;
            if($i==$begin_idx && is_array($v))
            {
                $info_arr=$v;
                break;
            }
            if($last_key===null)
            {
                $last_key=$v;
                $info_arr[$last_key] = "";
            }
            else
            {
                $info_arr[$last_key] = $v;
                $last_key=null;
            }
        }
        self::set_tag();
        $sys_info=self::get_sys_info(1);
        $sys_info["log_level"] = "info";
        $res = array_merge($info_arr,$sys_info);
        self::post(self::$log_tag_server_log,$res);
    }
    static public function log_debug()
    {
        $arg_arr=func_get_args();
        $info_arr=array();
        $last_key=null;
        $begin_idx=0;
        foreach($arg_arr as $i => $v)
        {
            if($i < $begin_idx)
                continue;
            if($i==$begin_idx && is_array($v))
            {
                $info_arr=$v;
                break;
            }
            if($last_key===null)
            {
                $last_key=$v;
                $info_arr[$last_key] = "";
            }
            else
            {
                $info_arr[$last_key] = $v;
                $last_key=null;
            }
        }
        self::set_tag();
        $sys_info=self::get_sys_info(1);
        $sys_info["log_level"] = "debug";
        $res = array_merge($info_arr,$sys_info);
        self::post(self::$log_tag_server_log,$res);
    }
    static public function log_error($errcode,$info)
    {
        $arg_arr=func_get_args();
        $info_arr=array();
        $last_key=null;
        $begin_idx=2;
        foreach($arg_arr as $i => $v)
        {
            if($i < $begin_idx)
                continue;
            if($i==$begin_idx && is_array($v))
            {
                $info_arr=$v;
                break;
            }
            if($last_key===null)
            {
                $last_key=$v;
                $info_arr[$last_key] = "";
            }
            else
            {
                $info_arr[$last_key] = $v;
                $last_key=null;
            }
        }
        self::set_tag();
        $sys_info=self::get_sys_info(1);
        $sys_info["log_level"] = "error";
        $sys_info["errcode"] = $errcode;
        $sys_info["info"] = $info;
        $res = array_merge($info_arr,$sys_info);
        self::post(self::$log_tag_server_log,$res);
    }
    static public function log_fatal($info)
    {
        $arg_arr=func_get_args();
        $info_arr=array();
        $last_key=null;
        $begin_idx=1;
        foreach($arg_arr as $i => $v)
        {
            if($i < $begin_idx)
                continue;
            if($i==$begin_idx && is_array($v))
            {
                $info_arr=$v;
                break;
            }
            if($last_key===null)
            {
                $last_key=$v;
                $info_arr[$last_key] = "";
            }
            else
            {
                $info_arr[$last_key] = $v;
                $last_key=null;
            }
        }
        self::set_tag();
        $sys_info=self::get_sys_info(1);
        $sys_info["log_level"] = "fatal";
        $sys_info["info"] = $info;
        $res = array_merge($info_arr,$sys_info);
        self::post(self::$log_tag_server_log,$res);
    }

    /**
     * 函数说明：被调方上报服务质量
     * 参数列表：
     *      cmd          当前处理的命令字
     *      cmt_time_s   从收到请求到发出响应包的耗时
     *      errcode      错误码或返回码
     *      info         错误信息
     */
    static public function log_profile($cmd,$cmd_time_s,$errcode,$info)
    {
        $arg_arr=func_get_args();
        $info_arr=array();
        $last_key=null;
        $begin_idx=4;
        foreach($arg_arr as $i => $v)
        {
            if($i < $begin_idx)
                continue;
            if($i==$begin_idx && is_array($v))
            {
                $info_arr=$v;
                break;
            }
            if($last_key===null)
            {
                $last_key=$v;
                $info_arr[$last_key] = "";
            }
            else
            {
                $info_arr[$last_key] = $v;
                $last_key=null;
            }
        }
        self::set_tag();
        $arr=array("log_level"=>"profile","cmd" => $cmd,"cmd_time_s" => $cmd_time_s,"errcode" => $errcode,"info" => $info,"pid" => self::$pid);
        $res = array_merge($info_arr,$arr);
        self::post(self::$log_tag_server_log,$res);
    }

    /**
     * 函数说明：调用方上报接口质量
     * 参数列表：
     *      call_cmd     调用的命令字
     *      callee_addr  调用方地址
     *      call_time    发起调用的时间
     *      time_span    接口耗时,单位：毫秒
     *      ret_code     返回码
     */
    static public function log_interface($call_cmd,$callee_addr,$call_time,$time_span,$ret_code)
    {
        $arg_arr=func_get_args();
        $info_arr=array();
        $last_key=null;
        $begin_idx=5;
        foreach($arg_arr as $i => $v)
        {
            if($i < $begin_idx)
                continue;
            if($i==$begin_idx && is_array($v))
            {
                $info_arr=$v;
                break;
            }
            if($last_key===null)
            {
                $last_key=$v;
                $info_arr[$last_key] = "";
            }
            else
            {
                $info_arr[$last_key] = $v;
                $last_key=null;
            }
        }
        self::set_tag();
        $arr=array("call_cmd" => $call_cmd,"callee_addr" => $callee_addr,"call_time" => $call_time, "time_span" => $time_span,"ret_code" => $ret_code,"pid" => self::$pid);
        $res = array_merge($info_arr,$arr);
        self::post(self::$log_tag_interface,$res);
    }

    /**
     * 函数说明:上报组件状态
     *      srv_cat      服务种类：mysql\redis\kafka\rabbitmq
     *      srv_type     服务类型。0：存储类，1：队列类
     *      disc_ratio   磁盘使用率。乘以100的整数。
     *      mem_ratio    内存使用率。乘以100的整数。
     *      cpu_ratio    cpu使用率。乘以100的整数。
     *      pid          服务的进程ID
     */
    static public function log_com($srv_cat,$srv_type,$disc_ratio,$mem_ratio,$cpu_ratio,$pid)
    {
        $arg_arr=func_get_args();
        $info_arr=array();
        $last_key=null;
        $begin_idx=6;
        foreach($arg_arr as $i => $v)
        {
            if($i < $begin_idx)
                continue;
            if($i==$begin_idx && is_array($v))
            {
                $info_arr=$v;
                break;
            }
            if($last_key===null)
            {
                $last_key=$v;
                $info_arr[$last_key] = "";
            }
            else
            {
                $info_arr[$last_key] = $v;
                $last_key=null;
            }
        }
        self::set_tag();
        $arr=array("srv_cat" => $srv_cat,"srv_type" => $srv_type,"disc_ratio" => $disc_ratio,"men_ratio" => $mem_ratio,"cpu_ratio" => $cpu_ratio,"pid" => $pid);
        $res = array_merge($info_arr,$arr);
        self::post(self::$log_tag_component,$res);
    }

    /**
     * 函数说明：上报业务数据
     * 参数列表：
     *      type        0:产品，1：活动
     *      cust_id     用户ID
     *      op          操作：购买/支付/退款/赠送/使用
     *      amt         金额
     */
    static public function log_biz($type,$cust_id,$op,$amt,$ord_id=null,$prd_id=null,$act_id=null)
    {
        $arg_arr=func_get_args();
        $info_arr=array();
        $last_key=null;
        $begin_idx=7;
        foreach($arg_arr as $i => $v)
        {
            if($i < $begin_idx)
                continue;
            if($i==$begin_idx && is_array($v))
            {
                $info_arr=$v;
                break;
            }
            if($last_key===null)
            {
                $last_key=$v;
                $info_arr[$last_key] = "";
            }
            else
            {
                $info_arr[$last_key] = $v;
                $last_key=null;
            }
        }
        self::set_tag();
        $arr=array("type" => $type,"cust_id" => $cust_id,"amt" => $amt,"op" => $op);
        if($ord_id !== null)
        {
            $arr["ord_id"] = $ord_id;
        }
        if($prd_id !== null )
        {
            $arr["prd_id"] = $prd_id;
        }
        if($act_id !== null)
        {
            $arr["act_id"] = $act_id;
        }
        $res = array_merge($info_arr,$arr);
        self::post(self::$log_tag_biz,$res);
    }

    /**
     * 函数说明: 自定义上报。（无系统字段）
     * 参数列表：
     *      postfix     tag的后缀
     */
    static public function log_custom($module_id,$postfix)
    {
        $arg_arr=func_get_args();
        $info_arr=array();
        $last_key=null;
        $begin_idx=2;
        foreach($arg_arr as $i => $v)
        {
            if($i < $begin_idx)
                continue;
            if($i==$begin_idx && is_array($v))
            {
                $info_arr=$v;
                break;
            }
            if($last_key===null)
            {
                $last_key=$v;
                $info_arr[$last_key] = "";
            }
            else
            {
                $info_arr[$last_key] = $v;
                $last_key=null;
            }
        }
        self::set_tag($module_id);
        $tag=$postfix;
        if(self::$log_tag != "")
            $tag=self::$log_tag.".".$tag;

        self::post($tag,$info_arr);
    }

    /**
     * 函数说明: 自定义上报。（携带系统字段）
     * 参数列表：
     *      postfix     tag的后缀
     */
    static public function log_custom_ex($postfix)
    {
        $arg_arr=func_get_args();
        $info_arr=array();
        $last_key=null;
        $begin_idx=1;
        foreach($arg_arr as $i => $v)
        {
            if($i < $begin_idx)
                continue;
            if($i==$begin_idx && is_array($v))
            {
                $info_arr=$v;
                break;
            }
            if($last_key===null)
            {
                $last_key=$v;
                $info_arr[$last_key] = "";
            }
            else
            {
                $info_arr[$last_key] = $v;
                $last_key=null;
            }
        }
        self::set_tag();
        $tag=$postfix;
        if(self::$log_tag != "")
            $tag=self::$log_tag.".".$tag;

        $sys_info=self::get_sys_info(1);
        $info_arr = array_merge($info_arr,$sys_info);
        self::post($tag,$info_arr);
    }
};
?>
