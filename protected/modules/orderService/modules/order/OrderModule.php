<?php

class OrderModule extends KModule
{
	public function init()
	{
		$this->setImport(array(
			'order.models.*',
			'order.models.services.*',
			'order.models.offers.*',
			'order.models.tickets.*',
			'order.components.*',
		));
	}



}
