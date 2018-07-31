<?php

class SearchEngineGPTSModule extends CWebModule
{
	public function init()
	{
		$this->setImport(array(
			'searchEngineGPTS.models.*',
			'searchEngineGPTS.models.gptsRequests.*',
			'searchEngineGPTS.models.gptsResponses.*',
			'searchEngineGPTS.models.Listeners.*',
			'searchEngineGPTS.components.*',
		));
	}

	public function beforeControllerAction($controller, $action)
	{
		if(parent::beforeControllerAction($controller, $action))
		{
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
	 * Получение названия контекста по классу и методу
	 * @param string className имя класса
	 * @param string methodName имя метода
	 * @return mixed значение указанного параметра
	 */
	public function getError($errorCode) {

		$contexts = require(dirname(__FILE__) . '/config/config.php');

		return empty($contexts['errors_descriptions'][$errorCode])
			? $contexts['errors_descriptions']['undefined_error']
			: $contexts['errors_descriptions'][$errorCode];
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
}
