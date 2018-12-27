<?php
define('MQ_EXCHANGE_ACCOUNT', 'logic.gb.direct.money.account');
define('MQ_ROUT_ACCOUNT_NOTIFY', 'account.notify');
define('MQ_QUEUE_ACCOUNT_NOTIFY', 'logic.gd.money.account.notify');

define('MQ_EXCHANGE_MAINBODY', 'logic.gb.direct.money.mainbody');
define('MQ_ROUT_MAINBODY_ADD', 'mainbody.add');
define('MQ_QUEUE_MAINBODY_ADD_AUTOAUTH', 'logic.gd.money.mainbody.autoauth');

define('MQ_DELAY_ROUT_ACCOUNT_NOTIFY', 'account.notify.delay');
define('MQ_DELAY_QUEUE_ACCOUNT_NOTIFY', 'logic.gd.money.account.notify.delay');
define('MQ_MESSAGE_TTL_ACCOUNT_NOTIFY', 1800000);//单位毫秒