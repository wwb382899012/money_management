<?php
/**
*	调用接口系统模块信息
*	@author sun
*	@since 2018-03-05
*/
namespace money\model;

class SystemInfo extends BaseModel
{
	protected $table = 'm_interface_priv';

	public static function getSystemInfoByFlag($flag)
	{
	    return (new static())->getOne(['system_flag' => $flag]);
	}
}
