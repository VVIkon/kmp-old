<?php

class AccountModule extends CWebModule
{
	public function init()
	{
		$this->setImport(array(
			'account.components.*',
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
