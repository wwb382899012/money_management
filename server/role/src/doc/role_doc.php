<?php
/**
* @api {POST} /com.jyblife.logic.bg.role.RoleList 角色列表
* @apiGroup Role
* @apiParam {string} sessionToken 会话token
* @apiParam {string} name 用户名陈模糊搜索(搜索条件,非必填)
* @apiParam {inter} page 当前页码
* @apiParam {inter} limit 每页显示数量，最大100
* @apiParamExample {json} post数据:
*  {"page":1, "limit":"100", "sessionToken":"xxxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":{
*    "page":1,"limit":10,"count":20,"data":[{
*    "uuid":"xxxxx",
*    "name": "资金专员", 
*    "status": "1",  # 1启用 2禁用
*    "last_update_time": "2018-01-02 14:00:00",
*    "info": "角色备注信息" 
*   }]} 
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.role.RoleDetail 角色详情
* @apiGroup Role
* @apiParam {string} sessionToken 会话token
* @apiParam {string} role_uuid 角色uuid
* @apiParamExample {json} post数据:
*  {"role_uuid":'xxxx', "sessionToken":"xxxxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{
*       "role_uuid":"xxxxx",
*       "name": "资金专员", 
*       "name": "角色信息备注",
*       "module_uuids": "[{"module_uuid":"xxx","son":"edit,del"}]", #选中的模型数据
*       "status": "1",    # 1启用 2禁用
*   }
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.role.RoleUpdate 角色更新
* @apiGroup Role
* @apiParam {string} sessionToken 会话token
* @apiParam {string} role_uuid 角色uuid
* @apiParam {string} name 角色名称
* @apiParam {string} info 角色描述（非必填）
* @apiParam {inter} status 角色状态1启用，2禁用
* @apiParamExample {json} post数据:
*  {"role_uuid":xxxxx, "name":"资金专员","info":"","status":1,"sessionToken":"xxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{
*      "role_uuid":"xxxx"    
*   }
* }
*/

/**
 * @api {POST} /com.jyblife.logic.bg.role.RoleAuthUpdate 角色权限更新
 * @apiGroup Role
 * @apiParam {string} sessionToken 会话token
 * @apiParam {string} role_uuid 角色uuid
 * @apiParam {json="[]"} module_uuids [{"xxxx":"edit,del"}]选中的模型数据
 * @apiParamExample {json} post数据:
 *  {"role_uuid":xxxxx, "module_uuids":"[{"xxxx":"edit,del"}]","sessionToken":"xxx"}
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "msg": "success",
 *   "code": 0,
 *   "data":{
 *      "role_uuid":"xxxx"
 *   }
 * }
 */

/**
* @api {POST} /com.jyblife.logic.bg.role.RoleAdd 角色新增
* @apiGroup Role
* @apiParam {string} sessionToken 会话token
* @apiParam {string} name 角色名称
* @apiParam {string} info 角色描述（非必填）
* @apiParam {inter} status 角色状态1启用，2禁用
* @apiParam {json="[]"} module_uuids [{"xxxx":"edit,del"}]选中的模型数据
* @apiParamExample {json} post数据:
*  {"name":"资金专员","info":"","status":1,"module_uuids":[{"xxxx":"edit,del"}],"sessionToken":"xxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{
*     "role_uuid":"xxxx"
*   }
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.role.RoleDel 角色删除
* @apiGroup Role
* @apiParam {string} sessionToken 会话token
* @apiParam {string} role_uuid 角色uuid
* @apiParamExample {json} post数据:
*  {"role_uuid":"xxxx", "sessionToken":"xxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{
*   }
* }
*/