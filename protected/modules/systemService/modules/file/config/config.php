<?php

//Подключение настроек зависящих от среды выполнения
$environmentConfig = require (dirname(__FILE__).'/envconfig.php');

return CMap::mergeArray($environmentConfig, [
    'error_descriptions' => [
        'undefined_error' => 'Неизвестная ошибка',
        '1' => 'Неправильный формат JSON тела запроса',
        '2' => 'Неверный логин пользователя',
        '3' => 'Неверный пароль пользователя',
        '4' => 'Неверный ключ пользователя',
        '5' => 'Неверно указан CONTENT_TYPE',
        '6' => 'Неверный идентификатор пользователя',
    ],
]);
