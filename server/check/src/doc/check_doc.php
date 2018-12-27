<?php
/**
 * @api {POST} /com.jyblife.logic.bg.check.CheckList 银行账户列表
 * @apiGroup Check
 * @apiParam {string} main_body_uuid 主体uuid（非必填）
 * @apiParam {string} bank_name 开户行名称（支持同时对简称、全称模糊查询，非必填）
 * @apiParam {string} real_pay_type 实付类型。0为全部 1为网银 2为银企 3为第三方代付（非必填）
 * @apiParam {int} page 分页字段，默认为1 （非必填）
 * @apiParam {int} pageSize 每页大小，默认为50，如果传入值小于0，则不分页。（非必填）
 * @apiParam {string} sessionToken
 * @apiParamExample {json} post数据:
 *	{
 *		"main_body_uuid":"1",
 *		"real_pay_type":1,
 *		"sessionToken":"aaabbb"
 *	}
 * @apiParam (输出字段){string} main_body_uuid 交易主体uuid
 * @apiParam (输出字段){string} main_body_name 交易主体名称
 * @apiParam (输出字段){string} short_name 账户简称
 * @apiParam (输出字段){string} bank_name 开户行名称
 * @apiParam (输出字段){string} bank_account 银行账号
 * @apiParam (输出字段){string} bank_dict_key 银行数据字典key
 * @apiParam (输出字段){string} account_name 开户户名
 * @apiParam (输出字段){string} provice 开户行省份
 * @apiParam (输出字段){string} city 开户行城市
 * @apiParam (输出字段){string} area 开户行地区
 * @apiParam (输出字段){string} address 开户行地址
 * @apiParam (输出字段){string} interface_priv 可访问系统
 * @apiParam (输出字段){string} real_pay_type 付款方式 1网银 2银企
 * @apiParam (输出字段){int} balance 余额
 * @apiParam (输出字段){int} single_pay_limit 单笔支付限额
 * @apiParam (输出字段){int} status 账户状态 0正常 1冻结 2注销
 * @apiParam (输出字段){int} deal_status 处理状态 0未处理 1已处理 2已驳回 20完成
 * @apiParam (输出字段){string} create_user_name 创建人
 * @apiParam (输出字段){string} update_time 最后修改时间
 */
