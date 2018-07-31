<?php

return [
    'gearman' => [
        'host' => '127.0.0.1',
        'port' => '4730',
        'workerPrefix' => 'dev'
    ],

    'authdata' => [
        'login' => 'testcl',
        'pass' => '123'
    ],
    'services' => [
        'orderService' => [
            'serverURL' => 'http://kmp-travel',
            'baseURL' => '/api/OrdersService/'
        ]
    ],
    'tokenLifetime' => 86400,       // время жизни сервисного токена, с
    'chat' => [
        'port' => 8100,
        'deactivationTime' => 60,
        'userGroups' => array(
            'operators' => [],
            'support' => [4710, 4659]
        ),
        'ssl' => null,
//        'ssl' => [
//            'local_cert' => '/your/path/of/server.pem',
//            'local_pk'   => '/your/path/of/server.key'
//        ],
        'historyMessagesNumber' => 5
    ],

    'templatesBasePath' => 'notifications/templates',

    'token_expiration_seconds' => '14400',
    'change_if_expired' => true
];
