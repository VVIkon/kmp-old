<?php

/**
 * Class OfferSearchController
 * Используется для реализации команд поиска предложений
 */
class OfferSearchController extends SecuredRestController
{
    /**
     * Запуск команды на получение прдложений у провайдеров
     */
    public function actionSearchStart()
    {
        $params = $this->_getRequestParams();
        // Проверка прав
        $permitions[] = 15; // Общий поиск
        switch ((int)$params['serviceType']) {
            case 1: // Поиск проживания
                $permitions[] = 17;
                break;
            case 2: // Поиск авиабилета
                $permitions[] = 16;
                break;
            case 3: // Поиск трансфера
                $permitions[] = 19;
                break;
            case 7: // Поиск Ж/д билета
                $permitions[] = 18;
                break;
        }
        $this->_sendErrorResponseIfNoPermissions($permitions);

        $module = YII::app()->getModule('searcherService');
        $searcher = $module->SearchManager();
        $response = $searcher->findOffer($params);
        if ($response === false) {
            $this->_sendResponse(false, array(),
                $module->getError($searcher->getLastError()),
                $searcher->getLastError()
            );
        } else {
            $this->_sendResponse(true, $response, '');
        }
    }

    /**
     * Получение найденных предложений из кэша
     */
    public function actionGetSearchResult()
    {

        $module = YII::app()->getModule('searcherService');

        $params = $this->_getRequestParams();

        $searcher = $module->SearchManager();

        $response = $searcher->getSearchResult($params);

        if ($response === false) {
            $this->_sendResponse(false, array(),
                $module->getError($searcher->getLastError()),
                $searcher->getLastError()
            );
        } else {
            $this->_sendResponse(true, $response, '');
        }
    }

    /**
     * Получение найденных предложений из кэша
     */
    public function actionGetOffer()
    {

        $module = YII::app()->getModule('searcherService');

        $params = $this->_getRequestParams();

        $searcher = $module->SearchManager();

        $response = $searcher->getOffer($params);

        if ($response === false) {
            $this->_sendResponse(false, array(),
                $module->getError($searcher->getLastError()),
                $searcher->getLastError()
            );
        } else {
            $this->_sendResponse(true, $response, '');
        }
    }

    /**
     * Получение найденных предложений из кэша
     */
    public function actionGetCacheOffer()
    {
        $module = YII::app()->getModule('searcherService');
        $params = $this->_getRequestParams();

        // проверим язык
        if (!isset($params['lang'])) {
            $this->_sendResponse(false, array(),
                $module->getError(SearcherErrors::LANGUAGE_NOT_SET),
                SearcherErrors::LANGUAGE_NOT_SET
            );
        }
        // проверим тип сервиса
        if (!isset($params['serviceType'])) {
            $this->_sendResponse(false, array(),
                $module->getError(SearcherErrors::SERVICE_TYPE_NOT_SET),
                SearcherErrors::SERVICE_TYPE_NOT_SET
            );
        }
        if (!isset($params['offerId'])) {
            $this->_sendResponse(false, array(),
                $module->getError(SearcherErrors::OFFER_ID_NOT_SET),
                SearcherErrors::OFFER_ID_NOT_SET
            );
        }

        $answer = [];

        $RefServices = RefServices::model()->findByAttributes(array('ServiceID' => $params['serviceType']));

        if ($RefServices) {
            $offerResponseRepositoryClassName = $RefServices->getModelName() . 'ResponseRepository';
        } else {
            $this->_sendResponseWithErrorCode(SearcherErrors::SERVICE_TYPE_INCORRECT);
            return;
        }

        // с помощью репозиториев офферов получим оффер
        if (class_exists($offerResponseRepositoryClassName)) {
            $Offer = $offerResponseRepositoryClassName::getByOfferId($params['offerId']);
        } else {
            $this->_sendResponseWithErrorCode(SearcherErrors::CANNOT_GET_OFFER);
            return;
        }

        // если нашелся оффер, то выдадим его
        if ($Offer) {
            $Offer->setConfig($module->getConfig());
            $answer['offerInfo'] = $Offer->toArray();
            $this->_sendResponse(true, $answer, '');
        } else {
            $this->_sendResponseWithErrorCode(SearcherErrors::CANNOT_GET_OFFER);
        }
    }

    /**
     *
     */
    public function actionParseServiceFormConditions()
    {
        $params = $this->_getRequestParams();

        // проверим входные параметры
        if (!isset($params['companyId']) || !isset($params['offerId']) || !isset($params['serviceType'])) {
            $this->_sendResponseWithErrorCode(SearcherErrors::INCORRECT_INPUT_PARAMS);
        }

        $company = CompanyRepository::getById($params['companyId']);
        if (is_null($company)) {
            $this->_sendResponseWithErrorCode(SearcherErrors::INCORRECT_INPUT_PARAMS);
            return;
        }

        $RefServices = RefServices::model()->findByAttributes(array('ServiceID' => $params['serviceType']));

        if ($RefServices) {
            $offerResponseRepositoryClassName = $RefServices->getModelName() . 'ResponseRepository';
        } else {
            $this->_sendResponseWithErrorCode(SearcherErrors::SERVICE_TYPE_INCORRECT);
            return;
        }

        // с помощью репозиториев офферов получим оффер
        if (class_exists($offerResponseRepositoryClassName)) {
            $Offer = $offerResponseRepositoryClassName::getByOfferId($params['offerId']);
        } else {
            $this->_sendResponseWithErrorCode(SearcherErrors::CANNOT_GET_OFFER);
            return;
        }

        if ($Offer) {
            $travelPolicy = new TravelPolicy($company, $params['serviceType']);
            $travelPolicy->applyPreExecute($Offer);
            $this->_sendResponseData([]);
        } else {
            $this->_sendResponseWithErrorCode(SearcherErrors::CANNOT_GET_OFFER);
        }
    }

    /**
     * Возвращает параметры вариантов дополнительного питания для проживания
     */
    public function actionGetHotelAdditionalService()
    {
        $searchMgr = new SearchManager();

        try {
            $resp = $searchMgr->getHotelAdditionalService($this->_getRequestParams());
            $this->_sendResponseData($resp);
        } catch (KmpException $ke) {
            $this->_sendResponseWithErrorCode($ke->getCode());
        }
    }
}
