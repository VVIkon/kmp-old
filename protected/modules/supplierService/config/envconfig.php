<?php

return [
    'authdata' => [
        'login' => 'testcl',
        'pass' => '123'
    ],
    'services' => [
        'orderService' => [
            'serverURL' => 'http://kmp-travel',
            'baseURL' => '/api/OrdersService/'
        ],
        'supplierService' => [
            'baseURL' => '/api/SupplierService/'
        ]
    ],
    'gearman' => [
        'host' => '127.0.0.1',
        'port' => '4730',
        'workerPrefix' => 'dev'
    ]
];
