<?php

return [
    'test_api' => [
        'url' => 'https://kmp.travel/gptour-test/api/',
        'authInfo' => [
            'key' => 'tFsVPUmkosfraqRI7f1BFTElOL3yiP',
            'login' => 's.kamenev',
            'password' => 'JkauDev'
        ],
        'paymentMethodId' => 4663588
    ],
    'prod_api' => [
        //'url' => 'https://kmp.travel/gptour/api/',
        'url' => '1',
        'authInfo' => [
            'key' => 'UTnWLyhu1eUzC0qOoNFHi3DBcVb8Sw',
            'login' => 'test141015',
            'password' => 'test12345'
        ],
        'paymentMethodId' => 4663588 // ? stub, check value
    ],
    'bookingPollAttempts' => 10,        // число попыток между запросами на бронирование
    'bookingPollAttemptTime' => 15      // время между попытками, с
];
