<?php

/**
 * Модуль содержащий набор системных команд
 * Class SystemServiceModule
 */
class SystemServiceModule extends KModule
{
	public function init()
	{

		$this->setImport(array(
			'systemService.models.*',
            'systemService.models.Entity.*',
            'systemService.models.Repository.*',
            'systemService.models.Collection.*',
            'systemService.models.ChatActionType.*',
            'systemService.models.ChatConnectionDecorator.*',
            'systemService.models.NotificationGate.*',

			'systemService.components.*',
            'systemService.controllers.*',
			'systemService.modules.file.components.*',
			'systemService.modules.dictionary.components.*',
			'systemService.modules.dictionary.components.validators.*',
			'systemService.modules.dictionary.models.handlers.*',
			'systemService.modules.dictionary.models.*',
            'systemService.modules.schedule.*',
            'systemService.modules.schedule.components.*',
		));
	}

	public function beforeControllerAction($controller, $action)
	{
		if(parent::beforeControllerAction($controller, $action))
		{
			// this method is called before any module controller action is performed
			// you may place customized code here
			return true;
		}
		else
			return false;
	}

	/**
	 * Получение названия контекста по классу и методу
	 * @param string className имя класса
	 * @param string methodName имя метода
	 * @return mixed значение указанного параметра
	 */
	/*public function getCxtName($className, $methodName) {

		$contexts = require(dirname(__FILE__) . '/config/config.php');

		return empty($contexts['context_descriptions'][$className][$methodName])
			? $className . ' ' . $methodName
			: $contexts['context_descriptions'][$className][$methodName];
	}*/

	/**
	 * Получение названия контекста по классу и методу
	 * @param string className имя класса
	 * @param string methodName имя метода
	 * @return mixed значение указанного параметра
	 */
	/*public function getError($errorCode) {

		$contexts = require(dirname(__FILE__) . '/config/config.php');

		return empty($contexts['errors_descriptions'][$errorCode])
			? $contexts['errors_descriptions']['undefined_error']
			: $contexts['errors_descriptions'][$errorCode];
	}*/

	/**
	 * Получение параметров конфигурации модуля
	 * @param string $paramName имя параметра
	 * @return mixed значение указанного параметра
	 */
	/*public function getConfig($paramName = '') {
		$config = require(dirname(__FILE__) . '/config/config.php');
		return empty($paramName) ? $config : $config[$paramName];
	}*/
}
