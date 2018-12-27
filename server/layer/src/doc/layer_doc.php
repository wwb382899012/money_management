<?php
/**
* @api {POST} /com.jyblife.logic.bg.layer.HttpAccessLayer 服务接入层
* @apiGroup layer
*
* @apiHeader {string="JMF"} Frame-type
* @apiHeader {string} Csrf-token 可为空
* @apiHeader {string} Cookie session-token登陆会话的cookie
* @apiParam {string="com.jyblife.logic.bg.layer.HttpAccessLayer"} service 服务名称
* @apiParam {string="dev"} env 环境参数
* @apiParam {string=""} set 可为空
* @apiParam {string="*"} group
* @apiParam {string="1.0.0"} version
* @apiParam {string="access"} method
* @apiParam {string="{}"} params 输入的参数
* @apiParam {string="com.jyblife.logic.bg.user.login"} targetService 目标服务
* @apiParamExample {json} post数据:
* {
*   "service":"com.jyblife.logic.bg.layer.HttpAccessLayer",
*   "env":"dev", "set":"", "version":"1.0.0",
*   "method": "access", "parmas": "{}", 
*   "targetService": "com.jyblife.logic.bg.user.login" 
* }
* @apiSuccessExample {json} 输出示例:
* {"code":0, "data":[], "msg":"success", 'csrfToken':''}
*/

/**
* @api {POST} /csrfToken 获取cstftoken值
* @apiGroup layer
*
* @apiHeader {string="JMF"} Frame-type
* @apiHeader {string} Csrf-token 可为空
* @apiHeader {string} Cookie session-token登陆会话的cookie
* @apiParam {string="com.jyblife.logic.bg.layer.HttpAccessLayer"} service 服务名称
* @apiParam {string="dev"} env 环境参数
* @apiParam {string=""} set 可为空
* @apiParam {string="*"} group
* @apiParam {string="1.0.0"} version
* @apiParam {string="csrfToken"} method
* @apiParam {string="{}"} params 输入的参数
* @apiParamExample {json} post数据:
* {
*   "service":"com.jyblife.logic.bg.layer.HttpAccessLayer",
*   "env":"dev", "set":"", "version":"1.0.0",
*   "method": "access", "parmas": "{}"
* }
* @apiSuccessExample {json} 输出示例:
* {"code":0, "data":{"token":"e2fddd7da76988a24fc2a2b1887fffd6"}, "msg":"success"}
*/

/**
* @api {POST} /com.jyblife.logic.bg.layer.SessionGet 获取会话信息
* @apiGroup layer
* @apiParam {string} sessionToken 会话token
* @apiParamExample {json} post数据:
*  {"sessionToken":"xxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{
*     "email" : "123@qq.com",   #用户对应的邮箱
*     "last_login_datetime": "2018-04-10 09:56:05",   
*     "main_body": [{"uuid":"xxx"}]   # 用户的主体权限
*     "name" : "曾海鹏",
*     "role": [{"uuid":"xxxx"}],   #用户角色
*     "user_id" : 128, #用户ID
*     "username": "money_test1",   #用户名    
*   }
* }
*/
