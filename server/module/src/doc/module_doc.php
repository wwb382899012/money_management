<?php
/**
* @api {POST} /com.jyblife.logic.bg.module.ModuleList 模块列表
* @apiGroup Module
* @apiParam {string} sessionToken 会话token
* @apiParam {string} name 模块名称(搜索条件,非必填)
* @apiParam {inter} is_menu 1菜单 2非菜单(搜索条件,非必填)
* @apiParam {inter} status 0启用 1停用(搜索条件,非必填)
* @apiParamExample {json} post数据:
*  {"sessionToken":"xxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":[{
*    "module_uuid":"xxxxx",
*    "module_pid_uuid": "xxxx",
*    "name": "系统管理", 
*    "srot": "10",   # 排序,
*    "status": 0,   # 0启用 1停用
*    "api_url": "com.jyblife.logic.bg.module",      #微服务前缀
*    "son_api": "列表|ModuleList,详情|ModuleDetail", #服务功能标识 
*    "is_menu": "1", # 1菜单 2:非菜单
*   }] 
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.module.ModuleDetail 模块详情
* @apiGroup Module
* @apiParam {string} sessionToken 会话token
* @apiParam {string} module_uuid 角色uuid
* @apiParamExample {json} post数据:
*  {"module_uuid":'xxxx', "sessionToken":"xxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{
*    "module_uuid":"xxxxx",
*    "module_pid_uuid": "xxxx",
*    "name": "系统管理", 
*    "srot": "10",   # 排序,
*    "status": 0,     #0启用 1停用
*    "api_url": "com.jyblife.logic.bg.module",      #微服务前缀
*    "son_api": "列表|ModuleList,详情|ModuleDetail", #服务功能标识 
*    "is_menu": "1", # 1菜单 2:非菜单
*   }
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.module.ModuleUpdate 模块更新
* @apiGroup Module
* @apiParam {string} sessionToken 会话token
* @apiParam {string} module_uuid 模块uuid
* @apiParam {string} module_pid_uuid 模块父uuid(非必填)
* @apiParam {string} name 模块名称
* @apiParam {inter} sort 模块排序状态
* @apiParam {inter} status 0启用 1停用
* @apiParam {string} api_url 页面地址标识
* @apiParam {string} son_api 页面功能标识
* @apiParam {inter} is_menu 1菜单 2非菜单
* @apiParamExample {json} post数据:
*  {"module_uuid":'xxxxx', "name":"系统管理","sort":10,
*   "status":1,"api_url":"com.jyblife.logic.bg.module", 
*   "son_api":"列表|ModuleList,详情|ModuleDetail",
*   "is_menu":1,"sessionToken":"xxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{
*   }
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.module.ModuleAdd 模块新增
* @apiGroup Module
* @apiParam {string} sessionToken 会话token
* @apiParam {string} module_pid_uuid 模块父uuid(非必填)
* @apiParam {string} name 模块名称
* @apiParam {inter} sort 模块排序状态
* @apiParam {inter} status 0启用 1停用
* @apiParam {string} api_url 页面地址标识
* @apiParam {string} son_api 页面功能标识
* @apiParam {inter} is_menu 1菜单 2非菜单
* @apiParamExample {json} post数据:
*  {"name":"系统管理","sort":10,
*   "status":1,"api_url":"com.jyblife.logic.bg.module", 
*   "son_api":"列表|ModuleList,详情|ModuleDetail",
*   "is_menu":1, "sessionToken":"xxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{
*     "uuid":"xxx"    
*   }
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.module.ModuleDel 模块删除
* @apiGroup Module
* @apiParam {string} sessionToken 会话token
* @apiParam {string} module_uuid 模块uuid
* @apiParamExample {json} post数据:
*  {"module_uuid":xxxxx, "sessionToken":"xxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0,
*   "data":{
*   }
* }
*/