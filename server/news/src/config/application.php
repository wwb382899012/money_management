<?php
define('FINANCIAL_EXCHANGE_NAME', 'logic.gb.direct.money.financial');
define('FINANCIAL_ROUT_CREATE', 'financial_plan.create');
define('FINANCIAL_ROUT_AUDIT', 'financial_plan.audit');
define('FINANCIAL_ROUT_REDEMPTION_AUDIT', 'financial_plan.redemption.audit');

define('LOAN_EXCHANGE_NAME', 'logic.gb.direct.money.loan');
define('LOAN_ROUT_AUDIT', 'loan_order.audit');
define('LOAN_ROUT_AUDIT_TRANSFER', 'loan_transfer.audit');

define('REPAY_ROUT_AUDIT_CASH_FLOW', 'repay_cash_flow.audit');

define('ORDER_EXCHANGE_NAME', 'logic.gb.direct.money.pay');
define('ORDER_ROUT_AUDIT', 'pay_order.audit');
define('ORDER_ROUT_AUDIT_TRANSFER', 'pay_transfer.audit');

define('INNER_EXCHANGE_NAME', 'logic.gb.direct.money.inner');
define('INNER_ROUT_AUDIT', 'inner_transfer.audit');

define('NEWS_QUEUE', 'logic.gd.money.news');