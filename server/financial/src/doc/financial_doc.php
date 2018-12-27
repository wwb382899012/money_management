<?php
/**
 * @api {POST} /com.jyblife.logic.bg.financial.AddProduct 新增理财产品
 * @apiGroup FProduct
 * @apiParam {string} sessionToken 会话token
 * @apiParam {string} product_name 理财产品名称
 * @apiParam {string} bank_dict_value 银行名称
 * @apiParamExample {json} post数据:
 *  {product_name":"点点金7001", "bank_dict_value":"招商银行", "sessionToken":"xxxxx"}
* @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{"uuid":"xxxx"}
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.financial.UpdateProduct 编辑理财产品
 * @apiGroup FProduct
 * @apiParam {string} uuid 产品uuid
 * @apiParam {Number} status 1正常 2注销
 * @apiParam {string} sessionToken 会话token
 * @apiParam {string} product_name 理财产品名称
 * @apiParam {string} bank_dict_value 银行名称
 * @apiParamExample {json} post数据:
 *  {"uuid":"xxx","product_name":"点点金7001", "bank_dict_value":"招商银行", "sessionToken":"xxxxx","status":1}
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{"uuid":"xxxx"}
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.financial.StatusProduct 注销/启用产品
 * @apiGroup FProduct
 * @apiParam {string} sessionToken 会话token
 * @apiParam {string} uuid 产品uuid
 * @apiParam {inter} status 1正常 2注销
 * @apiParamExample {json} post数据:
 *  {"uuid":"xxx","sessionToken":"xxxxx", "status":1}
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{"uuid":"xxxx"}
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.financial.ListProduct 产品列表
 * @apiGroup FProduct
 * @apiParam {string} sessionToken 会话token
 * @apiParam {inter} page 页码
 * @apiParam {inter} limit 每页限制
 * @apiParam {string} product_name 理财产品名称(搜索条件非必填)
 * @apiParam {string} bank_dict_value 银行名称(搜索条件非必填)
 * @apiParamExample {json} post数据:
 *  {"page":1, "limit":10, "sessionToken":"xxxxx"}
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{"count":10,"page":1,"limit":10,"data":[{
 *     "product_name": "点点金7001",
 *     "bank_dict_value": "中国建设银行",
 *     "status": 1,  # 1正常 2注销
 *     "create_name": "王书莲",
 *     "update_time": "2018-06-08 12:30:13"
 *   }]}
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.financial.AddPlan 新增理财计划
 * @apiGroup Financial
 * @apiParam {String} sessionToken 会话token
 * @apiParam {String} money_manager_product_uuid 理财产品uuid
 * @apiParam {String} plan_main_body_uuid 交易主体uuid
 * @apiParam {String} pay_bank_uuid 出款银行uuid
 * @apiParam {String} pay_bank_account 出款银行账户
 * @apiParam {String} pay_bank_name 出款银行名称
 * @apiParam {Number} real_pay_type 付款方式：1网银 2银企
 * @apiParam {Number} term_type 基金类型：1开放式 2封闭式
 * @apiParam {Number} amount 投资总本金，单位分
 * @apiParam {String="cny"} currency 币种，目前固定为人民币
 * @apiParam {String} rate_start_date 起息日
 * @apiParam {String} rate_over_date 投资产品到期日(封闭式产品必填)
 * @apiParam {Number} forecast_annual_income_rate 预计年化收益率
 * @apiParam {Number} forecast_interest 预计利息
 * @apiParam {Number} if_audit 1提交审核 2不提交审核
 * @apiParam {Object} cash_flow[] 现金流
 * @apiParam {String} cash_flow___repay_date 日期
 * @apiParam {String} cash_flow___cash_flow_type 事项：1本金支付 2本金回款 3利息回款
 * @apiParam {Number} cash_flow___amount 金额(分)
 * @apiParam {Number=0} cash_flow___change_amount 调整额(只有利息有调整额)
 * @apiParam {String} cash_flow___info 说明(非必填)
 * @apiParamExample {json} post数据:
 * {"money_manager_product_uuid":"18745e2b11a08478e40b154ad352b906","plan_main_body_uuid": "9699cbdcfeffff98592b0135eb76fe49", "pay_bank_uuid": "9699cbdcfeffff98592b0135eb76fe49","pay_bank_account":"6225xxxx","pay_bank_name": "招商银行","real_pay_type": "1","term_type": 2,"amount":1000000,"currency": "cny","rate_start_date": "2018-03-14","rate_over_date": "2019-03-14","forecast_annual_income_rate": 0.1,"forecast_interest": 100000,"if_audit": 2,"cash_flow": [{"repay_date": "2018-03-14","cash_flow_type": 1,"amount": "1000000","change_amount": 0},{"repay_date": "2019-03-14","cash_flow_type": 2,"amount": "1000000", "change_amount": 0},{"repay_date": "2019-03-14","cash_flow_type": 3,"amount": "100000","change_amount": 0}]}
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{"uuid":"xxxxx"}
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.financial.SavePlan 编辑理财计划
 * @apiGroup Financial
 * @apiParam {String} plan_uuid 编辑不能为空
 * @apiParam {String} sessionToken 会话token
 * @apiParam {String} money_manager_product_uuid 理财产品uuid
 * @apiParam {String} plan_main_body_uuid 交易主体uuid
 * @apiParam {String} pay_bank_uuid 出款银行uuid
 * @apiParam {String} pay_bank_account 出款银行账户
 * @apiParam {String} pay_bank_name 出款银行名称
 * @apiParam {Number} real_pay_type 付款方式：1网银 2银企
 * @apiParam {Number} term_type 基金类型：1开放式 2封闭式
 * @apiParam {Number} amount 投资总本金，单位分
 * @apiParam {String="cny"} currency 币种，目前固定为人民币
 * @apiParam {String} rate_start_date 起息日
 * @apiParam {String} rate_over_date 投资产品到期日(封闭式产品必填)
 * @apiParam {Number} forecast_annual_income_rate 预计年化收益率
 * @apiParam {Number} forecast_interest 预计利息
 * @apiParam {Number} if_audit 1提交审核 2不提交审核
 * @apiParam {Object} cash_flow[] 现金流
 * @apiParam {String} cash_flow___repay_date 日期
 * @apiParam {String} cash_flow___cash_flow_type 事项：1本金支付 2本金回款 3利息回款
 * @apiParam {Number} cash_flow___amount 金额(分)
 * @apiParam {Number=0} cash_flow___change_amount 调整额(只有利息有调整额)
 * @apiParam {String} cash_flow___info 说明(非必填)
 * @apiParamExample {json} post数据:
 * {"money_manager_product_uuid":"18745e2b11a08478e40b154ad352b906","plan_main_body_uuid": "9699cbdcfeffff98592b0135eb76fe49", "pay_bank_uuid": "9699cbdcfeffff98592b0135eb76fe49","pay_bank_account":"6225xxxx","pay_bank_name": "招商银行","real_pay_type": "1","term_type": 2,"amount":1000000,"currency": "cny","rate_start_date": "2018-03-14","rate_over_date": "2019-03-14","forecast_annual_income_rate": 0.1,"forecast_interest": 100000,"if_audit": 2,"cash_flow": [{"repay_date": "2018-03-14","cash_flow_type": 1,"amount": "1000000","change_amount": 0},{"repay_date": "2019-03-14","cash_flow_type": 2,"amount": "1000000", "change_amount": 0},{"repay_date": "2019-03-14","cash_flow_type": 3,"amount": "100000","change_amount": 0}]}
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data":{"uuid":"xxxxx"}
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.financial.ListPlan 理财计划列表
 * @apiGroup Financial
 * @apiParam {String} sessionToken 会话token
 * @apiParam {Number} page 页码
 * @apiParam {Number} limit 每页数量
 * @apiParam {Number} hasAudit 0 包含所有 1只包含无需审核处理的 2只包含需要审核处理(非必填，默认为0)
 * @apiParamExample {json} post数据:
 * {"page":1, "limit":10, "sessionToken":"xxx"}
 * @apiSuccessExample {json} 输出示例:
 * {"code":"0","msg":"success","data":{"page":"1","limit":"10","count":"3","data":[{"money_manager_product_uuid":"18745e2b11a08478e40b154ad352b906","money_manager_plan_num":"","plan_main_body_uuid":"9699cbdcfeffff98592b0135eb76fe49","plan_main_body_name":"","term_type":"2","amount":"1000000","currency":"cny","rate_start_date":"2018-03-14","rate_over_date":"2019-03-14","plan_status":"1","is_pay_off":"1","create_time":"2018-03-29 16:34:13","plan_uuid":"47ab3fb7880b69d12eeb787df162c52f","money_manager_product_name":"点点金7001"}]}}
 * @apiParam (输出字段) {string} money_manager_product_uuid 产品id
 * @apiParam (输出字段) {string} money_manager_plan_num 理财计划编号
 * @apiParam (输出字段) {String} plan_main_body_uuid 交易主体编号
 * @apiParam (输出字段) {String} plan_main_body_name 交易主体名称
 * @apiParam (输出字段) {Number} term_type 1开放式 2封闭式
 * @apiParam (输出字段) {string} rate_start_date 起息日
 * @apiParam (输出字段) {String} rate_over_date 到期日
 * @apiParam (输出字段) {String} plan_status 0未提交 1审核中 2已审核完成 3意拒绝 20完结
 * @apiParam (输出字段) {String} is_pay_off 1未还清 2还清
 */

