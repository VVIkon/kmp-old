<?php

$modulesRoutes = [];

$modulesRoutes['OrderServiceRoutes'] = require (dirname(__FILE__).'/../modules/orderService/config/routes.php');
$modulesRoutes['SystemServiceRoutes'] = require (dirname(__FILE__).'/../modules/systemService/config/routes.php');
$modulesRoutes['SearcherServiceRoutes'] = require (dirname(__FILE__).'/../modules/searcherService/config/routes.php');
$modulesRoutes['SupplierServiceRoutes'] = require (dirname(__FILE__).'/../modules/supplierService/config/routes.php');
$modulesRoutes['ApiServiceRoutes']      = require (dirname(__FILE__).'/../modules/apiService/config/routes.php');

$routes = [];

foreach ($modulesRoutes as $moduleRoutes) {
  $routes = CMap::mergeArray($routes,$moduleRoutes);
}

return CMap::mergeArray($routes, [

  'login'                               => 'cabinetUI/user/login',
  'logout'                              => 'cabinetUI/user/logout',

  'cabinetUI/orders/order/<id:\d+>'     => 'cabinetUI/orders/order',
  'cabinetUI/orders/order/new'          => 'cabinetUI/orders/order',

  'cabinetUI/admin'                     => 'cabinetUI/clientAdmin/index',
  'cabinetUI/admin/<action:\w+>'        => 'cabinetUI/clientAdmin/<action>',

  'cabinetUI/reports'                   => 'cabinetUI/reports/index',
  'cabinetUI/reports/<action:\w+>'      => 'cabinetUI/reports/<action>',

  'messenger'                           => 'UI/core/messenger', 

  'adminUI'                             => 'adminUI/admin/index',
  'adminUI/admin/<action:\w+>'          => 'adminUI/admin/<action>',
  'adminUI/<applink:.+>'               => 'adminUI/admin/index',

  //-----------------------------------------Не выкладывать на prod
  //'gii'=>'gii',
  //'gii/<controller:\w+>'=>'gii/<controller>',
  //'gii/<controller:\w+>/<action:\w+>'=>'gii/<controller>/<action>',
  //---------------------------------------------------------------

  // base controller
  '/'                                                   => 'main/index',
  '/<action:\w+>'                                       => 'main/<action>',
  '/<action:\w+>/<id:\d+>'                              => 'main/<action>',
  // default
  //    '<controller:\w+>/<id:\d+>'                             => '<controller>/view',
  //'<controller:\w+>/<action:\w+>/<id:\d+>'                => '<controller>/<action>',
  //'<controller:\w+>/<action:\w+>'                         => '<controller>/<action>',
  // module
  //'<module:\w+>/<controller:\w+>/<action:\w+>/<id:\d+>' => '<module>/<controller>/<action>',
  //tour
  //'tour/view/<tour_id:\d+>/*'                           => 'tour/tour/view/',
]);
