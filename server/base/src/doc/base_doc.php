<?php
/**
* @api {POST} /com.jyblife.logic.bg.base.MainBodyList 主体列表
* @apiGroup Base
* @apiParam {string} name 主体名称（支持同时对简称、全称模糊查询，非必填）
* @apiParam {string} status 注销启用标识。1为启用，2为注销（非必填）
* @apiParam {string} uuid 主体唯一标示（非必填）
* @apiParam {int} page 分页字段，默认为1 （非必填）
* @apiParam {int} limit 每页大小，默认为50，如果传入值小于0，则不分页。（非必填）
* @apiParam {string} sessionToken 
* @apiParamExample {json} post数据:
*	{
*		"name":"test",
*		"status":1,
*		"sessionToken":"aaabbb"
*	}
* @apiParam (输出字段){string} short_name 简称
* @apiParam (输出字段){string} full_name 全称
* @apiParam (输出字段){string} short_code 简码
* @apiParam (输出字段){string} is_internal 是否公司内部主体 1是 2否
* @apiParam (输出字段){string} status 注销启用标识。1为启用，2为注销
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":{
*    "page": 1,
*    "limit": 50,
*    "count": 1,
*    "data":[
*     {"uuid":"finan20180302000001",
*     "short_name": "test",
*     "full_name": "test",
*     "short_code": "aabb",
*     "is_internal" : "1",
*     "status": 1
*    }]
*   }
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.base.MainBodyEffectList 获取用户关联的主体列表
* @apiGroup Base
* @apiParam {string} name 主体名称（支持同时对简称、全称模糊查询，非必填）
* @apiParam {string} status 注销启用标识。1为启用，2为注销（非必填）
* @apiParam {string} uuid 主体唯一标示（非必填）
* @apiParam {int} page 分页字段，默认为1 （非必填）
* @apiParam {int} limit 每页大小，默认为50，如果传入值小于0，则不分页。（非必填）
* @apiParam {string} sessionToken 
* @apiParamExample {json} post数据:
*	{
*		"name":"test",
*		"status":1,
*		"sessionToken":"aaabbb"
*	}
* @apiParam (输出字段){string} short_name 简称
* @apiParam (输出字段){string} full_name 全称
* @apiParam (输出字段){string} short_code 简码
* @apiParam (输出字段){string} is_internal 是否公司内部主体 1是 2否
* @apiParam (输出字段){string} status 注销启用标识。1为启用，2为注销
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":{
*    "page": 1,
*    "limit": 50,
*    "count": 1,
*    "data":[
*     {"uuid":"finan20180302000001",
*     "short_name": "test",
*     "full_name": "test",
*     "short_code": "aabb",
*     "is_internal" : "1",
*     "status": 1
*    }]
*   }
* }
*/

/**
 * @api {POST} /com.jyblife.logic.bg.base.MainBodyCreateOrUpdate 主体新增或更新
 * @apiGroup Base
 * @apiParam {string} uuid 主体唯一标示（非必填，不传入则为新增，传入则为更新）
 * @apiParam {string} short_name 主体简称
 * @apiParam {string} full_name 主体简称
 * @apiParam {string} is_internal 是否公司内部主体 1是 2否
 * @apiParam {string} short_code 主体简称(非必填)
 * @apiParam {string} sessionToken
 * 
 * @apiParamExample {json} post数据:
 *	{
 *		"short_name":"test",
 *		"full_name":"test",
 *		"short_code":"aabb",
 *		"is_internal":1,
 *		"sessionToken":"aaabbb"
 *	}
 * @apiParam (输出字段){string} uuid 主体唯一标示
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{
 *    {
 *    	"uuid":"finan20180302000001"
 *    }
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.base.MainBodyStatusModify 主体状态变更
 * @apiGroup Base
 * @apiParam {string} uuid 主体唯一标示
 * @apiParam {string} status 是否启用 1启用 2 注销
 * @apiParam {string} sessionToken
 *
 * @apiParamExample {json} post数据:
 *	{
 *		"uuid":"aabb",
 *		"status":2,
 *		"sessionToken":"aabb"
 *	}
 */

/**
 * @api {POST} /com.jyblife.logic.bg.base.MainBodyDetail 主体详情
 * @apiGroup Base
 * @apiParam {string} uuid 主体唯一标示
 * @apiParam {string} sessionToken
 * @apiParam (输出字段){string} short_name 简称
 * @apiParam (输出字段){string} full_name 全称
 * @apiParam (输出字段){string} short_code 简码
 * @apiParam (输出字段){string} is_internal 是否公司内部主体 1是 2否
 * @apiParam (输出字段){string} status 是否启用 1启用 2 注销
 *
 * @apiParamExample {json} post数据:
 *	{
 *      "uuid":"finan20180302000001",
 *      "short_name": "test",
 *      "full_name": "test",
 *      "short_code": "aabb",
 *      "is_internal" : "1",
 *      "status": 1
 *		"sessionToken":"aabb"
 *	}
 */