/**
 * @api {POST} /com.jyblife.logic.bg.financial.DetailPlan 理财计划详情
 * @apiGroup Financial
 * @apiParam {String} sessionToken 会话token
 * @apiParam {String} plan_uuid 理财计划uuid
 * @apiParamExample {json} post数据:
 * {"plan_uuid":"xxxx", "bank_water":"xxxx", "plan_uuid":"xxxx","sessionToken":"xxx"}
 * @apiSuccessExample {json} 输出示例:
 * {"code":"0","msg":"success","data":{"money_manager_product_uuid":"18745e2b11a08478e40b154ad352b906","money_manager_plan_num":"","plan_main_body_uuid":"9699cbdcfeffff98592b0135eb76fe49","plan_main_body_name":"","term_type":"2","amount":"1000000","currency":"cny","rate_start_date":"2018-03-14","rate_over_date":"2019-03-14","plan_status":"1","is_pay_off":"1","create_time":"2018-03-29 16:34:13","cash_flow":[{"uuid":"2624e6777dbcae1eb87b67b07174439c","money_manager_plan_uuid":"47ab3fb7880b69d12eeb787df162c52f","repay_date":"2018-03-14","cash_flow_type":"1","amount":"1000000","change_amount":"0","info":"","status":"0","update_time":"2018-03-29 16:34:44","create_time":"2018-03-29 16:34:13"}],"audit_step":1}}
 * @apiParam (输出字段) {string} cash_flow_type 1本金支付 2本金回款 3利息回款
 * @apiParam (输出字段) {string} status 0未提交 1审核中 2已审核 3已驳回 20完结
 * @apiParam (输出字段) {string} audit_step 1未有审核状态 2资金专员审核 3权限人审核 4上传流水
 */

