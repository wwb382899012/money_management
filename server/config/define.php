<?php
/**
 * Created by PhpStorm.
 * User: xupengpeng
 * Date: 2018/9/29
 * Time: 14:56
 */

return [
    'SERVER_PATH' => dirname(__DIR__).DIRECTORY_SEPARATOR,
    /**
     * 用户中心配置
     */
    'USER_CLIENT_ID' => 'money',
    'USER_SECRET' => '086883962a101a4d9f6da1c1d10c6385',

    /**
     * 会话配置
     */
    'SESSION_PRE' => 'session_money_',
    'SESSION_EXPIRE' => 7200,
    /**
     * MQ消息队列配置
     */
    'MQ_EXCHANGE_ACCOUNT' => 'logic.gb.direct.money.account',
    'MQ_ROUT_ACCOUNT_NOTIFY' => 'account.notify',
    'MQ_QUEUE_ACCOUNT_NOTIFY' => 'logic.gd.money.account.notify',
    'MQ_DELAY_ROUT_ACCOUNT_NOTIFY' => 'account.notify.delay',
    'MQ_DELAY_QUEUE_ACCOUNT_NOTIFY' => 'logic.gd.money.account.notify.delay',
    'MQ_MESSAGE_TTL_ACCOUNT_NOTIFY' => 1800000,//单位毫秒

    'MQ_EXCHANGE_MAINBODY' => 'logic.gb.direct.money.mainbody',
    'MQ_ROUT_MAINBODY_ADD' => 'mainbody.add',
    'MQ_QUEUE_MAINBODY_ADD_AUTOAUTH' => 'logic.gd.money.mainbody.autoauth',

    'FINANCIAL_EXCHANGE_NAME' => 'logic.gb.direct.money.financial',
    'FINANCIAL_ROUT_CREATE' => 'financial_plan.create',
    'FINANCIAL_ROUT_AUDIT' => 'financial_plan.audit',
    'FINANCIAL_ROUT_REDEMPTION_AUDIT' => 'financial_plan.redemption.audit',
    'NEWS_QUEUE' => 'logic.gd.money.news',
    
    'LOAN_EXCHANGE_NAME' => 'logic.gb.direct.money.loan',
    'LOAN_ROUT_AUDIT' => 'loan_order.audit',
    'LOAN_ROUT_AUDIT_TRANSFER' => 'loan_transfer.audit',
    'REPAY_ROUT_AUDIT' => 'repay_order.audit',
    'REPAY_ROUT_AUDIT_TRANSFER' => 'repay_transfer.audit',
    'REPAY_ROUT_AUDIT_CASH_FLOW' => 'repay_cash_flow.audit',
    
    'ORDER_EXCHANGE_NAME' => 'logic.gb.direct.money.pay',
    'ORDER_ROUT_AUDIT' => 'pay_order.audit',
    'ORDER_ROUT_AUDIT_TRANSFER' => 'pay_transfer.audit',
    'ORDER_RESULT_LISTENER'  => 'order.result.listener',
    'ORDER_RESULT_EXCHANGE_NAME' => 'logic.gb.direct.money.order.result',

    'INNER_EXCHANGE_NAME' => 'logic.gb.direct.money.inner',
    'INNER_ROUT_AUDIT' => 'inner_transfer.audit',
];
