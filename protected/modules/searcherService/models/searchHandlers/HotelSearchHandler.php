<?php

/**
 * Class HotelSearchHandler
 * Класс для поиска предложения по размещению
 */
class HotelSearchHandler extends SearchHandler
{

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        parent::__construct($module);
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Запуск задания поиска
     * @param $token
     * @return bool
     */
    public function startSearch($token, $agentId)
    {
        $request = new AccomodationSearchRequest($this->module, SearchRequestsFactory::ACCOMMODATION_REQUEST_TYPE);
        try {
            $request->loadFromCache($token);
        } catch (KmpDbException $kde) {

            LogHelper::logExt(
                $kde->class, $kde->method,
                $this->module->getCxtName($kde->class, $kde->method),
                $this->module->getError($kde->getCode()) . PHP_EOL . $kde->getDbMessage(),
                $kde->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            return false;
        }

        $request->agentId = $agentId;

        $progress = 0;
        OfferFinder::setSearchRequestTaskPercentComplete($token, $progress);

        $this->parsePreCondition($request);
        $offers = [];
        $existedOffers = [];

        $providersCount = count($request->providers);
        foreach ($request->providers as $providerType) {

            $provider = ProvidersFactory::createOfferProvider($providerType);

            if (!$provider) {
                continue;
            }

            if (!empty($request->hotelSupplier)) {
                $suppliers = [];
                $suppliers[] = (int)$request->hotelSupplier;
            } else {
                $suppliers = $provider->getOfferSuppliers(SearchRequestsFactory::ACCOMMODATION_REQUEST_TYPE);
            }

            if (!$suppliers) {
                throw new KmpInvalidSettingsException(get_class(), __FUNCTION__,
                    SearcherErrors::INCORRECT_GATEWAYS_CONFIG, [
                        'token' => $token,
                        'agentId' => $agentId,
                        'provider' => $providerType,
                        'supplier' => ''
                    ]
                );
            }

            /** @todo при поиске через несколько движков одновременно надо еще учитывать количество провайдеров.. */
            $tasksCount = count($suppliers);
            $percentPerTask = (int)round(100 / $tasksCount);

            // данная кухня для того, чтобы в итоге процентов стало 100
            $percents = array_fill(0, $tasksCount, $percentPerTask);
            if (($percentPerTask * $tasksCount) < 100) {
                $percents[0] += (100 - $percentPerTask * $tasksCount);
            }


            foreach ($suppliers as $supplier) {

                $this->runOffersSearchBySupplierTask(
                    $token,
                    $agentId,
                    SearchRequestsFactory::ACCOMMODATION_REQUEST_TYPE,
                    $providerType,
                    $supplier,
                    array_pop($percents)
                );
            }

            /*$offersPortions = $this->getPortionsConfig($providerType);

            if (!empty($offersPortions['first']) && is_int($offersPortions['first'])) {
                $minimalRequest = clone $request;
                $minimalRequest->offerLimit = $offersPortions['first'];
                $existedOffers = $this->getMinimalOffersPortion($minimalRequest, $provider, $token);
                $progress = 100 / $providersCount * 0.3;
                OfferFinder::setSearchRequestTaskPercentComplete($token, $progress);
            }

            $providerOffers = $this->search($request, $provider);
            $offers = array_merge($offers, $providerOffers);*/
        }

        return true;
    }

    /**
     * Получить минимальную порцию результатов поиска
     * @deprecated ?
     *
     * @param $request
     * @param $provider
     * @param $token
     * @return array
     */
    protected function getMinimalOffersPortion($request, $provider, $token)
    {
        $providerOffers = $this->search($request, $provider);

        $providerOffers = $this->parsePostConditionRules($providerOffers);

        if (empty($providerOffers)) {
            return [];
        }

        foreach ($providerOffers as $key => $offer) {
            $offer->toCache($token);
        }

        return $providerOffers;
    }

    /**
     * Запуск поиска на указанном провайдере
     * @param $provider
     * @param $request
     */
    protected function search($request, $provider)
    {

        try {
            $providerOffers = $provider->find($request);
            //$this->validateOffers($providerOffers);

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
        }

        $offers = [];

        if (empty($providerOffers)) {
            return $offers;
        }

        $providerId = ProvidersFactory::getOfferProviderIdByClassName(get_class($provider));

        foreach ($providerOffers as $providerOffer) {
            $offer = new HotelProviderOffer($this->module, SearchHandlersFactory::ACCOMMODATION_OFFER_TYPE);
            $providerOffer['providerId'] = $providerId;

//            $providerOffer['supplierCode'] = $offer->getSupplierCode(
//                $providerOffer['supplierCode'],
//                $providerOffer['providerId']
//            );
//
            $result = $offer->initParams($providerOffer);

            if (!$result) {
                continue;
            }

            $offers[] = $offer;
        }

        return $offers;
    }

    /**
     * Поиск предложений по указанному шлюзу и поставщику услуги
     * @param string $token токен поиска
     * @param int $agentId ID агента, для которого совершается поиск
     * @param string $providerType тип шлюза (ex. engineGPTS)
     * @param int $supplierCode код поставщика (из конфига, см. kt_ref_suppliers)
     * @param int $percent процент поиска: 100 / число поставщиков
     */
    public function searchBySupplier($token, $agentId, $providerType, $supplierCode, $percent)
    {
        try {
            $provider = ProvidersFactory::createOfferProvider($providerType);

            $request = new AccomodationSearchRequest($this->module, SearchRequestsFactory::ACCOMMODATION_REQUEST_TYPE);

            $request->agentId = $agentId;

            try {
                $request->loadFromCache($token);
            } catch (KmpDbException $kde) {

                LogHelper::logExt(
                    $kde->class, $kde->method,
                    $this->module->getCxtName($kde->class, $kde->method),
                    $this->module->getError($kde->getCode()) . PHP_EOL . $kde->getDbMessage(),
                    $kde->params,
                    LogHelper::MESSAGE_TYPE_ERROR,
                    $this->namespace . '.errors'
                );

                throw $kde;
            }

            $request->supplierCode = $supplierCode;

            // это для красоты, чтобы проценты ползли при поиске по одному поставщику. 
            // Может добавить геморроя при поиске по нескольким движкам
            if ($percent == 100) {
                OfferFinder::setSearchRequestTaskPercentComplete($token, 1);
            }

            $providerOffers = $this->search($request, $provider);
            $providerOffers = $this->parsePostConditionRules($providerOffers);

            // тоже красивости
            if ($percent == 100) {
                OfferFinder::setSearchRequestTaskPercentComplete($token, 50);
            }

            $this->bulkSaveOffers($providerOffers, $token);

        } catch (Exception $e) {
            LogHelper::logExt(
                __CLASS__, __FUNCTION__, '',
                $e->getMessage(), [
                    'msg' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ],
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
        } finally {
            OfferFinder::setSearchRequestTaskPercentComplete($token, $percent);
        }
    }

    /**
     * Парсинг и применение предусловий
     * @param $request
     * @return mixed
     */
    protected function parsePreConditionRules($request)
    {
        $modifiedRequest = $request;
        $rules = $this->getSearchRules();

        foreach ($rules as $rule) {
            $modifiedRequest = $this->applySearchRule($rule, $modifiedRequest);
        }
        return $modifiedRequest;
    }

    /**
     * Парсинг и применение постусловий
     * @param $offers
     * @return mixed
     */
    protected function parsePostConditionRules($offers)
    {
        return $offers;
        /**
         * @deprecated это все неправильно
         */
        /*
        if (empty($offers)) {
            return false;
        }

        $modifiedOffers = [];
        foreach ($offers as $offer) {
            $modifiedOffers[] = clone $offer;
        }

        $rules = $this->getSearchRules();
        foreach ($modifiedOffers as $key => $modifiedOffer) {

            foreach ($rules as $rule) {
                $modifiedOffer = $this->applySearchRule($rule, $modifiedOffer);
            }

//          todo Убрать после реализации обработки результатов поиска с типом поставщика - multipleGDS
            if ($modifiedOffer->offerType = SearchRequestsFactory::FLIGHT_REQUEST_TYPE) {
                if ($modifiedOffer->supplierCode == 'multipleGDS') {
                    unset($modifiedOffers[$key]);
                    continue;
                }
            }
//

        }

        return $modifiedOffers;
        */
    }

    /**
     * Применить правило предусловий
     * @param $rule
     * @param $params
     * @return mixed
     */
    protected function applySearchRule($rule, $request)
    {
        return $request;
    }

    protected function excludeExistedOffers($offers, $existedOffers)
    {

        $processedOffers = [];

        foreach ($offers as $key => $offer) {

            $found = false;
            foreach ($existedOffers as $key => $existedOffer) {
                if ($offer->isEqual($existedOffer)) {
                    unset($existedOffers[$key]);
                    $found = true;
                    break;
                }
            }

            if ($found == false) {
                $processedOffers[] = $offer;
            }

        }

        return $processedOffers;
    }

    /**
     * Массовое сохранение предложений
     * @param $offers
     */
    protected function bulkSaveOffers($offers, $token)
    {
        if (empty($offers)) {
            return [];
        }

        foreach ($offers as $offer) {
            $offer->toCache($token);
        }
    }

    /**
     * Парсинг предусловий предложения
     * @param $request
     */
    protected function parsePreCondition($request)
    {
        $request->setProviders($this->getOfferProviders());
        $request->setSuppliers($this->getOfferSuppliers());
    }

    /**
     * Массовое сохранение объектов
     * @param $tableName
     * @param $arrayValues
     */
    protected function bulkSave($tableName, $arrayValues)
    {
        $builder = Yii::app()->db->schema->commandBuilder;

        $command = $builder->createMultipleInsertCommand($tableName, $arrayValues);
        $command->execute();
    }

    /**
     * Валидация предложений полученных от провайдера
     */
    protected function validateOffers()
    {

    }

    /**
     * Запуск задачи поиска предложений на указанном движке и с указанным поставщиком
     * @param $token string
     * @param int $agentId ID пользователя КТ, запустившего поиск
     * @param $provider string тип поискового движка
     * @param $supplier string код поставщика в КТ
     * @param $percentPerTask процент выполнения общей задачи на каждую
     * @return bool
     */
    private function runOffersSearchBySupplierTask($token, $agentId, $type, $provider, $supplier, $percentPerTask)
    {
        if (empty($type)) {
            return false;
        }

        if (EnvironmentHelper::getEnvironmentType() == EnvironmentHelper::LOCAL) {

            $this->runSearchTaskAsConsoleCommand($token, $agentId, $provider, $supplier, $percentPerTask);
        } else {

            $this->runSearchTaskAsGearmanJob($token, $agentId, $provider, $supplier, $percentPerTask);
        }

        return true;
    }

    /**
     * Запуск задачи поиска как консольной команды
     * @param $token
     * @param $type
     * @return bool
     */
    private function runSearchTaskAsConsoleCommand($token, $agentId, $provider, $supplier, $percentPerTask)
    {
        $command = 'runAccommodationOfferSearch';

        if (empty($command)) {
            return false;
        }

        if (PHP_OS == 'WINNT') {
            $cmd = YII::app()->basePath . "/yiic.bat $command StartSearchBySupplier --token=$token --agentId=$agentId --provider=$provider --supplier=$supplier --percent=$percentPerTask";
            pclose(popen("start /B " . $cmd, "r"));
        } else {
            $cmd = YII::app()->basePath . "/yiic $command StartSearchBySupplier --token=$token --agentId=$agentId --provider=$provider --supplier=$supplier --percent=$percentPerTask";
            exec($cmd . " > /dev/null &");
        }
    }

    /**
     * Запуск задачи поиска как задания gearman
     * @param $token
     * @param $type
     */
    private function runSearchTaskAsGearmanJob($token, $agentId, $provider, $supplier, $percentPerTask)
    {

        if (!extension_loaded('gearman')) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__,
                SearcherErrors::CANNOT_CREATE_GEARMAN_CLIENT, [
                    'token' => $token,
                    'agentId' => $agentId,
                    'provider' => $provider,
                    'supplier' => $supplier,
                    'percent' => $percentPerTask
                ]);
        }

        $gearmanClient = new GearmanClient();

        $config = $this->module->getConfig('gearman');
        if (!$config) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__,
                SearcherErrors::INCORRECT_GEARMAN_CONFIG, [
                    'token' => $token,
                    'agentId' => $agentId,
                    'provider' => $provider,
                    'supplier' => $supplier,
                    'percent' => $percentPerTask
                ]
            );
        }

        $gearmanClient->addServer($config['host'], $config['port']);

        $searchFunction = $config['workerPrefix'] . '_' . 'accommodationSearcherBySupplier';

        $job = $gearmanClient->doBackground(
            $searchFunction,
            json_encode([
                'token' => $token,
                'agentId' => $agentId,
                'provider' => $provider,
                'supplier' => $supplier,
                'percent' => $percentPerTask
            ])
        );

        if (empty($job)) {
            throw new KmpException(get_class(), __FUNCTION__,
                SearcherErrors::CANNOT_START_GEARMAN_SEARCH_TASK, [
                    'token' => $token,
                    'agentId' => $agentId,
                    'provider' => $provider,
                    'supplier' => $supplier,
                    'percent' => $percentPerTask
                ]
            );
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
                'agentId' => $agentId,
                'supplier' => $supplier,
                'percent' => $percentPerTask
            ],
            LogHelper::MESSAGE_TYPE_INFO,
            $this->namespace . '.gearman.client'
        );

    }

}