/**
 * @api {POST} /com.jyblife.logic.bg.financial.AuditPlan 理财审核
 * @apiGroup Financial
 * @apiParam {String} sessionToken 会话token
 * @apiParam {String} plan_uuid 理财计划uuid
 * @apiParam {String} type start发起审批|audit审核
 * @apiParam {String} info 审核建议(非必填)
 * @apiParam {String} approve_type type为audit为必填，1 审批通过 2 审批拒绝
 * @apiParam {String} bank_water 银行流水(回填银行流水必填)
 * @apiParam {String} bank_img_file_uuid 银行流水截图uuid(回填银行流水必填)
 * @apiParamExample {json} post数据:
 * {"plan_uuid":"xxxx", "bank_water":"xxxx", "plan_uuid":"xxxx","sessionToken":"xxx"}
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data": {}
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.financial.SaveRedemption 新增/更新赎回
 * @apiGroup Fredemp
 * @apiParam {String} sessionToken 会话token
 * @apiParam {String} plan_uuid 理财计划uuid
 * @apiParam {Array} cash_flow 如下: uuid为空新增;cash_flow_type 2本金回款 3利息回款;is_delete 2为删除;audit 1提交审核 2不提交审核
 * @apiParamExample {json} post数据:
 * {"plan_uuid":"xxxx", "sessionToken":"xxx", "cash_flow":[{"repay_date":"2018-03-21","cash_flow_type":2,"amount":10000,"change_amount":0, "info":"说明", "audit":1}]}
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data": [{"repay_date":"2018-03-21","cash_flow_type":2,"amount":10000,"change_amount":0, "info":"说明","uuid":"xxxx"}]
 * }
 */

