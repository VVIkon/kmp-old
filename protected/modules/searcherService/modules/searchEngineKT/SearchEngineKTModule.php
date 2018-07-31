<?php

class SearchEngineKTModule extends CWebModule
{
	public function init()
	{
		// this method is called when the module is being created
		// you may place code here to customize the module or the application

		// import the module-level models and components
		$this->setImport(array(
			'searchEngineKT.models.*',
			'searchEngineKT.components.*',
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
	public function getCxtName($className, $methodName) {

		$contexts = require(dirname(__FILE__) . '/config/config.php');

		return empty($contexts['context_descriptions'][$className][$methodName])
			? $className . ' ' . $methodName
			: $contexts['context_descriptions'][$className][$methodName];
	}

	/**
	 * Получение описания ошибки по её коду
	 * @param string className имя класса
	 * @param string methodName имя метода
	 * @return mixed значение указанного параметра
	 */
	public function getError($errorCode) {

		$contexts = require(dirname(__FILE__) . '/config/config.php');

		return empty($contexts['error_descriptions'][$errorCode])
			? $contexts['error_descriptions']['undefined_error'] . ' ' . $errorCode
			: $contexts['error_descriptions'][$errorCode];
	}

	/**
	 * Получение параметров конфигурации модуля
	 * @param string $paramName имя параметра
	 * @return mixed значение указанного параметра
	 */
	public function getConfig($paramName = '') {
		$config = require(dirname(__FILE__) . '/config/config.php');
		return empty($paramName) ? $config : $config[$paramName];
	}

	/**
	 * Вызов конструктора компонента при обращении через модуль
	 * @param string $name
	 * @param array $args
	 * @return object
	 */
	public function __call($name, $args)
	{
		$reflect  = new ReflectionClass($name);
		$instance = $reflect->newInstanceArgs($args);
		return $instance;
	}
}