/**
 * @api {POST} /com.jyblife.logic.bg.base.SystemList 业务系统列表
 * @apiGroup Base
 * @apiParam {string} sys_name 业务系统名称（支持模糊查询，非必填）
 * @apiParam {string} status 系统状态标识。1启用，2注销（非必填）
 * @apiParam {string} system_flag 业务系统唯一标示（非必填）
 * @apiParam {string} uuid 主体唯一标示（非必填）
 * @apiParam {int} page 分页字段，默认为1 （非必填）
 * @apiParam {int} pageSize 每页大小，默认为50，如果传入值小于0，则不分页。（非必填）
 * @apiParam {string} sessionToken
 * @apiParamExample {json} post数据:
 *	{
 *		"system_flag":"test",
 *		"status":1,
 *		"sessionToken":"aaabbb"
 *	}
 * @apiParam (输出字段){string} system_flag 业务系统唯一标示
 * @apiParam (输出字段){string} sys_name 系统名称
 * @apiParam (输出字段){string} ip_address 服务器ip限制
 * @apiParam (输出字段){string} pwd_key 秘钥
 * @apiParam (输出字段){int} status 状态 1、启用 2注销 
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":[
 *    {"uuid":"20180302000001",
 *    "system_flag": "test",
 *    "sys_name": "test",
 *    "ip_address": "172.16.1.1,172.16.1.2",
 *    "pwd_key" : "11adsfa",
 *    "status": 1
 *   }]
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.base.SystemCreateOrUpdate 业务系统新增或更新
 * @apiGroup Base
 * @apiParam {string} uuid 业务系统id（非必填，不传入则为新增，传入则为更新）
 * @apiParam {string} system_flag 主体唯一标示
 * @apiParam {string} sys_name 系统名称
 * @apiParam {string} ip_address 服务器ip限制（非必填）
 * @apiParam {string} pwd_key 秘钥
 * @apiParam {string} sessionToken
 * @apiParamExample {json} post数据:
 *	{
 *		"system_flag":"test",
 *		"sys_name":"test",
 *		"ip_address":"172.16.1.1,172.16.1.2",
 *		"pwd_key":"aaabbb",
 *		"sessionToken":"ccc"
 *	}
 * @apiParam (输出字段){string} uuid 
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{
 *    {
 *    	"uuid":"finan20180302000001"
 *    }
 * }
 */
/**
 * @api {POST} /com.jyblife.logic.bg.base.SystemDetail 业务系统详情
 * @apiGroup Base
 * @apiParam {string} uuid 业务系统id（非必填，不传入则为新增，传入则为更新）
 * @apiParam {string} sessionToken
 * @apiParamExample {json} post数据:
 *	{
 *		"uuid":"111",
 *		"sessionToken":"ccc"
 *	}
 * @apiParam (输出字段){string} uuid 业务系统唯一标示
 * @apiParam (输出字段){string} system_flag 业务系统唯一标示
 * @apiParam (输出字段){string} sys_name 系统名称
 * @apiParam (输出字段){string} ip_address 服务器ip限制
 * @apiParam (输出字段){string} pwd_key 秘钥
 * @apiParam (输出字段){int} status 状态 1、启用 2注销 
 * @apiParam (输出字段){string} create_time 创建时间
 * @apiParam (输出字段){string} update_time 修改时间
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{
 *    {
 *    	"uuid":"finan20180302000001"
 *    }
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.base.SystemStatusModify 业务系统状态变更
 * @apiGroup Base
 * @apiParam {string} uuid 主体唯一标示
 * @apiParam {string} status 是否启用 1启用 2 注销 3删除
 * @apiParam {string} sessionToken
 *
 * @apiParamExample {json} post数据:
 *	{
 *		"uuid":"aabb",
 *		"status":2,
 *		"sessionToken":"aabb"
 *	}
 */


