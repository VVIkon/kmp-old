<?php
$configRoot = dirname(__FILE__);
$configLocal = file_exists($configRoot . '/main-local.php') ? require($configRoot . '/main-local.php') : array();
$basePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..';

require_once($basePath . '/helpers/global.php');

$params = require($configRoot . '/params.php');
$aliases = require($configRoot . '/aliases.php');
$import = require($configRoot . '/import.php');
$modules = require($configRoot . '/modules.php');
$components = require($configRoot . '/components.php');
$credentials = require($configRoot . '/credentials.php');
$environment = require($configRoot . '/environment.php');
$serviceRoutes = require($configRoot . '/service_routes.php');

return CMap::mergeArray(array(
    'basePath' => $basePath,
    'name' => '.Backup',
    'language' => 'ru',
//  'localeDataPath' => $basePath . '/i18n/data/',
    'aliases' => $aliases,
    'params' => CMap::mergeArray($params, $credentials, $environment, $serviceRoutes),
    'import' => $import,
    'modules' => $modules,
    'components' => $components,
    'preload' => array(
        'log',
        'debug'
    ),
//  'behaviors' => array('DModuleUrlRulesBehavior'),
), $configLocal);
