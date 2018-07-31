<?php

return [
    'authdata' => [
        'login' => 'testcl',
        'pass' => '123'
    ],
    'gearman' => [
        'host' => 'https://dev.kmp.travel',
        'port' => '4730',
        'workerPrefix' => 'dev'
    ],
    'tempStorage' => [
        'tmpPath' => Yii::app()->basePath.'/../temp',
        'ftpPath' => 'ftp://dev.kmp.travel'
    ]
];