/**
 * @api {POST} /com.jyblife.logic.bg.financial.AuditRedemption 赎回审核
 * @apiGroup Fredemp
 * @apiParam {String} sessionToken 会话token
 * @apiParam {String} plan_uuid 理财计划uuid
 * @apiParam {Array} cash_flow 如下: uuid：现金流uuid，bank_water：银行流水，bank_img_file_uuid：银行流水截图uuid
 * @apiParamExample {json} post数据:
 * {"plan_uuid":"xxxx","sessionToken":"xxx", "cash_flow":[{"uuid":"xxx", "bank_water":"xxx", "bank_img_file_uuid":"xxx"},{"uuid":"xxx", "bank_water":"xxx", "bank_img_file_uuid":"xxx"}]}
 * @apiSuccessExample {json} 输出示例:
 * {
 *   "code": 0,
 *   "msg": "success",
 *   "data": {}
 * }
 */

/**
 * @api {POST} /event 理财事件
 * @apiGroup Financial
 * @apiParam {String}  exchange_name 交换机名称:logic.gb.direct.money.financial
 * @apiParam {String}  rout_1 路由键(理财产品创建):financial_plan.create
 * @apiParam {String}  rout_2 路由键(理财产品审核事件):financial_plan.audit
 * @apiParam {String}  rout_3 路由键(理财赎回审核事件):financial_plan.redemption.audit
 * @apiParamExample {json} rout_1消息内容:
 * {
 *  "node_code": "xxx",
 *  "plan_uuid":"xxxx",
 *  "money_manager_product_name":"理财产品名称",
 *  "money_manager_plan_num":"xxxx",   #理财计划编号
 *  "amount":"100000",
 *  "create_user_id":"15",
 *  "create_time": ""
 * }
 * @apiParamExample {json} rout_2消息内容:
 * {
 *  "node_code": "xxx",
 *  "plan_uuid":"xxxx",
 *  "money_manager_plan_num":"xxxx",   #理财计划编号
 *  "amount": 1000000,               # 金额
 *  "real_pay_type": 1,              # 付款方式 1网银 2银企
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
 *  "plan_uuid":"xxxx",          #理财产品uuid
 *  "money_manager_plan_num":"xxxx",   #理财计划编号
 *  "cash_flow_data":[{
 *    "repay_date": "2018-09-01",    #日期
 *    "cash_flow_type": "1"     #类型 1本金支付 2本金回款 3利息回款
 *    "amount": "100000",       #金额
 *    "change_amount":0,        #调整额
 *    "create_user_name": "曾海鹏",
 *    "create_user_id":"15",
 *    "create_user_email":"1234@qq.com",
 *  }],
 *  "cur_audit_user_id":"1223",   #当前审核人
 *  "cur_audit_control_type":"2",  #当前审核操作 1等待 2通过 3驳回 4拒绝
 *  "next_audit_user_infos": [{"id":"123", "email":"123@qq.com","name":"曾海鹏"}],   #下个审核人(为0,表示流程完结)
 *  "audit_datetime": "2018-03-23 11:25",   #审核时间
 * }
 */

