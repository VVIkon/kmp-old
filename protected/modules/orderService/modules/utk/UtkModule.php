<?php

class UtkModule extends KModule
{
	public function init()
	{
		// this method is called when the module is being created
		// you may place code here to customize the module or the application

		// import the module-level models and components
		$this->setImport(array(
			'utk.models.*',
			'utk.models.Listeners.*',
			'utk.components.*',
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
