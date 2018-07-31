<?php

class FileModule extends KModule
{
	public function init()
	{
		$this->setImport(array(
			'file.models.*',
			'file.components.*',
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
