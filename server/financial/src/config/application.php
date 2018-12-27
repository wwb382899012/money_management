<?php
define('AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_1', 'financial_audit_pay_type_1_code');   //网银付款流程
define('AUDIT_NODE_PAY_TYPE_1_NO_1', 'pay_type_1_node_no_1');     //发起审核
define('AUDIT_NODE_PAY_TYPE_1_NO_2', 'pay_type_1_node_no_2');     //资金专员审核
define('AUDIT_NODE_PAY_TYPE_1_NO_3', 'pay_type_1_node_no_3');     //权签人审核
define('AUDIT_NODE_PAY_TYPE_1_NO_4', 'pay_type_1_node_no_4');     //购买理财资金专员上传回单（网银打款）

define('AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_2', 'financial_audit_pay_type_2_code');   //银企付款流程
define('AUDIT_NODE_PAY_TYPE_2_NO_1', 'pay_type_2_node_no_1');     //发起审核
define('AUDIT_NODE_PAY_TYPE_2_NO_2', 'pay_type_2_node_no_2');     //资金专员审核
define('AUDIT_NODE_PAY_TYPE_2_NO_3', 'pay_type_2_node_no_3');     //权签人审核

define('REDEM_AUDIT_FLOW_CODE', 'redemption_audit_code_1_node');   //赎回审核流程
define('REDEM_AUDIT_NODE_NO_1', 'redemption_audit_node_no_1');     //发起节点
define('REDEM_AUDIT_NODE_NO_2', 'redemption_audit_node_no_2');     //赎回审核节点
define('REDEM_AUDIT_NODE_NO_4', 'redemption_audit_node_no_4');     //赎回流程资金专员上传回单（网银打款）

define('FINANCIAL_EXCHANGE_NAME', 'logic.gb.direct.money.financial');
define('FINANCIAL_ROUT_CREATE', 'financial_plan.create');
define('FINANCIAL_ROUT_AUDIT', 'financial_plan.audit');
define('FINANCIAL_ROUT_REDEMPTION_AUDIT', 'financial_plan.redemption.audit');