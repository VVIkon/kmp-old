<?php
//Подключение настроек зависящих от среды выполнения
$environmentConfig = require (dirname(__FILE__).'/envconfig.php');

return CMap::mergeArray($environmentConfig, [
        'authdata' => [
            'login' => 'testcl',
            'pass' => '123'
        ]
]);