/**
 * @api {POST} /com.jyblife.logic.bg.base.DictKvList 数据字典键值列表
 * @apiGroup Base
 * @apiParam {string} dict_type 数据字典类型（非必填）
 * @apiParam {string} dict_desc 数据字典描述（非必填）
* @apiParam {int} page 分页字段，默认为1 （非必填）
* @apiParam {int} pageSize 每页大小，默认为50，如果传入值小于0，则不分页。（非必填）
 * @apiParam {string} sessionToken
 * @apiParamExample {json} post数据:
 *	{
 *		"dict_type":"test",
 *		"dict_desc":1,
 *		"sessionToken":"aaabbb"
 *	}
 * @apiParam (输出字段){string} uuid 唯一标示
 * @apiParam (输出字段){string} dict_key 数据字典键值
 * @apiParam (输出字段){string} dict_value 标签
 * @apiParam (输出字段){string} dict_type 数据字典类型
 * @apiParam (输出字段){string} dict_desc 数据字典描述
 * @apiParam (输出字段){int} index 排序字段
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":[
 *    {"uuid":"20180302000001",
 *    "dict_key": "111",
 *    "dict_value": "test1",
 *    "dict_type": "test",
 *    "dict_desc" : "字典描述",
 *    "index": 20
 *   }]
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.base.DictCreateOrUpdate 数据字典新增或更新
 * @apiGroup Base
 * @apiParam {string} uuid 数据字典id（非必填，不传入则为新增，传入则为更新）
 * @apiParam {string} dict_key 数据字典键值
 * @apiParam {string} dict_value 标签
 * @apiParam {string} dict_type 数据字典类型
 * @apiParam {string} dict_desc 数据字典描述
 * @apiParam {int} index 排序字段
 * @apiParam {string} sessionToken
 * @apiParamExample {json} post数据:
 *	{
 *		"dict_key":"test",
 *		"dict_value":"test",
 *		"dict_type":"172.16.1.1,172.16.1.2",
 *		"dict_desc":"aaabbb",
 *		"index":1,
 *		"sessionToken":"ccc"
 *	}
 * @apiParam (输出字段){string} uuid
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{
 *    {
 *    	"uuid":"20180302000001"
 *    }
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.base.DictKvDel 数据字典键值删除
 * @apiGroup Base
 * @apiParam {string} uuid 
 * @apiParam {string} sessionToken
 *
 * @apiParamExample {json} post数据:
 *	{
 *		"uuid":"aabb",
 *		"sessionToken":"asab"
 *	}
 */


/**
 * @api {POST} /com.jyblife.logic.bg.base.DictList 数据字典列表
 * @apiGroup Base
 * @apiParam {string} dict_type 数据字典类型（非必填）
 * @apiParam {string} dict_desc 数据字典描述（非必填）
* @apiParam {int} page 分页字段，默认为1 （非必填）
* @apiParam {int} pageSize 每页大小，默认为50，如果传入值小于0，则不分页。（非必填）
 * @apiParam {string} sessionToken
 * @apiParamExample {json} post数据:
 *	{
 *		"dict_type":"test",
 *		"dict_desc":1,
 *		"sessionToken":"aaabbb"
 *	}
 * @apiParam (输出字段){string} uuid 唯一标示
 * @apiParam (输出字段){string} dict_type 数据字典类型
 * @apiParam (输出字段){string} dict_desc 数据字典描述
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":[
 *    {"uuid":"20180302000001",
 *    "dict_type": "test",
 *    "dict_desc" : "字典描述"
 *   }]
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.base.BaseBankQuery 数据字典列表
 * @apiGroup Base
 * @apiParam {string} bank 数据字典类型 1：兴业银行；2：招商银行；3：建设银行；4：平安银行；5：农业银行
 * @apiParam {string} sessionToken
 * @apiParamExample {json} post数据:
 *	{
 *		"bank":"1",
 *		"sessionToken":"aaabbb"
 *	}
 * @apiParam (输出字段){string} bank 银行对应标识
 * @apiParam (输出字段){string} code 	编码
 * @apiParam (输出字段){string} name 名称
 * @apiParam (输出字段){string} provinceName 省名称
 * @apiParam (输出字段){string} citycodes 城市列表
 * @apiParam (输出字段){string} areaCodes 地区列表
 * @apiParam (输出字段){string} currencys 币种列表
 * @apiParam (输出字段){string} banks 银行列表
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{
 *       "bank": "1",
 *       "provinces": [
 *            {
 *               "provinceName": "广东省",
 *               "citycodes": [
 *                   {
 *                       "code": "GDZS",
 *                       "name": "中上市"
 *                   },
 *                   {
 *                       "code": "GDCZ",
 *                       "name": "潮州市"
 *                   }
 *               ]
 *           },
 *           {
 *               "provinceName": "重庆市",
 *               "citycodes": [
 *                   {
 *                       "code": "GDZS",
 *                       "name": "中上市"
 *                   },
 *                   {
 *                       "code": "GDCZ",
 *                       "name": "潮州市"
 *                   }
 *               ]
 *           }
 *       ],
 *       "areaCodes": [
 *           {
 *               "code": "10",
 *               "name": "北京"
 *           },
 *           {
 *               "code": "12",
 *               "name": "离岸分行"
 *           }
 *       ],
 *       "currencys": [
 *           {
 *               "code": "10",
 *               "name": "人民币"
 *           },
 *           {
 *               "code": "21",
 *               "name": "港币"
 *           }
 *       ],
 *       "banks": [
 *           {
 *               "code": "1",
 *               "name": "兴业银行"
 *           },
 *           {
 *               "code": "2",
 *               "name": "农业银行"
 *           }
 *       ]
 *  }
 * }
 */
