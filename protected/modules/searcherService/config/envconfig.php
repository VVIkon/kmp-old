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
    'gateways' => [
        'engineGPTS' => [
            'namespace' => '/modules/searchEngineGPTS/components',
            'class'     => 'gptsSearch',
            'firstOffersPortion'    => 8,  //количество предложений при первом поиске
            'maxOffersPortion'      => 255 //максимальное количество предложений получаемое от провайдера
        ]
    ],
    'cacheClear' => 'PT2H',   // в формате DateInterval 15 минут по-умолчанию
    'expireAviaSchedule' => 86400,  // время жизни кеша расписания запрошенных авиаперелётов, с

];