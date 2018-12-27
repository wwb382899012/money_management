<?php
/**
 * @api {POST} /com.jyblife.logic.bg.loan.LoanOrder 借款指令
 * @apiGroup Loan
 * @apiParam {string} system_flag 业务系统标示 (每个业务系统单独申请)
 * @apiParam {string} out_order_num 唯一标示 (模块唯一)
 * @apiParam {string} loan_main_body 贷款主体名称
 * @apiParam {string} loan_bank_name 贷款银行名称
 * @apiParam {string} loan_bank_account 贷款银行账号
 * @apiParam {string} collect_main_body 借款方
 * @apiParam {string} collect_bank_name 借款银行名称
 * @apiParam {string} collect_bank_account 借款银行账户
 * @apiParam {string} collect_account_name 借款方开户户名
 * @apiParam {float} rate 利息率
 * @apiParam {int} amount 付款金额(分)
 * @apiParam {string} currency 币种 （非必填，默认为"cny")
 * @apiParam {string} bs_background 业务背景 (非必填)
 * @apiParam {string} loan_date 借款日期
 * @apiParam {string} forecast_date 预计还款日期
 * @apiParam {string} order_create_people 指令发起人(非必填)
 * @apiParam {string} special_require 特殊要求(非必填)
 * @apiParam {string} plus_require 其他要求(非必填)
 * @apiParam {string} contact_annex_uuids 合同附件列表(非必填)
 * @apiParam {string} others_annex_uuids 其他附件列表(非必填)
 * @apiParam {int} timestamp 时间戳,精确到秒
 * @apiParam {string} secret 加密串。
 *    所有参数按照字母顺序排序后，根据a=1&b=2格式连接成新的字符串，并在结尾加入业务系统秘钥（由资金平台统一分配给各个业务系统）后，整个字符串进行sha1签名的结果。
 *    如参数b=1,a=2,key=aabb
 *    则加密前字符串为a=1&b=2aabb
 * @apiParamExample {json} post数据:
 *    {
 *        "system_flag":"test",
 *        "out_order_num":"finan20180302000001",
 *        "loan_main_body_uuid":"buss1",
 *        "loan_bank_name":"招商银行",
 *        "loan_bank_account":"1111111111111111",
 **        "loan_main_body_uuid":"buss1",
 *        "loan_bank_name":"招商银行",
 *        "loan_bank_account":"1111111111111111",
 *        "collect_main_body":"加油宝科技平台",
 *        "amount":111,
 *        "timestamp":1519981745,
 *        "secret":"aaabbadsfadsfadfasdfadsf"
 *    }
 * @apiParam (输出字段){string} out_order_num 外部订单号
 * @apiParam (输出字段){string} order_num 订单编号
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{
 *    "out_order_num":"finan20180302000001",
 *    "order_num": "20180302000001",
 *    "amount": 111
 *   }
 * }
 */
 
 /**
 * @api {POST} /com.jyblife.logic.bg.loan.LoanOrderQuery 指令状态查询
 * @apiGroup Loan
 * @apiParam {string} system_flag 业务系统标示 (每个业务系统单独申请)
 * @apiParam {string} order_num 订单编号 (非必填)
 * @apiParam {string} out_order_num 外部订单号 (非必填)
 * @apiParam {string} order_status 状态 (非必填) 0未处理 1已处理 2已驳回 20归档完结',
 * @apiParam {string} apply_begin_time 查询范围开始时间 (非必填)
 * @apiParam {string} apply_end_time 查询范围结束时间 (非必填)
 * @apiParam {int} timestamp 时间戳,精确到秒
 * @apiParam {int} page 分页字段，默认为1 （非必填）
 * @apiParam {int} pageSize 每页大小，默认为50，如果传入值小于0，则不分页。（非必填）
 * @apiParam {string} secret 加密串。
 *    所有参数按照字母顺序排序后，根据a=1&b=2格式连接成新的字符串，并在结尾加入业务系统秘钥（由资金平台统一分配给各个业务系统）后，整个字符串进行sha1签名的结果。
 *    如参数b=1,a=2,key=aabb
 *    则加密前字符串为a=1&b=2aabb
 * @apiParamExample {json} post数据:
 *    {
 *        "system_flag":"test",
 *        "out_order_num":"finan20180302000001"
 *        "timestamp":1519981745,
 *        "secret":"aaabbadsfadsfadfasdfadsf"
 *    }
 * @apiParam (输出字段){string} out_order_num 外部订单号
 * @apiParam (输出字段){string} order_num 订单编号
 * @apiParam (输出字段) {int} loan_type 指令类别 1:借款 2:提前还款 3:延期还款'
 * @apiParam (输出字段) {string} amount 付款金额
 * @apiParam (输出字段) {string} order_status 指令状态 0待审核 1已审核 2已驳回 20归档完结',
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "msg": "success",
 *   "code": 0,
 *   "data":{[
 *        "out_order_num":"finan20180302000001",
 *        "order_num": "20180302000001",
 *        "loan_type":1,
 *        "order_status":1
 *   ]}
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.loan.LoanOrderList 借款指令列表
 * @apiGroup Loan
 * @apiParam {string} order_create_people 指令发起人(搜索条件,非必填)
 * @apiParam {inter} order_status 指令状态(搜索条件,非必填)
 * @apiParam {datetime} apply_begin_time 申请开始时间(搜索条件,非必填)
 * @apiParam {datetime} apply_end_time 申请结束时间(搜索条件,非必填)
 * @apiParam {datetime} approve_begin_time 审批开始时间(搜索条件,非必填)
 * @apiParam {datetime} approve_end_time 审批结束时间(搜索条件,非必填)
 * @apiParam {string} loan_main_body_uuid 借款方(搜索条件,非必填)
 * @apiParam {string} collect_main_body_uuid 借款方(搜索条件,非必填)
 * @apiParam {int} page 分页字段，默认为1 （非必填）
 * @apiParam {int} pageSize 每页大小，默认为50，如果传入值小于0，则不分页。（非必填）
 * @apiParam {string} sessionToken 会话token
 * @apiParam (输出字段){string} uuid 付款指令唯一标示
 * @apiParam (输出字段){string} order_num 指令编号
 * @apiParam (输出字段){string} order_create_people 指令发起人
 * @apiParam (输出字段){string} loan_type 付款指令类别
 * @apiParam (输出字段){int} amount 借款金额
 * @apiParam (输出字段){string} currency 币种
 * @apiParam (输出字段){string} loan_main_body 付款方
 * @apiParam (输出字段){string} collect_main_body 借款方
 * @apiParam (输出字段){string} order_status 指令状态
 * @apiParam (输出字段){string} loan_status 付款状态
 * @apiParam (输出字段){string} loan_main_body 贷款方
 * @apiParam (输出字段){string} collect_main_body 借款方
 * @apiParam (输出字段){string} create_time 创建时间
 * @apiParam (输出字段){string} update_time 状态时间
 * @apiParam (输出字段){json} node_list 审批节点详情 json格式
 * @apiParamExample {json} post数据:
 *    {
 *        "loan_main_body_uuid":"aabb",
 *        "approve_begin_time":"2018-03-11"
 *    }
 * @apiSuccessExample {json} 输出示例:
 * {
 * }
 */


