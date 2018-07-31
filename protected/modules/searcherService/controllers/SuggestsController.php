<?php

/**
 * Class SuggestsController
 * Используется для реализации команд работы с поиском подсказок для пользовательского ввода
 */
class SuggestsController extends SecuredRestController
{

    /**
     * Операция выполняет поиск предложений автоподстановки локаций
     */
	public function actionGetSuggestLocation()
	{
        $this->_sendErrorResponseIfNoPermissions([20, 40, 41, 42]);

		$module = YII::app()->getModule('searcherService');

		$params = $this->_getRequestParams();

		$searcher = $module->SearchManager();

		$response = $searcher->getSuggestLocation($params);

		if ($response === false) {
			$this->_sendResponse(false, array(),
				$module->getError($searcher->getLastError()),
				$searcher->getLastError()
			);
		} else {
			$this->_sendResponse(true, $response,'');
		}
	}

	public function actionGetSuggestHotel()
	{
		$module = YII::app()->getModule('searcherService');

		$params = $this->_getRequestParams();

		$searcher = $module->SearchManager();

		$response = $searcher->getSuggestHotel($params);

		if ($response === false) {
			$this->_sendResponse(false, array(),
				$module->getError($searcher->getLastError()),
				$searcher->getLastError()
			);
		} else {
			$this->_sendResponse(true, $response,'');
		}
	}

    public function actionGetSchedule()
    {
        $module = YII::app()->getModule('searcherService');
        $params = $this->_getRequestParams();
        $searcher = $module->SearchManager();
        $response = $searcher->getSсhedule($params);

        if ($response === false) {
            $this->_sendResponse(false, array(),
                $module->getError($searcher->getLastError()),
                $searcher->getLastError()
            );
        } else {
            $this->_sendResponse(true, $response,'');
        }
    }

}