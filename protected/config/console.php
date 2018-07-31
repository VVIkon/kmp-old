<?php

$dir = dirname(__FILE__);

require_once($dir . '/../helpers/global.php');
$params = require($dir . '/params.php');
$credentials = require($dir . '/credentials.php');
$environment = require($dir . '/environment.php');
$serviceRoutes = require($dir . '/service_routes.php');

return [
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'KMP Travel Console App',

    'aliases' => require_once($dir . '/aliases.php'),
    'params' => CMap::mergeArray($params, $credentials, $environment, $serviceRoutes),
    'import' => require_once($dir . '/import.php'),
    'modules' => require_once($dir . '/modules.php'),
    'components' => require_once($dir . '/components.php'),
    'preload' => array(
        'log',
        'debug',
    ),
//    'components' => [
//        'db' => require(dirname(__FILE__).'/db.php'),
//    ],
    'commandMap' => array(
        // ORDER SERVICE
        'DelegateWorker' => array(
            'class' => 'application.modules.orderService.commands.GearmanDelegateWorker',
        ),
        'ServiceCheckStatus' => array(
            'class' => 'application.modules.orderService.commands.ServiceCheckStatusCommand',
        ),
        'Report' => array(
            'class' => 'application.modules.orderService.commands.ReportCommand',
        ),

        // SYSTEM SERVICE
        'ServiceToken' => array(
            'class' => 'application.modules.systemService.commands.ServiceTokenCommand',
        ),
        'AsyncTask' => array(
            'class' => 'application.modules.systemService.commands.AsyncTaskCommand',
        ),
        'FTPTest' => array(
            'class' => 'application.modules.systemService.commands.FTPTestCommand',
        ),
        'Chat' => array(
            'class' => 'application.modules.systemService.commands.ChatCommand',
        ),
        'Notification' => array(
            'class' => 'application.modules.systemService.commands.NotificationCommand',
        ),
        'ScheduleManage' => array(
            'class' => 'application.modules.systemService.commands.ScheduleManageCommand',
        ),


        // SUPPLIER SERVICE
        'GPTSBookingPoll' => array(
            'class' => 'application.modules.supplierService.modules.GPTSEngine.commands.GPTSBookingPoll',
        ),
        'Dictionary' => array(
            'class' => 'application.modules.supplierService.modules.Dictionaries.commands.Dictionary',
        ),
        'LaunchDictionaryWorkers' => array(
            'class' => 'application.modules.supplierService.modules.Dictionaries.commands.DictionaryWorkersLauncher'
        ),

        // SEARCHER SERVICE
        'runOfferSearch' => array(
            'class' => 'application.modules.searcherService.commands.RunOfferSearch',
        ),
        'runAviaOfferSearch' => array(
            'class' => 'application.modules.searcherService.commands.RunAviaOfferSearch',
        ),
        'runAccommodationOfferSearch' => array(
            'class' => 'application.modules.searcherService.commands.RunAccommodationOfferSearch',
        ),
        'CacheClear' => array(
            'class' => 'application.modules.searcherService.commands.RunCacheClear',
        ),
    ),
];