/**
 * @api {POST} /com.jyblife.logic.bg.loan.LoanOrderDetail 借款指令详情
 * @apiGroup Loan
 * @apiParam {string} uuid 借款指令唯一标示
 * @apiParam {string} sessionToken 会话token
 * @apiParam (输出字段){string} order_num 指令编号
 * @apiParam (输出字段){string} out_order_num 外部指令编号
 * @apiParam (输出字段){string} loan_type 贷款类别
 * @apiParam (输出字段){string} loan_main_body 贷款方
 * @apiParam (输出字段){string} loan_bank_name 贷款银行名称
 * @apiParam (输出字段){string} loan_bank_account 贷款银行账户
 * @apiParam (输出字段){string} collect_main_body 借款方
 * @apiParam (输出字段){string} collect_bank_name 借款银行名称
 * @apiParam (输出字段){string} collect_bank_account 借款银行账户
 * @apiParam (输出字段){int} amount 借款金额
 * @apiParam (输出字段){string} currency 币种
 * @apiParam (输出字段){float} rate 利息率
 * @apiParam (输出字段){string} loan_datetime 借款日期
 * @apiParam (输出字段){string} forecast_datetime 预计还款日期
 * @apiParam (输出字段){string} financing_dict_key 融资平台
 * @apiParam (输出字段){string} bs_background 业务背景
 * @apiParam (输出字段){string} order_create_people 指令发起人
 * @apiParam (输出字段){string} special_require 特殊要求
 * @apiParam (输出字段){string} plus_require 其他要求
 * @apiParam (输出字段){string} order_status 指令状态
 * @apiParam (输出字段){string} contact_annex 合同附件一,多个逗号相隔
 * @apiParam (输出字段){string} others_annex 其他附件一,多个逗号相隔
 * @apiParam (输出字段){string} loan_status 付款状态
 * @apiParam (输出字段){string} create_time 申请时间
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
 * @api {POST} /com.jyblife.logic.bg.loan.LoanTransferList 付款调拨列表
 * @apiGroup Loan
 * @apiParam {string} order_create_people 指令发起人(搜索条件,非必填)
 * @apiParam {inter} transfer_status 指令状态(搜索条件,非必填)
 * @apiParam {datetime} apply_begin_time 申请开始时间(搜索条件,非必填)
 * @apiParam {datetime} apply_end_time 申请结束时间(搜索条件,非必填)
 * @apiParam {datetime} approve_begin_time 审批开始时间(搜索条件,非必填)
 * @apiParam {datetime} approve_end_time 审批结束时间(搜索条件,非必填)
 * @apiParam {string} loan_main_body_uuid 付款方(搜索条件,非必填)
 * @apiParam {string} collect_main_body_uuid 借款方(搜索条件,非必填)
 * @apiParam {inter} is_financing 是否对接融资 1、是 2 否(搜索条件,非必填)
 * @apiParam {int} page 分页字段，默认为1 （非必填）
 * @apiParam {int} pageSize 每页大小，默认为50，如果传入值小于0，则不分页。（非必填）
 * @apiParam {string} sessionToken 会话token
 * @apiParam (输出字段){string} uuid 付款调拨唯一标示
 * @apiParam (输出字段){string} transfer_num 调拨指令编号
 * @apiParam (输出字段){string} order_num 付款指令编号
 * @apiParam (输出字段){string} order_create_people 指令发起人
 * @apiParam (输出字段){string} bs_background 业务背景
 * @apiParam (输出字段){string} transfer_loan_type 付款指令类别
 * @apiParam (输出字段){int} amount 付款金额
 * @apiParam (输出字段){string} currency 币种
 * @apiParam (输出字段){string} loan_main_body 贷款方
 * @apiParam (输出字段){string} loan_bank_name 贷款银行名称
 * @apiParam (输出字段){string} loan_bank_account 贷款银行账户
 * @apiParam (输出字段){string} collect_main_body 借款方
 * @apiParam (输出字段){string} collect_bank_name 借款银行名称
 * @apiParam (输出字段){string} collect_bank_account 借款银行账户
 * @apiParam (输出字段){string} annex 附件
 * @apiParam (输出字段){string} special_require 特殊要求
 * @apiParam (输出字段){string} plus_require 其他要求
 * @apiParam (输出字段){string} transfer_status 指令状态
 * @apiParam (输出字段){string} loan_status 付款状态
 * @apiParam (输出字段){string} create_time 创建时间
 * @apiParam (输出字段){string} update_time 状态时间
 * @apiParamExample {json} post数据:
 *    {
 *        "loan_main_body_uuid":"aabb",
 *        "apply_begin_time":"2018-03-11"
 *    }
 * @apiSuccessExample {json} 输出示例:
 * {
 * }
 */


