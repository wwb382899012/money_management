<?php
/**
 * @api {POST} /com.jyblife.logic.bg.inner.CreateTransfer 创建内部调拨指令
 * @apiGroup Inner
 * @apiParam {string} main_body_uuid 划拨主体标识
 * @apiParam {string} pay_bank_uuid 出款银行uuid
 * @apiParam {inter}  pay_bank_account 出款银行账号
 * @apiParam {string} collect_bank_uuid 收款银行uuid
 * @apiParam {string} collect_bank_account 收款银行账号
 * @apiParam {string} real_pay_type 付款方式 1网银 2银企
 * @apiParam {int}     amount 付款金额(分)
 * @apiParam {string} currency 币种 （非必填，默认为"cny")
 * @apiParam {string} hope_deal_date 期望调拨日期
 * @apiParam {string} special_require 调拨说明(非必填)
 * @apiParam {string} annex_uuids 附件一,多个逗号相隔(非必填)
 * @apiParamExample {json} post数据:
 *    {
 *        "main_body_uuid":"1111",
 *        "pay_bank_uuid":"2222",
 *        "pay_bank_account":"6666666",
 *        "collect_bank_uuid":"3333",
 *        "collect_bank_account":"6666",
 **        "amount":"10000",
 *        "currency":"cny",
 *        "hope_deal_date":"2018-04-24",
 *        "collect_main_body":"加油宝科技平台",
 *        "special_require":"aabb",
 *        "annex_uuids":"aabb,ccdd",
 *    }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.inner.InnerTransferList 内部调拨列表
 * @apiGroup Inner
 * @apiParam {string} main_body_uuid 划拨主体标识(搜索条件,非必填)
 * @apiParam {datetime} apply_begin_time 申请开始时间(搜索条件,非必填)
 * @apiParam {datetime} apply_end_time 申请结束时间(搜索条件,非必填)
 * @apiParam {datetime} approve_begin_time 审批开始时间(搜索条件,非必填)
 * @apiParam {datetime} approve_end_time 审批结束时间(搜索条件,非必填)
 * @apiParam {int} page 分页字段，默认为1 （非必填）
 * @apiParam {int} pageSize 每页大小，默认为50，如果传入值小于0，则不分页。（非必填）
 * @apiParam {string} sessionToken 会话token
 * @apiParam (输出字段){string} uuid 内部调拨唯一标示
 * @apiParam (输出字段){string} transfer_num 调拨指令编号
 * @apiParam (输出字段){string} main_body_name 调拨主体
 * @apiParam (输出字段){string} amount 金额
 * @apiParam (输出字段){string} currency 币种
 * @apiParam (输出字段){string} pay_bank_name 出款银行
 * @apiParam (输出字段){string} pay_bank_account 出款银行账户
 * @apiParam (输出字段){string} collect_bank_name 收款银行名称
 * @apiParam (输出字段){string} collect_bank_account 收款银行账户
 * @apiParam (输出字段){string} hope_deal_date 期望调拨日期
 * @apiParam (输出字段){string} real_deal_date 实际调拨日期
 * @apiParam (输出字段){string} transfer_status 指令状态
 * @apiParam (输出字段){string} pay_status 付款状态
 * @apiParam (输出字段){string} create_time 创建时间
 * @apiParam (输出字段){string} update_time 状态时间
 * @apiParamExample {json} post数据:
 *    {
 *        "main_body_uuid":"aabb",
 *        "apply_begin_time":"2018-03-11"
 *    }
 * @apiSuccessExample {json} 输出示例:
 * {
 * }
 */


/**
 * @api {POST} /com.jyblife.logic.bg.inner.InnerTransferDetail 内部调拨详情
 * @apiGroup Inner
 * @apiParam {string} uuid 调拨唯一标示
 * @apiParam {string} sessionToken 会话token
 * @apiParam (输出字段){string} transfer_num 指令编号
 * @apiParam (输出字段){string} main_body_name 调拨主体
 * @apiParam (输出字段){string} pay_bank_name 付款银行名称
 * @apiParam (输出字段){string} pay_bank_account 付款银行账户
 * @apiParam (输出字段){string} collect_bank_name 收款银行名称
 * @apiParam (输出字段){string} collect_bank_account 收款银行账户
 * @apiParam (输出字段){string} real_pay_type 1、网银 2、银企
 * @apiParam (输出字段){int} amount 付款金额
 * @apiParam (输出字段){string} currency 币种
 * @apiParam (输出字段){string} hope_deal_date 期望调拨日期
 * @apiParam (输出字段){string} special_require 调拨说明
 * @apiParam (输出字段){string} order_status 指令状态
 * @apiParam (输出字段){string} loan_status 付款状态
 * @apiParam (输出字段){string} annex 附件
 * @apiParam (输出字段){string} order_status 付款状态
 * @apiParam (输出字段){string} create_time 申请时间
 * @apiParam (输出字段){string} loan_status 审核时间
 * @apiParam (输出字段){string} approve_desc 审核意见
 * @apiParam (输出字段){string} create_time 创建时间
 * @apiParam (输出字段){string} update_time 审核时间
 * @apiParamExample {json} post数据:
 *    {
 *        "uuid":"1111",
 *    }
 * @apiSuccessExample {json} 输出示例:
 * {
 * }
 */
/**
 *
 * @api {POST} /event 内部调拨事件
 * @apiGroup Inner
 * @apiParam {String}  exchange_name 交换机名称:logic.gb.direct.money.inner
 * @apiParam {String}  rout_1  路由键(内部调拨指令创建):inner_transfer.create
 * @apiParam {String}  rout_2  路由键(内部调拨指令审核通过):inner_transfer.audit
 * @apiParamExample {json} rout_1消息内容:
 * {
 *  "node_code": "xxx",
 *  "inner_uuid":"xxxx",
 *  "order_num":"aabb",    #调拨编号
 *  "main_body":"xxxx",      #调拨主体名称
 *  "amount":"100000",
 *  "create_time": ""
 *  }
 * @apiParamExample {json} rout_2消息内容:
 * {
 *  "node_code": "xxx",
 *  "inner_uuid":"xxxx",
 *  "order_num":"aabb",    #调拨编号
 *  "main_body":"xxxx",      #调拨主体名称
 *  "amount":"100000",
 *  "create_user_name": "曾海鹏",
 *  "create_user_id":"15",
 *  "create_user_email":"1234@qq.com",
 *  "cur_audit_user_id":"1223",   #当前审核人
 *  "cur_audit_control_type":"2",  #当前审核操作 1等待 2通过 3驳回 4拒绝
 *  "next_audit_user_infos": [{"id":"123", "email":"123@qq.com","name":"曾海鹏"}],   #下个审核人(为0,表示流程完结)
 *  "audit_datetime": "2018-03-23 11:25",   #审核时间
 *  }
 */
