<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => SERVER_PATH,
        ],

        'cosv5' => [
            'driver'         => 'cosv5',
            'region'         => defined('COSV5_REGION') ? COSV5_REGION : 'ap-shanghai',
            'credentials'    => [
                'appId'      => defined('COSV5_APP_ID') ? COSV5_APP_ID : '',
                'secretId'   => defined('COSV5_SECRET_ID') ? COSV5_SECRET_ID : '',
                'secretKey'  => defined('COSV5_SECRET_KEY') ? COSV5_SECRET_KEY : '',
                'token'      => defined('COSV5_TOKEN') ? COSV5_TOKEN : null,
            ],
            'timeout'            => defined('COSV5_TIMEOUT') ? COSV5_TIMEOUT : 1800,
            'connect_timeout'    => defined('COSV5_CONNECT_TIMEOUT') ? COSV5_CONNECT_TIMEOUT : 60,
            'bucket'             => defined('COSV5_BUCKET') ? COSV5_BUCKET : 'money-management',
            'cdn'                => defined('COSV5_CDN') ? COSV5_CDN : '',
            'scheme'             => defined('COSV5_SCHEME') ? COSV5_SCHEME : 'https',
            'read_from_cdn'      => defined('COSV5_READ_FROM_CDN') ? COSV5_READ_FROM_CDN : false,
        ],

    ],

];
