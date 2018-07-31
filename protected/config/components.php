<?php
return [
    'request' => [
        // 'enableCsrfValidation'=>true,
    ],

    'session' => [
      'autoStart' => true
    ],

    'user' => [
        //'allowAutoLogin' => true,
        'loginUrl' => '/',
    ],

    'urlManager' => [
        'showScriptName' => false,
        'urlFormat' => 'path',
        //'useStrictParsing' => true,
        'rules' => require(dirname(__FILE__) . '/rules.php'),
    ],

    'db' => require(dirname(__FILE__) . '/db.php'),

    'cache' => [
        'class' => 'system.caching.CFileCache',
    ],

    'errorHandler'=>array(
        'errorAction'=>'main/error',
    ),

    'log' => array(
        'class' => 'CLogRouter',
        'routes' => array(
            // REST request logs
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace, info, error, warning, profile',
                'categories' => 'system.rest.*',
                'logPath' => dirname(__FILE__) . '/../logs',
                'logFile' => 'requests.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 1
            ),
            // Реквест логи БД
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace, profile, info, warning, error',
                'categories' => 'system.db.*',
                'logPath' => dirname(__FILE__) . '/../logs',
                'logFile' => 'sql_queries.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),

            /**
             *  ORDER SERVICE
             */

            // запросы к сервису заявок
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace',
                'categories' => 'system.orderservice.*',
                'logPath' => dirname(__FILE__) . '/../logs/ordersvc',
                'logFile' => 'ordersvcRequests.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 5
            ),
            // info
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'info',
                'categories' => 'system.orderservice.info',
                'logPath' => dirname(__FILE__) . '/../logs/ordersvc',
                'logFile' => 'orderInfo.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
            // warning
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'warning',
                'categories' => 'system.orderservice.warning',
                'logPath' => dirname(__FILE__) . '/../logs/ordersvc',
                'logFile' => 'orderWarning.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
            // error
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'error',
                'categories' => 'system.orderservice.error',
                'logPath' => dirname(__FILE__) . '/../logs/ordersvc',
                'logFile' => 'orderError.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
            // utk requests
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'info, error',
                'categories' => 'system.orderservice.utkrequests',
                'logPath' => dirname(__FILE__) . '/../logs/ordersvc',
                'logFile' => 'ordersvcUtkRequests.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),

            /**
             *  SEARCHER SERVICE
             */

            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace, info, error, warning',
                'categories' => 'system.searcherservice.errors',
                'logPath' => dirname(__FILE__) . '/../logs/searchersvc',
                'logFile' => 'searchersvcErrors.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace, info',
                'categories' => 'system.searcherservice.*',
                'logPath' => dirname(__FILE__) . '/../logs/searchersvc',
                'logFile' => 'searchersvcRequests.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace, info, error, warning',
                'categories' => 'system.searcherservice.gearman.client.*',
                'logPath' => dirname(__FILE__) . '/../logs/searchersvc',
                'logFile' => 'searchersvcGearmanClientRequests.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace, info, error, warning',
                'categories' => 'system.searcherservice.gearman.worker.*',
                'logPath' => dirname(__FILE__) . '/../logs/searchersvc',
                'logFile' => 'searchersvcGearmanWorker.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace, info, error, warning',
                'categories' => 'system.searcherservice.perfomance.*',
                'logPath' => dirname(__FILE__) . '/../logs/searchersvc',
                'logFile' => 'searchersvcPerfomance.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),

            /**
             *  SUPPLIER SERVICE
             */

            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace, info',
                'categories' => 'system.supplierservice.*',
                'logPath' => dirname(__FILE__) . '/../logs/suppliersvc',
                'logFile' => 'suppliersvcRequests.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'error, warning',
                'categories' => 'system.supplierservice.*',
                'logPath' => dirname(__FILE__) . '/../logs/suppliersvc',
                'logFile' => 'suppliersvcErrors.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),

            /**
             *  SYSTEM SERVICE
             */

            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace, info, error, warning',
                'categories' => 'system.systemservice.errors',
                'logPath' => dirname(__FILE__) . '/../logs/systemsvc',
                'logFile' => 'systemsvcErrors.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace, info, error, warning',
                'categories' => 'system.systemservice.*',
                'logPath' => dirname(__FILE__) . '/../logs/systemsvc',
                'logFile' => 'systemsvcRequests.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
            /**
             *  API SERVICE
             */

            array(
                'class' => 'CFileLogRoute',
                'levels' => 'trace, info',
                'categories' => 'system.apiservice.*',
                'logPath' => dirname(__FILE__) . '/../logs/apisvc',
                'logFile' => 'apiRequests.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
            array(
                'class' => 'CFileLogRoute',
                'levels' => 'error, warning',
                'categories' => 'system.apiservice.*',
                'logPath' => dirname(__FILE__) . '/../logs/apisvc',
                'logFile' => 'apiErrors.log',
                'rotateByCopy' => true,
                'maxFileSize' => 10024,
                'maxLogFiles' => 2
            ),
        ),
    ),
];
