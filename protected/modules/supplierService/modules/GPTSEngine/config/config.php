<?php

//Подключение настроек зависящих от среды выполнения
$environmentConfig = require (dirname(__FILE__).'/envconfig.php');

//Подключение конифгурации правил парсинга авиатарифов
$flightFaresParsingRules = require (dirname(__FILE__).'/flightFaresParsingRules.php');

return CMap::mergeArray($environmentConfig, $flightFaresParsingRules, [
    'log_namespace' => "system.supplierservice"
]);