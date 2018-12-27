<?php
/**
* @api {POST} /com.jyblife.logic.bg.sys_water.AddSysWater 新增系统流水
* @apiGroup Sys_water
* @apiParam {String} sessionToken 会话token
* @apiParam {Number} trade_type 交易类型 1付款 2借还款 3内部调拨 4理财
* @apiParam {String} pay_bank_uuid 付款银行uuid
* @apiParam {String} pay_bank_name 付款银行名称
* @apiParam {String} pay_bank_account 付款账号
* @apiParam {String} pay_main_body_uuid 付款主体uuid
* @apiParam {String} collect_bank_uuid 收款账号uuid(非必填)
* @apiParam {String} collect_bank_name 收款银行名称(非必填)
* @apiParam {String} collect_bank_account 收款银行账号(非必填)
* @apiParam {String} collect_main_body_uuid 收款主体uui(非必填)
* @apiParam {String} out_water_no 外部流水号(非必填)
* @apiParam {Number} amount 金额
* @apiParam {String="cny"} currency 币种
* @apiParamExample {json} post数据:
*  {"trade_type":1, "pay_bank_uuid":"xxxxx","pay_bank_name":"招商银行","pay_bank_account":"xxxxx","pay_main_body_uuid":"xxx", 
*   "amount":1000000,"currency":"cny","sessionToken":"xxxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":{"uuid":"xxxx"} 
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.sys_water.OutSysWater 增加外部流水号
* @apiGroup Sys_water
* @apiParam {String} sessionToken 会话token
* @apiParam {String} out_water_no 外部流水号
* @apiParam {String} water_uuid 流水uuid

* @apiParamExample {json} post数据:
*  {"out_water_no":"xxx","water_uuid":"xxxx","sessionToken":"xxxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":{
*       "raw" : 1    
*   } 
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.sys_water.InvalidWater 作废流水
* @apiGroup Sys_water
* @apiParam {String} sessionToken 会话token
* @apiParam {String} water_uuid 流水uuid

* @apiParamExample {json} post数据:
*  {"out_water_no":"xxx","water_uuid":"xxxx","sessionToken":"xxxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":{
*       "raw" : 1
*   } 
* }
*/