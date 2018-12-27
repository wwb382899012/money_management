<?php
/**
* @api {POST} /com.jyblife.logic.bg.pay.Order 打款
* @apiGroup Pay
* @apiParam {int} trade_type 交易类型  1付款 2借还款 3内部调拨 4理财
* @apiParam {string} order_uuid 打款请求唯一标示
* @apiParam {string} pay_bank_account 打款账号
* @apiParam {string} pay_main_body_uuid 打款主体
* @apiParam {string} collect_bank_account 收款账号
* @apiParam {string} collect_main_body_uuid 收款主体
* @apiParam {string} notice_url 打款结果通知微服务地址
* @apiParam {int} amount 付款金额(分)
* @apiParam {string} currency 币种 （非必填，默认为"cny")
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success"
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.pay.NoticeResult 打款结果通知
* @apiGroup Pay
* @apiParam {string} turnId 交易的唯一标识符号。(请求端产生)
* @apiParam {string} status 打款状态 
* @apiParam {string} desc 异常信息
* @apiParam {string} serialId 银行交易流水号
* @apiSuccessExample {json} 输出示例:
* {
*   "msg": "success",
*   "code": 0
* }
*/

/**
 * @api {POST} /com.jyblife.logic.bg.pay.SetUKPwd 同步ukey读密码
 * @apiGroup Pay
 * @apiParam {string} user_account 用户账号
 * @apiParam {string} readPwd_1 用户读密码 (rsa私钥加密后结果)	
 * @apiParam {string} readPwd_2 用户读密码 (rsa私钥加密后结果)	
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "msg": "success",
 *   "code": 0
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.pay.GetUKPwd 获取ukey读密码
 * @apiGroup Pay
 * @apiParam {string} user_account 用户账号
 * @apiParam (输出字段){string} readPwd_1 ukey读密码
 * @apiParam (输出字段){string} readPwd_2 ukey读密码
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "msg": "success",
 *   "code": 0
 * }
 */
