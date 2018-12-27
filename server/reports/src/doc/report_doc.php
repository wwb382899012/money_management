<?php
/**
* @api {POST} /com.jyblife.logic.bg.reports.FullTrade 全量交易报表
* @apiGroup Report
* @apiParam {string} sessionToken 会话token
* @apiParam {int} page 当前页码
* @apiParam {int} limit 每页显示数量
* @apiParamExample {json} post数据:
*  {"page":1, "limit":"100", "sessionToken":"xxxxx"}
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":{
*    "page":1,"limit":10,"count":20,"data":[{
*      "out_order_num":"xxxx",      #外部指令编号
*      "trade_order_num":"xxxx",    #交易指令编号
*      "pay_date": "2018-09-01",    #要求付款日期
*      "trade_type": "1",           #交易类型:1付款 2借还款 3内部调拨 4理财
*      "trade_son_type": "交易细类",
*      "currency": "cny",
*      "amount": 1000000,
*      "pay_main_body_name": "付款方",
*      "pay_bank_name": "付款银行名称",
*      "pay_bank_account": "6226xxxxx", #付款账号
*      "collect_main_body_name": "收款方",
*      "collect_bank_name": "收款银行名称",
*      "collect_bank_account": "6286xxxxx", #收款银行账户
*      "bank_water_no": "2783972377629",  #银行交易流水
*      "real_pay_type": "1",  #实付类型 1网银 2银企
*      "is_financing": "1",   #融资方式 0无需融资 1需要融资
*      "financing_dict_value": "保理公司",  
*      "trade_status": "已还清",   #交易状态
*      "mature_date": "2018-09-03",  #到期日
*      "interest_rate": "0.02",    #利息率
*      "trade_create_datetime": "2018-02-01 12:00:00", #指令到达时间
*      "trade_receive_datetime": "2018-02-01 12:00:00", #指令接收时间
*      "trade_entry_datetime": "2018-02-01 12:00:00", #交易录入时间
*      "order_create_user_name": "曾海鹏",   #指令提交人
*      "audit_name_1": "曾海鹏",  #资金专员
*      "audit_datetime_1": "2018-09-10 12:00:00",  #资金专员审核时间 
*      "audit_name_2": "曾海鹏",  #权签人
*      "audit_datetime_1": "2018-09-10 12:00:00",  #权签人审核时间 
*   }]} 
* }
*/

/**
 * @api {POST} /com.jyblife.logic.bg.reports.EodTrade EOD日终检查表
 * @apiGroup Report
 * @apiParam {string} sessionToken 会话token
 * @apiParam {bool} generate 是否生成
 * @apiParam {int} page 当前页码
 * @apiParam {int} limit 每页显示数量
 * @apiParamExample {json} post数据:
 *  {"page":1, "limit":"100", "sessionToken":"xxxxx"}
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{
 *    "page":1,"limit":10,"count":20,"data":[{
 *      "out_order_num":"xxxx",      #外部指令编号
 *      "trade_order_num":"xxxx",    #交易指令编号
 *      "trade_uuid": "xxxx",    #业务主键
 *      "trade_type": "1",           #交易类型:1付款 2借还款 3内部调拨 4理财
 *      "trade_son_type": "交易细类",
 *      "trade_entry_datetime": "2018-02-01 12:00:00", #指令到达时间
 *      "trade_receive_datetime": "2018-02-01 12:00:00", #指令接收时间
 *      "trade_create_datetime": "2018-02-01 12:00:00", #调拨交易生成时间
 *      "trade_audit_datetime": "2018-09-10 12:00:00",  #调拨交易审批时间
 *   }]}
 * }
 */