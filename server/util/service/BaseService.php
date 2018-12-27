<?php

/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/7/20
 * Time: 10:55
 * @link https://github.com/top-think/think-validate
 */

namespace money\service;
use money\model\Model;
use think\Validate;

class BaseService extends \BaseService
{
    /**
     * 当前验证规则
     * @var array
     */
    protected $rule = [];

    /**
     * 验证提示信息
     * @var array
     */
    protected $message = [];

    /**
     * 验证字段描述
     * @var array
     */
    protected $field = [];

    public function __construct(){
        parent::__construct();
    }

    public function invoke($req)
    {
        $ret = parent::invoke($req);
        //打印SQL日志
        if (defined('MYSQL_LOG') && MYSQL_LOG) {
            \CommonLog::instance()->getDbLogger()->info(print_r((new Model())->getSqlLog(), true));
        }
        return $ret;
    }

    protected function CheckIn() {
        $validate = Validate::make($this->rule, $this->message, $this->field);
        if (!$validate->check($this->m_request)) {
            throw new \Exception('参数错误:'.$validate->getError(), \ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
        }
    }

    protected function exec() {

    }

    protected function CheckOut() {

    }

    public function getDataByArray($array , $key){
        return isset($array[$key])?$array[$key]:null;
    }
}
