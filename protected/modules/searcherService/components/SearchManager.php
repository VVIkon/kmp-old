<?php

/**
 * Class SearchManager
 * Класс для выполнения операций поиска
 */
class SearchManager
{
    /**
     * Используется для хранения ссылки
     * на модуль поиска через КТ
     * @var object
     */
    private $searchKt;

    /**
     * Используется для хранения ссылки
     * на модуль поиска через gpts
     * @var object
     */
    private $searchGpts;

    /**
     * Используется для хранения ссылки
     * на модуль подсказок для ввода
     * @var object
     */
    private $searchSuggests;

    /**
     * Используется для хранения ссылки на текущий модуль
     * @var object
     */
    private $module;

    /**
     * namespace для записи логов
     * @var string
     */
    private $namespace;

    /**
     * Код ошибки
     * @var int
     */
    private $errorCode;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct()
    {
        $this->searchKt = YII::app()->getModule('searcherService')->getModule('searchEngineKT');
        $this->searchGpts = YII::app()->getModule('searcherService')->getModule('searchEngineGPTS');
        $this->searchSuggests = YII::app()->getModule('searcherService')->getModule('searchSuggests');

        $this->module = YII::app()->getModule('searcherService');
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Получить список локаций по указаным параметрам
     * @param $params
     * @return bool|array
     */
    public function getSuggestLocation($params)
    {
        if (!$this->checkGetSuggestLocationCommonParams($params)) {
            return false;
        }

        // Если в параметрах представлен countryId - поиск выполняется только по ID страны,
        // и в результатах возвращается наименование страны. Прочие поля остаются пустые.
        $location = [];
        if (!empty($params['countryId'])) {
            $country = CountryRepository::getById($params['countryId']);
            if (is_null($country)) {
                $this->errorCode = SearcherErrors::COUNTRY_NOT_FOUND;
                return false;
            }
            $country->setLang($params['lang']);

            $location['country'] = $country->getName();
        }
        if (!empty($params['cityId'])) {
            $city = CityRepository::getById($params['cityId']);
            if (is_null($city)) {
                $this->errorCode = SearcherErrors::CITY_NOT_FOUND;
                return false;
            }
            $city->setLang($params['lang']);
            $location['city'] = $city->getName();
        }

        if (!empty($location)) {
            return [
                0 => $location
            ];
        }

        $langId = LangForm::GetLanguageCodeByName($params['lang']);
        $suggest = SuggestsFactory::createSuggestClass($params['serviceType']);

        try {
            return $suggest->find($params['location'], $langId);
        } catch (KmpDbException $kde) {

            LogHelper::logExt(
                $kde->class,
                $kde->method,
                $this->module->getCxtName($kde->class, $kde->method),
                $this->module->getError($kde->getCode()) . PHP_EOL . $kde->getDbMessage(),
                $kde->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $kde->getCode();
            return false;
        }
    }

    /**
     * Получить предложения по вводу отеля
     * @param $params
     * @return bool
     */
    public function getSuggestHotel($params)
    {
        if (!$this->checkGetSuggestHotelParams($params)) {
            return false;
        }

        $langId = LangForm::GetLanguageCodeByName($params['lang']);
        $suggest = SuggestsFactory::createSuggestClass(SuggestsFactory::HOTEL_SUGGEST_TYPE);

        try {
            return $suggest->find($params, $langId);
        } catch (KmpDbException $kde) {

            LogHelper::logExt(
                $kde->class,
                $kde->method,
                $this->module->getCxtName($kde->class, $kde->method),
                $this->module->getError($kde->getCode()) . PHP_EOL . $kde->getDbMessage(),
                $kde->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $kde->getCode();
            return false;
        }
    }

    /**
     * Проверка общих параметров поиска предложений автоподстановки
     * @param $params
     */
    public function checkGetSuggestHotelParams($params)
    {
        $suggestValidator = $this->module->SuggestsValidator($this->module);

        try {
            $suggestValidator->checkSuggestHotelParams($params);
        } catch (KmpInvalidArgumentException $ke) {
            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $ke->getCode();

            return false;
        }

        return true;
    }

    /**
     * Проверка общих параметров поиска предложений автоподстановки
     * @param $params
     */
    public function checkGetSuggestLocationCommonParams($params)
    {
        $suggestValidator = $this->module->SuggestsValidator($this->module);

        try {
            $suggestValidator->checkSuggestCommonParams($params);
        } catch (KmpInvalidArgumentException $ke) {
            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $ke->getCode();

            return false;
        }

        return true;
    }

    /**
     * Команда создания запроса на поиск предложений
     * @param $params array
     * @return array|bool
     */
    public function findOffer($params)
    {
        if (!$this->checkFindOffer($params)) {
            return false;
        }

        /**
         * Определяем, кем и для кого совершается запрос
         * @todo это, блин, не круто - подключать классы из другого сервиса, ломает всю концепцию.
         * По идее профиль пользователя должен грузиться автоматом при проверке usertoken'а
         * куда-нибудь в Yii для глобального доступа.
         */
        $accountModule = Yii::app()->getModule('systemService')->getModule('account');
        $accountsMgr = $accountModule->AccountsMgr($accountModule);
        $userinfo = $accountsMgr->getUserProfileByToken($params['usertoken']);

        $agentId = 0;

        switch ((int)$userinfo['userType']) {
            case 1: //operator
                if (!empty($params['requestDetails']['clientId'])) {
                    $Company = CompanyRepository::getById((int)$params['requestDetails']['clientId']);

                    if (is_null($Company)) {
                        throw new KmpException(
                            __CLASS__, __FUNCTION__,
                            SearcherErrors::CLIENT_COMPANY_NOT_FOUND,
                            $params['requestDetails']
                        );
                    }

                    if ($Company->isAgent()) {
                        $CompanyOnlineManager = AccountRepository::getCompanyOnlineManager($Company->getId());

                        if (is_null($CompanyOnlineManager)) {
                            throw new KmpException(
                                __CLASS__, __FUNCTION__,
                                SearcherErrors::COMPANY_HAS_NO_ONLINE_MANAGERS,
                                ['companyId' => $Company->getId()]
                            );
                        }

                        $agentId = $CompanyOnlineManager->getUserId();
//                        $agentId = $userinfo['userId'];
                    } else if ($Company->isDirectSales()) {
                        $params['requestDetails']['clientId'] = null;
                    }

                } else {
                    $params['requestDetails']['clientId'] = null;
                }
                break;
            case 2: //agent
                $params['requestDetails']['clientId'] = (int)$userinfo['companyID'];
                $agentId = (int)$userinfo['userId'];
                break;
            case 3: //corporator
                $params['requestDetails']['clientId'] = (int)$userinfo['companyID'];
                break;
        }

        $requestInfo = $this->makeSearchRequest($params);

        if (empty($requestInfo['token'])) {
            return false;
        }

        if ($requestInfo['isNew']) {
            $type = OfferFinder::getOfferTypeByToken($requestInfo['token']);
            try {

                if (!$this->runSearchTask($requestInfo['token'], $type, $agentId)) {
                    return false;
                }
            } catch (KmpException $ke) {

                LogHelper::logExt(
                    $ke->class,
                    $ke->method,
                    $this->module->getCxtName($ke->class, $ke->method),
                    $this->module->getError($ke->getCode()),
                    $ke->params,
                    LogHelper::MESSAGE_TYPE_ERROR,
                    $this->namespace . '.errors'
                );

                $this->errorCode = $ke->getCode();
                return false;
            }
        }

        //Синхронный запуск поиска
        /*if (!$this->startSearchTask($requestInfo['token'])) {
            return false;
        }*/

        return ['searchToken' => $requestInfo['token']];
    }

    /**
     * Возвращает параметры вариантов дополнительного питания для проживания
     * @param $params
     * @return mixed
     */
    public function getHotelAdditionalService($params)
    {
        // проверим входные параметры
        $validator = new OfferValidator($this->module);
        $validator->checkGetHotelAdditionalService($params);

        // найдем оффер
        $offer = HotelOfferResponseRepository::getByOfferId($params['offerId']);

        if (is_null($offer)) {
            throw new KmpException(get_class($this), __FUNCTION__, SearcherErrors::OFFER_ID_NOT_SET, []);
        }

        // проверим, если доп услуги есть в кеше, то достанем из кеша, иначе запросим шлюз
        if (!$offer->hasAddOffers()) {
            $gateId = $offer->getSupplier()->getGatewayID();
            $gate = ProvidersFactory::createOfferProviderByGateId($gateId);

            if (!$gate) {
                throw new KmpException(get_class($this), __FUNCTION__, SearcherErrors::GATE_NOT_FOUND, []);
            }

            $gate->getHotelAddOffers($offer);
            $offer->refresh();
        }

        // выберем доп услуги оффера
        $addOffers = $offer->getAddOffers();
        $resp = [
            'offers' => []
        ];

        // выведем в формате sl_addServiceOffer
        foreach ($addOffers as $addOffer) {
            $addOffer->setViewCurrency(CurrencyStorage::findByString($params['viewCurrency']));
            $resp['offers'][] = $addOffer->toSLAddServiceOffer();
        }

        return $resp;
    }

    /**
     * Получение найденных предложений
     * @param $params
     * @return array|bool
     */
    public function getSearchResult($params)
    {
        if (!$this->checkGetSearchResult($params)) {
            return false;
        }

        $TokenCache = TokenCacheRepository::getByToken($params['searchToken']);

        if (!$TokenCache) {
            $this->errorCode = SearcherErrors::INCORRECT_REQUEST_TOKEN;
            return false;
        }

        $tokenlimit = $TokenCache->getTokenLimitMinutes();
        $percent = $TokenCache->getPercent();

        $serviceType = $TokenCache->getServiceId();

        // если Отели - вызовем новый код
        // создадим валюту просмотра из входных данных
        $CurrencyRates = CurrencyRates::getInstance();
        $currencyId = $CurrencyRates->getIdByCode($params['currency']);
        $ViewCurrency = CurrencyStorage::getById($currencyId);

        $offersRepositoryClassName = $TokenCache->getOffersRepositoryClassName();
        $offers = $offersRepositoryClassName::getOffersByToken($TokenCache->getToken(), $params['startOfferId'], $params['offerLimit']);
        $searchRequest = $offersRepositoryClassName::getSearchRequestByToken($TokenCache);

//            list($queryCount, $queryTime) = Yii::app()->db->getStats();
//            echo "Query count: $queryCount, Total query time: " . sprintf('%0.5f', $queryTime) . "s";
//            echo PHP_EOL;
//            exit;

        // найдем компанию, для которой выбрать правила ТП
        $companyToSearchTravelPolicyRules = $searchRequest->getCompany();

        // если компания не задана, то возьмем компанию текущего пользователя
        if (is_null($companyToSearchTravelPolicyRules)) {
            $userProfile = Yii::app()->user->getState('userProfile');
            $account = AccountRepository::getAccountById($userProfile['userId']);
            $companyToSearchTravelPolicyRules = $account->getCompany();
        }

        try {
            $travelPolicy = new TravelPolicy($companyToSearchTravelPolicyRules, $serviceType);
            $travelPolicy->applySearch($offers);
        } catch (TravelPolicyException $e) {
//            var_dump($e);
//            return false;
        }

//            var_dump(count($offers));
//            exit;

        list($offersInfo, $offerIds) = $offersRepositoryClassName::decorateForGetSearchResult($offers, $ViewCurrency, $params['lang']);

        if (empty($offersInfo)) {
            $offerIds = [$params['startOfferId']];
        }

        $lastOfferId = max($offerIds);

        return [
            'serviceType' => $serviceType,
            'completed' => $percent,
            'tokenlimit' => $tokenlimit,
            'lastOfferId' => $lastOfferId,
            'response' => $offersInfo
        ];
    }

    /**
     * Проверка параметров запроса на получение найденных придложений
     * @param $params
     * @return bool
     */
    private function checkGetSearchResult($params)
    {

        $generalValidator = new OfferValidator($this->module);

        try {
            $generalValidator->checkGetSearchResultParams($params);
        } catch (KmpInvalidArgumentException $ke) {

            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $ke->getCode();

            return false;
        }

        return true;
    }

//    /**       DEPRECATED
//     * Получить предложения по указаному токену из кэша
//     * @param $token
//     * @return bool|mixed
//     */
//    private function getOffersHandler($token)
//    {
//        try {
//            $offerType = OfferFinder::getOfferTypeByToken($token);
//        } catch (KmpDbException $kde) {
//
//            LogHelper::logExt(
//                $kde->class,
//                $kde->method,
//                $this->module->getCxtName($kde->class, $kde->method),
//                $this->module->getError($kde->getCode()) . PHP_EOL . $kde->getDbMessage(),
//                $kde->params,
//                LogHelper::MESSAGE_TYPE_ERROR,
//                $this->namespace . '.errors'
//            );
//
//            $this->errorCode = $kde->getCode();
//            return false;
//        }
//
//        $offersHandler = ResponseHandlersFactory::createResponseHandler($offerType, $this->module);
//        return $offersHandler;
//    }

    /**
     * Валидация параметров и состояний для
     * получения списка подсказок ввода аэропорта
     * @param $params
     * @return bool
     */
    private function checkGetFlightSuggestLocations($params)
    {
        $suggestValidator = $this->module->SuggestsValidator($this->module);

        try {
            $suggestValidator->checkFlightSuggestParams($params);
        } catch (KmpInvalidArgumentException $ke) {

            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $ke->getCode();

            return false;
        }

        return true;
    }

    /**
     * Фильтрация входных параметров для получения
     * списка подсказок ввода аэропорта
     * @param $params
     * @return mixed
     */
    private function filterGetFlightSuggestLocations($params)
    {
        return $params;
    }

    /**
     * Валидация параметров команды запроса предложений
     * @param $params
     * @return bool
     */
    private function checkFindOffer($params)
    {
        $generalValidator = new OfferValidator($this->module);

        try {
            $generalValidator->checkFindOfferParams($params);

            $offerValidator = OfferValidatorsFactory::createOfferValidator($params['serviceType'], $this->module);
            $offerValidator->checkRequestParams($params['requestDetails']);
        } catch (KmpInvalidArgumentException $ke) {

            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $ke->getCode();

            return false;
        }

        return true;
    }

    /**
     * Сформировать запрос поиска
     * @param $params array
     * @return array|bool
     */
    private function makeSearchRequest($params)
    {

        $finder = OfferFindersFactory
            ::createOfferFinder($params['serviceType'], $this->module);

        try {
            $requestToken = $finder->makeSearchRequestTask($params);
            $isNew = $finder->isLastRequestNew();

        } catch (KmpDbException $kde) {
            LogHelper::logExt(
                $kde->class,
                $kde->method,
                $this->module->getCxtName($kde->class, $kde->method),
                $this->module->getError($kde->getCode()) . PHP_EOL . $kde->getDbMessage(),
                $kde->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'

            );

            $this->errorCode = $kde->getCode();
            return false;
        } catch (KmpException $ke) {

            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $ke->getCode();
            return false;
        }

        return ['token' => $requestToken, 'isNew' => $isNew];
    }

    /**
     * Запуск задачи поиска
     * @param string $token токен задачи поиска
     * @param int $type тип предложений для поиска
     * @param int $agentId ID пользователя
     * @return bool
     */
    private function runSearchTask($token, $type, $agentId)
    {
        if (empty($type)) {
            return false;
        }

        if (EnvironmentHelper::getEnvironmentType() == EnvironmentHelper::LOCAL) {

            $this->runSearchTaskAsConsoleCommand($token, $type, $agentId);

        } else {

            $this->runSearchTaskAsGearmanJob($token, $type, $agentId);
        }

        return true;
    }

    /**
     * Запуск задачи поиска как консольной команды
     * @param string $token токен задачи поиска
     * @param int $type тип предложений для поиска
     * @param int $agentId ID пользователя
     * @return bool
     */
    private function runSearchTaskAsConsoleCommand($token, $type, $agentId)
    {
        $command = SearchCommandsManager::getSearchCommand($type);

        if (empty($command)) {
            return false;
        }

        if (PHP_OS == 'WINNT') {
            $cmd = YII::app()->basePath . "/yiic.bat $command startsearch --token=$token --agentId=$agentId";
            pclose(popen("start /B " . $cmd, "r"));
        } else {
            $cmd = YII::app()->basePath . "/yiic $command startsearch --token=$token --agentId=$agentId";
            exec($cmd . " > /dev/null &");
        }
    }

    /**
     * Запуск задачи поиска как задания gearman
     * @param string $token токен задачи поиска
     * @param int $type тип предложений для поиска
     * @param int $agentId ID пользователя
     */
    private function runSearchTaskAsGearmanJob($token, $type, $agentId)
    {

        if (!extension_loaded('gearman')) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__,
                SearcherErrors::CANNOT_CREATE_GEARMAN_CLIENT, ['token' => $token, 'type' => $type, 'agentId' => $agentId]);
        }
        $gearmanClient = new GearmanClient();

        $config = $this->module->getConfig('gearman');
        if (!$config) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__,
                SearcherErrors::INCORRECT_GEARMAN_CONFIG, ['token' => $token, 'type' => $type, 'agentId' => $agentId]);
        }

