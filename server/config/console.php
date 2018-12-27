<?php
/**
 * 执行php server/bin/console make:console生成此配置文件
 */

return [
    'account:balance-async' => function () { return new money\command\AccountBalanceSyncCommand(); },
    'account:bank-import' => function () { return new money\command\AccountBankImportCommand(); },
    'account:notify' => function () { return new money\command\AccountNotifyCommand(); },
    'outMainBody:auth' => function () { return new money\command\OutMainBodyAuthCommand(); },
    'app:test' => function () { return new money\command\AppTestCommand(); },
    'news:daily-msg' => function () { return new money\command\DailyNewsSendCommand(); },
    'financial:redemption' => function () { return new money\command\FinancialRedemptionCommand(); },
    'make:console' => function () { return new money\command\MakeConsoleCommand(); },
    'news:add-msg' => function () { return new money\command\NewsAddMsgCommand(); },
    'news:send-email' => function () { return new money\command\NewsSendEmailCommand(); },
    'oa:config-init' => function () { return new money\command\OaConfigInitCommand(); },
    'oa:loan-order-call' => function () { return new money\command\OaLoanOrderCallCommand(); },
    'oa:loan-order-notify' => function () { return new money\command\OaLoanOrderNotifyCommand(); },
    'oa:pay-order-call' => function () { return new money\command\OaPayOrderCallCommand(); },
    'oa:pay-order-notify' => function () { return new money\command\OaPayOrderNotifyCommand(); },
    'oa:repay-order-call' => function () { return new money\command\OaRepayOrderCallCommand(); },
    'oa:repay-order-notify' => function () { return new money\command\OaRepayOrderNotifyCommand(); },
    'oa:order-timeout' => function () { return new money\command\OaOrderTimeoutCommand(); },
    'reports:eod' => function () { return new money\command\ReportsEodCommand(); },
    'reports:full' => function () { return new money\command\ReportsFullCommand(); },
    'server' => function () { return new money\command\ServerCommand(); },
    'user:admin-enable' => function () { return new money\command\UserAdminEnableCommand(); },
    'user:list-sync' => function () { return new money\command\UserListSyncCommand(); },
];
