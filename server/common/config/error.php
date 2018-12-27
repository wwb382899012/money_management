<?php
/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/7/26
 * Time: 10:43
 */



/**
 * 文件配置错误
 */
define('SYS_ERROR',1000);//系统错误
define('PARAM_ERROR' ,1002);          //参数错误
define('ERROR_PARAM' ,1002);          //参数错误
define('ERR_ASSERT' ,1001);          //assert失败错误
define('ASSERT_ERROR',1001);
define('ERR_BAD_PARAM' ,1002);          //参数错误
define('ERR_UNKNOWN' ,1003);         //未知错误码
define('ERR_UNSET' ,1004);         //未初始化错误
define('ERR_TYPE' ,1005);         //错误类型
define('ERR_STRING' ,1006);         //非法字符串
define('ERR_SERVICE',1007);			//非法服务名

/**
 * 数据库操作错误
 */
define('ERR_DB_INITIAL',2001); // 分配数据库连接失败
define('ERR_DB_CONNECT' ,2002); // 连接数据库失败
define('ERR_DB_LOST' ,2003); // 和数据库断开连接
define('ERR_DB_BEGIN' ,2004); // 开始事务失败
define('ERR_DB_COMMIT' ,2005); // 提交事务失败
define('ERR_DB_ROLLBACK' ,2006); // 回滚事务失败
define('ERR_DB_NULL_RESULT' ,2007); // 获取到空数据库结果集
define('ERR_DB_AFFECT_ROW' ,2008); // 影响行数不符合
define('ERR_DB_UNKNOW' ,2009); // 意外错误
define('ERR_DB_ZEROCOLUMN' ,2010); //查询结果列为0
define('ERR_DB_NOTUNIQ' ,2011); //查询结果行不为1
define('ERR_DB_DEL_NOT_UNIQ' ,2012); //删除记录不惟一
define('ERR_DB_AFFECTED' ,2013); //影响行不惟一
define('ERR_DB_DUP_ENTRY' ,2014); //重复键
define('ERR_DB_DUP_TRANS' ,2015); //重复事务
define('ERR_DB_NO_TRANS' ,2016); //事务未开始
define('ERR_DB_TABLE' ,2017); //数据库表名不存在
define('ERR_DB_DATABASE' ,2018); //数据库表名不存在



/**
 * 业务级别错误
 */
define("INVALID_PARAM",3000);//参数错误
define('INVALID_USER',3001);//非法用户
define('INVALID_SKU',3002);//非法sku
define('INVALID_FEE',3003);//非法sku
define('INVALID_ORDER',3004);//非法订单
define('SEND_SMS_FAIL',4001);//发短信失败
define('VERIFY_SMS_FAIL',4002);//短信验证失败
define('VERIFY_SMS_ERROR',4003);//短信验证异常
define('SMS_SEND_LIMIT',4004);//短信验证异常
define('INVALID_PASSWD',4005);//密码错误
define('INVALID_PAY_TYPE',4006);//非法支付方式
define('INVALID_VERIFY_VALUE',4007);//非法确认值
define('INVALID_BUY_STATE',4008);//非法采购状态
define('INVALID_APPROVAL_STATE',4009);//非法审核值
define('INVALID_ADVICE_TYPE',4010);//非法审核值
define('INVALID_SIGN_TYPE',4011);//非法签名方式
define('VERIFY_ERROR',4012);//验证失败


//活动组件服务错误码
define('ACT_COMP_INVALID_PARAM',5001);//参数错误

define('ACT_COMP_CALL_COUPON_GET_ID_FAILED',5002);//获取红包id失败
define('ACT_COMP_CALL_COUPON_AWARD_FAILED',5003);//红包发奖失败

define('ACT_COMP_DRAW_LOTTERY_RAND_FAILED',5004);//随机抽奖失败
define('ACT_COMP_DRAW_LOTTERY_BOX_IS_EMPTY',5005);//抽奖箱空了

define('ACT_COMP_TASK_CANNOT_COMPLETE_DIRECTLY',5006);//该任务不可以直接进入已完成状态
define('ACT_COMP_TASK_ALREADY_COMMON_COMPLETE',5007);//该任务属于普通任务，已处于已完成状态
define('ACT_COMP_TASK_ALREADY_DAILY_COMPLETE',5008);//该任务属于日常任务,已处于已完成状态


//严选渠道服务错误码
define('YANXUAN_INVALID_PARAM',6001);//参数错误

define('YANXUAN_REQUEST_FAILED',6002);//请求严选错误