        $gearmanClient->addServer($config['host'], $config['port']);

        $searchFunction = OfferSearchWorker::getSearchFunctionByTypeOffer($type, $config['workerPrefix']);

        $job = $gearmanClient->doBackground(
            $searchFunction,
            json_encode(['token' => $token, 'agentId' => $agentId])
        );

        if (empty($job)) {
            throw new KmpException(get_class(), __FUNCTION__,
                SearcherErrors::CANNOT_START_SEARCH_TASK, ['token' => $token, 'type' => $type, 'agentId' => $agentId]);
        }

        LogHelper::logExt(
            get_class($this),
            __FUNCTION__,
            $this->module->getCxtName(get_class($this), __FUNCTION__),
            '',
            [
                'jobHandle' => $job,
                'function' => $searchFunction,
                'token' => $token,
                'agentId' => $agentId
            ],
            LogHelper::MESSAGE_TYPE_INFO,
            $this->namespace . '.gearman.client'
        );

    }

    /**
     * Запуск синхронной команды поиска
     * @param $token
     * @return bool
     * @todo зачем это нужно? таски вроде запускаются хэндлерами...
     */
    public function startSearchTask($token)
    {
        $type = OfferFinder::getOfferTypeByToken($token);
        $handler = SearchHandlersFactory::createSearchHandler($type, $this->module);
        if (empty($handler)) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__,
                SearcherErrors::INCORRECT_REQUEST_TOKEN, ['token' => $token]
            );
        }
        $handler->startSearch($token);

        return true;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->errorCode;
    }

    public function getSсhedule($params)
    {
        $searchFlightTimeTable = new GptsSearchFlightTimeTable();
        $schedule = $searchFlightTimeTable->scheduleSearcher($params);
        if ($schedule == false) {
            $this->errorCode = $searchFlightTimeTable->getErrorCode();
        }
        return $schedule;
    }
}
