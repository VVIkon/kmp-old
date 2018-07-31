<?php

class SearcherServiceModule extends KModule
{
	public function init()
	{
		$this->setImport(array(
			'searcherService.models.*',
			'searcherService.models.offerFinders.*',
			'searcherService.models.searchRequests.*',
			'searcherService.models.searchHandlers.*',
			'searcherService.models.responseHandlers.*',
			'searcherService.models.offers.*',
            'searcherService.models.Entity.*',
            'searcherService.models.Entity.Hotel.*',
            'searcherService.models.Entity.Avia.*',
            'searcherService.models.Repository.*',

			'searcherService.components.*',
			'searcherService.components.offerValidators.*',
			'searcherService.components.workers.*',
			'searcherService.modules.searchSuggests.models.*',
			'searcherService.modules.searchSuggests.components.*',
			'searcherService.modules.searchEngineGPTS.models.*',
			'searcherService.modules.searchEngineGPTS.components.*'
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