/**
 * @api {POST} /com.jyblife.logic.bg.loan.LoanTransferDetail 借款调拨详情
 * @apiGroup Loan
 * @apiParam {string} uuid 借款调拨唯一标示
 * @apiParam {string} sessionToken 会话token
 * @apiParam (输出字段){string} transfer_num 指令编号
 * @apiParam (输出字段){string} out_order_num 外部指令编号
 * @apiParam (输出字段){string} transfer_loan_type 借款类别
 * @apiParam (输出字段){string} loan_main_body 贷款方
 * @apiParam (输出字段){string} loan_bank_name 贷款银行名称
 * @apiParam (输出字段){string} loan_bank_account 贷款银行账户
 * @apiParam (输出字段){string} collect_main_body 借款方
 * @apiParam (输出字段){string} collect_bank_name 借款银行名称
 * @apiParam (输出字段){string} collect_bank_account 借款银行账户
 * @apiParam (输出字段){string} real_pay_type 1、网银 2、银企
 * @apiParam (输出字段){int} amount 借款金额
 * @apiParam (输出字段){string} currency 币种
 * @apiParam (输出字段){float} rate 利息率
 * @apiParam (输出字段){string} loan_datetime 借款日期
 * @apiParam (输出字段){string} forecast_datetime 预计还款日期
 * @apiParam (输出字段){string} bs_background 业务背景
 * @apiParam (输出字段){string} order_create_people 指令发起人
 * @apiParam (输出字段){string} special_require 特殊要求
 * @apiParam (输出字段){string} plus_require 其他要求
 * @apiParam (输出字段){string} order_status 指令状态
 * @apiParam (输出字段){string} loan_status 付款状态
 * @apiParam (输出字段){string} contact_annex 合同附件一,多个逗号相隔
 * @apiParam (输出字段){string} others_annex 其他附件一,多个逗号相隔
 * @apiParam (输出字段){string} order_status 付款状态
 * @apiParam (输出字段){string} create_time 申请时间
 * @apiParam (输出字段){string} loan_status 审核时间
 * @apiParam (输出字段){string} approve_desc 审核意见
 * @apiParam (输出字段){string} create_time 创建时间
 * @apiParam (输出字段){string} update_time 审核时间
 * @apiParam (输出字段){string} repay_date 还款日期
 * @apiParam (输出字段){string} cash_flow_type 现金流类型
 * @apiParamExample {json} post数据:
 *    {
 *        "uuid":"1111",
 *    }
 * @apiSuccessExample {json} 输出示例:
 * {
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.loan.RepayOrder 还款指令
 * @apiGroup Loan
 * @apiParam {string} system_flag 业务系统标示 (每个业务系统单独申请)
 * @apiParam {string} out_order_num 唯一标示 (模块唯一)
 * @apiParam {string} loan_out_order_num 借款外部请求号
 * @apiParam {string} amount 还款本金
 * @apiParam {string} require_repay_date 要求付款日期
 * @apiParam {string} repay_desc 还款说明 (非必填)
 * @apiParam {string} order_create_people 指令发起人(必填)
 * @apiParam {string} repay_type 还款类型 1 正常还款 2 提前还款 3延期还款
 * @apiParam {int} timestamp 时间戳,精确到秒
 * @apiParam {string} secret 加密串。
 *    所有参数按照字母顺序排序后，根据a=1&b=2格式连接成新的字符串，并在结尾加入业务系统秘钥（由资金平台统一分配给各个业务系统）后，整个字符串进行sha1签名的结果。
 *    如参数b=1,a=2,key=aabb
 *    则加密前字符串为a=1&b=2aabb
 * @apiParamExample {json} post数据:
 *    {
 *        "system_flag":"test",
 *        "out_order_num":"finan20180302000001",
 *        "loan_main_body_uuid":"buss1",
 *        "loan_bank_name":"招商银行",
 *        "loan_bank_account":"1111111111111111",
 **        "loan_main_body_uuid":"buss1",
 *        "loan_bank_name":"招商银行",
 *        "loan_bank_account":"1111111111111111",
 *        "collect_main_body":"加油宝科技平台",
 *        "amount":111,
 *        "timestamp":1519981745,
 *        "secret":"aaabbadsfadsfadfasdfadsf"
 *    }
 * @apiParam (输出字段){string} out_order_num 外部订单号
 * @apiParam (输出字段){string} order_num 订单编号
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{
 *    "out_order_num":"finan20180302000001",
 *    "order_num": "20180302000001",
 *    "amount": 111
 *   }
 * }
 */


