<?php

//Подключение настроек зависящих от среды выполнения
$environmentConfig = require(dirname(__FILE__) . '/envconfig.php');

return CMap::mergeArray($environmentConfig, [
    'log_namespace' => "system.searcherservice",
    // массив описания контекстов
    'context_descriptions' => [
        'undefined_context' => 'Неизвестный контекст',
        '' => [],
        '' => []
    ],
    'errors_descriptions' => [
        'undefined_error' => 'Неизвестная ошибка',
        '1' => 'Неправильный формат JSON тела запроса',
        '2' => 'Неверный логин пользователя',
        '3' => 'Неверный пароль пользователя',
        '4' => 'Неверный ключ пользователя',
        '5' => 'Неверно указан CONTENT_TYPE',
    ],
    //Коды поставщиков для шлюза GPTS
    'suppliers_gpts_codes' => [
        'sirena'        => 243,
        'amadeus'       => 214
    ]

]);