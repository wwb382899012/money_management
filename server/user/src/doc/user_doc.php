<?php
/**
* @api {POST} /com.jyblife.logic.bg.user.UserLogin 用户登录
* @apiGroup User
*
* @apiParam {string} sessionToken 会话token前缀
* @apiParam {string} username 登录账号
* @apiParam {string} password 登录密码
* @apiParamExample {json} post数据:
* {
*   "sessionToken":"e2fddd7da76988a24fc2a2b1887fdeise4",
*   "username": "test", 
*   "password": "123456" 
* }
* @apiSuccessExample {json} 输出示例:
* {"code":0, "data":{"sessionToken":"xxxxx","user_id":"128"}, "msg":"success"}
*/

/**
 * @api {POST} /com.jyblife.logic.bg.user.UserLogout 用户注销
 * @apiGroup User
 *
 * @apiParam {string} sessionToken 会话token前缀
 * @apiParamExample {json} post数据:
 * {
 *   "sessionToken":"e2fddd7da76988a24fc2a2b1887fdeise4",
 * }
 * @apiSuccessExample {json} 输出示例:
 * {"code":0, "msg":"success"}
 */

/**
* @api {POST} /com.jyblife.logic.bg.user.UserList 用户列表
* @apiGroup User
*
* @apiParam {string} sessionToken 会话token
* @apiParam {string} username 用户名陈模糊搜索(搜索条件,非必填)
* @apiParam {Array} main_body_uuid 交易主体uuid(搜索条件,非必填)
* @apiParam {Array} role_uuid 角色uuid(搜索条件,非必填)
* @apiParam {inter} page 当前页码
* @apiParam {inter} limit 每页显示数量，最大100
* @apiParamExample {json} post数据:
*  {"page":1, "limit":"100", "sessionToken":"xxxx",role_uuid:["xxx","xxx"]}
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":{
*    "limit": 10,
*    "page": 1,
*    "count": 100,    
*    "data":[
*    "user_id":"98763",
*    "username": "test", 
*    "name": "王书连",
*    "role_name": "资金专员",
*    "role_uuid": "xxxxx",
*    "status_": "启用",
*    "last_login_time": "2018-01-02 14:00:00" 
*   }]}
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.user.UserDetail 用户详情
* @apiGroup User
*
* @apiParam {string} sessionToken 会话token
* @apiParam {inter} user_id 用户id
* @apiParamExample {json} post数据:
*  {"user_id":1344, "sessionToken":xxx}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{
*       "user_id":"98763",
*       "username": "test", 
*       "name": "王书连",
*       "role_name": "资金专员",
*       "role_uuid": "xxxxx",
*       "status_": "启用",
*       "last_login_time": "2018-01-02 14:00:00",
*       "main_body": [{"uuid":"xxxx"}],   #主体公司
*       "role_ids": [{"uuid":"xxxx"}],     #角色ID  
*   }
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.user.UserUpdate 用户更新
* @apiGroup User
* @apiParam {String} sessionToken 会话token
* @apiParam {Inter} user_id 用户id
* @apiParam {String} roles_uuids 角色uuids,多个逗号隔开
* @apiParam {String} main_body_uuids 主体uuids,多个用逗号隔开
* @apiParam {String} email 邮箱地址
* @apiParamExample {json} post数据:
*  {"user_id":1344, "roles_uuids":"xxxx,xxx","main_body_uuids":"xxxx,xxxxx","sessionToken":"xxx","email":"123@qq.com"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{
*   }
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.user.PrivData 获取会话用户数据权限
* @apiGroup User
* @apiParam {string} sessionToken 会话token
* @apiParam {string} tablename 实体主表名称
* @apiParamExample {json} post数据:
*  {"sessionToken":"xxx", "tablename": "m_pay_order"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{"main_body_uuid":[xxxx,xxxxx]}
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.user.CheckUserDataPriv 判断用户数据权限
* @apiGroup User
* @apiParam {String} sessionToken 会话token
* @apiParam {Number} user_id 用户id,为空则获取全部有次权限的用户(可为空)
* @apiParam {String} tablename 实体主表名称
* @apiParam {String} priv_uuid 实体主键
* @apiParamExample {json} post数据:
*  {"sessionToken":"xxx", "tablename": "m_pay_order", "user_id":"xxxx", "priv_uuid":"xxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{"user_ids":[xxxx,xxxxx]}    
* }
*/