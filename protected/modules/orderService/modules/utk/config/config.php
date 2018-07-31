<?php

//Подключение настроек зависящих от среды выполнения
$environmentConfig = require (dirname(__FILE__).'/envconfig.php');

return CMap::mergeArray($environmentConfig, [
    'log_namespace' => "system.orderservice.utk",
    'error_descriptions' => [
        'undefined_error' => 'Неизвестная ошибка',
        '1' => 'Неправильный формат JSON тела запроса',
        '2' => 'Неверный логин пользователя'
    ],
]);
