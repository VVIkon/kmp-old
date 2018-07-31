<?php

return [
    'provider' => [
        'test_api' => [
            'url' => 'https://kmp.travel/gptour-test/api/',
            'actions' => [
                'authorize' => 'authorization',
                GptsRequestsFactory::FLIGHT_OFFER_TYPE => 'searchFlight',
                GptsRequestsFactory::ACCOMMODATION_OFFER_TYPE => 'searchAccommodation',
                'searchSchedule' => 'searchFlightTimeTable',
                'additionalOptions' => 'additionalOptions'
            ],
            'authInfo' => [
                'key' => 'tFsVPUmkosfraqRI7f1BFTElOL3yiP',
                'login' => 's.kamenev',
                'password' => 'JkauDev'
            ]
        ],
        'prod_api' => [
            /*'url' => 'https://kmp.travel/gptour/api/',*/
            'url' => '1',
            'actions' => [
                'authorize' => 'authorization',
                GptsRequestsFactory::FLIGHT_OFFER_TYPE => 'searchFlight',
                GptsRequestsFactory::ACCOMMODATION_OFFER_TYPE => 'searchAccommodation',
                'searchSchedule' => 'searchFlightTimeTable'
            ],
            'authInfo' => [
                'key' => 'UTnWLyhu1eUzC0qOoNFHi3DBcVb8Sw',
                'login' => 'test141015',
                'password' => 'test12345'
            ]
        ]
    ],
    //Поставщики по типам предложений
    'suppliers' => [
        1 => [ //тип услуги размещение
            3, 4,/*5,*/
            6, 7, 8, 9, 10, 11, 12, 13, 14 //идентификаторы поставщиков в КТ
        ],
        2 => [ //тип услуги перелёт
            1, 2 //идентификаторы поставщиков в КТ
        ]
    ]


];
