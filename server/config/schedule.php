<?php
/**
 * 计划任务
 */

return [
    'account' => [
        [
            'command' => 'php server/bin/console account:balance-async',
            'schedule' => '*/30 * * * *',
            'enabled' => true,
        ],
        [
            'command' => 'php server/bin/console account:notify',
            'schedule' => '* * * * *',
            'enabled' => true,
        ],
        [
            'command' => 'php server/bin/console outMainBody:auth',
            'schedule' => '* * * * *',
            'enabled' => true,
        ],
    ],
    'financial' => [
        [
            'command' => 'php server/bin/console financial:redemption',
            'schedule' => '0 0 * * *',
            'enabled' => true,
        ],
    ],
    'loan' => [
        [
            'command' => 'php server/bin/console oa:loan-order-call',
            'schedule' => '*/5 * * * *',
            'enabled' => true,
        ],
        [
            'command' => 'php server/bin/console oa:loan-order-notify',
            'schedule' => '*/5 * * * *',
            'enabled' => true,
        ],
        [
            'command' => 'php server/bin/console oa:repay-order-call',
            'schedule' => '*/5 * * * *',
            'enabled' => true,
        ],
        [
            'command' => 'php server/bin/console oa:repay-order-notify',
            'schedule' => '*/5 * * * *',
            'enabled' => true,
        ],
        [
            'command' => 'php server/bin/console oa:order-timeout',
            'schedule' => '*/10 * * * *',
            'enabled' => true,
        ],
    ],
    'news' => [
        [
            'command' => 'php server/bin/console news:send-email',
            'schedule' => '*/5 * * * *',
            'enabled' => true,
        ],
        [
            'command' => 'php server/bin/console news:add-msg',
            'schedule' => '* * * * *',
            'enabled' => true,
        ],
        [
	        'command' => 'php server/bin/console news:daily-msg',
	        'schedule' => '01 1 * * *',
	        'enabled' => true,
        ],
    ],
    'order' => [
        [
            'command' => 'php server/bin/console oa:pay-order-call',
            'schedule' => '*/5 * * * *',
            'enabled' => true,
        ],
       [
            'command' => 'php server/bin/console oa:pay-order-notify',
            'schedule' => '*/5 * * * *',
            'enabled' => true,
        ],
    ],
    'pay' => [

    ],
    'user' => [
        [
            'command' => 'php server/bin/console user:list-sync',
            'schedule' => '*/5 * * * *',
            'enabled' => true,
        ],
    ],
];
