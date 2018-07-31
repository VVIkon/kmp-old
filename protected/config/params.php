<?php

/**
 * Параметры приложения
 *
 * Доступ: <?= Yii::app()->params['adminEmail']; ?>   // _email_
 */


return [
    'adminEmail' => '_email_',

    'testEmail' => 'mfc.consult@mail.ru',// заглушка для теста отправки емэйл

    'emailFrom' => 'kmp@dev.kmp.travel',// заглушка для теста отправки емэйл о бронировании

    'smtp' => [
        'charset' => 'utf-8',
        'host' => 'ssl://smtp.yandex.ru',
        'port' => 465,
        'username' => 'kmp-sen',
        'password' => 'dev-kmp',
    ],

//    'gearman'   => [
//        'host' => '127.0.0.1',
//        'port' => '4730',
//        'workerPrefix' => 'dev'
//    ]

    'servicePhone' => [
        'phone' => '8 495 721 17 07',
        'title' => 'Служба поддержки',
    ],
    'photoUrl' => '/images/photos/',
    'flagsUrl' => 'images/icons/flags/',

    'photoPath' => 'webroot.images.photos',

    'fixDays' => [
        'startMayDay' => ['30', '4'],
        'endMayDay' => ['9', '5'],
        'startNY' => ['25', '12'],
        'endNY' => ['7', '1'],
    ],

    'errors' => [
        // ошибки
    ],

];