/**
 * @api {POST} /event 借款事件
 * @apiGroup Loan
 * @apiParam {String}  exchange_name 交换机名称:logic.gb.direct.money.loan
 * @apiParam {String}  rout_1 路由键(借款指令待审核):loan_order.create
 * @apiParam {String}  rout_2  路由键(借款指令审核通过事件):loan_order.audit
 * @apiParam {String}  rout_3  路由键(借款调拨创建事件):loan_transfer.audit
 * @apiParam {String}  rout_4  路由键(借款调拨审核事件):loan_transfer.audit
 * @apiParamExample {json} rout_1消息内容:
 * {
 *  "node_code": "xxx",
 *  "loan_uuid":"xxxx",
 *  "order_num":"aabb",    #借款指令编号
 *  "loan_main_body":"xxxx",   #贷款主体名称
 *  "collect_main_body":"xxxx",   #借款方名称
 *  "amount":"100000",
 *  "create_time": ""
 *  }
 * @apiParamExample {json} rout_2消息内容:
 * {
 *  "node_code": "xxx",
 *  "loan_uuid":"xxxx",
 *  "order_num":"xxxx",   #借款指令编号
 *  "loan_main_body":"xxxx",   #贷款主体名称
 *  "collect_main_body":"xxxx",   #借款方名称
 *  "amount": 1000000,               # 金额
 *  "create_user_name": "曾海鹏",
 *  "create_user_id":"15",
 *  "create_user_email":"1234@qq.com",
 *  "cur_audit_user_id":"1223",   #当前审核人
 *  "cur_audit_control_type":"2",  #当前审核操作 1等待 2通过 3驳回 4拒绝
 *  "next_audit_user_infos": [{"id":"123", "email":"123@qq.com","name":"曾海鹏"}],   #下个审核人(为0,表示流程完结)
 *  "audit_datetime": "2018-03-23 11:25",   #审核时间
 * }
 * @apiParamExample {json} rout_3消息内容:
 * {
 *  "node_code": "xxx",
 *  "transfer_uuid":"xxxx",
 *  "transfer_num":"aabb",    #借款调拨编号
 *  "loan_main_body":"xxxx",   #贷款主体名称
 *  "collect_main_body":"xxxx",   #借款方名称
 *  "amount":"100000",
 *  "create_time": ""
 *  }
 * @apiParamExample {json} rout_4消息内容:
 * {
 *  "node_code": "xxx",
 *  "transfer_uuid":"xxxx",
 *  "transfer_num":"xxxx",   #借款调拨编号
 *  "loan_main_body":"xxxx",   #贷款主体名称
 *  "collect_main_body":"xxxx",   #借款方名称
 *  "amount": 1000000,               # 金额
 *  "transfer_loan_type": 1,              # 付款类别
 *  "create_user_name": "曾海鹏",
 *  "create_user_id":"15",
 *  "create_user_email":"1234@qq.com",
 *  "cur_audit_user_id":"1223",   #当前审核人
 *  "cur_audit_control_type":"2",  #当前审核操作 1等待 2通过 3驳回 4拒绝
 *  "next_audit_user_infos": [{"id":"123", "email":"123@qq.com","name":"曾海鹏"}],   #下个审核人(为0,表示流程完结)
 *  "audit_datetime": "2018-03-23 11:25",   #审核时间
 * }
 */
