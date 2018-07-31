<?php

class DictionaryModule extends KModule
{
	public function init()
	{
		$this->setImport(array(
			'dictionary.models.*',
			'dictionary.models.handlers.*',
			'dictionary.components.*',
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
}
