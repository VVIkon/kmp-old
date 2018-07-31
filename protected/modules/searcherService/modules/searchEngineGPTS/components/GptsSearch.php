<?php

/**
 * Class GptsSearch
 * Класс для выполнения операций поиска c использованием GPTS
 */
class GptsSearch
{
    /** @var string namespace для записи логов */
    private $namespace;
    /** @var SearchEngineGPTSModule Модуль компонента */
    private $module;
    /** @var int Код ошибки */
    private $errorCode;
    /** @var ApiClient Клиент api внутрисервисной коммуникации */
    private $apiClient;
    /** @var GptsApiClient Клиент к api провайдера */
    private $gptsApiClient;
    /** @var array коды поставщиков из конфига */
    private $suppliers;

    /**
     * @param $module object
     */
    public function __construct()
    {
        $searcherService = Yii::app()->getModule('searcherService');
        $this->module = $searcherService->getModule('searchEngineGPTS');
        $this->namespace = $this->module->getConfig('log_namespace');
        $this->apiClient = new ApiClient($searcherService);

        if (!$this->init()) {
            return false;
        }
    }

    /**
     * Инициализация поискового модуля
     * @return bool
     */
    protected function init()
    {
        $config = $this->module->getConfig();
        if (!$this->checkConfig($config)) {
            return false;
        }

        if (EnvironmentHelper::getEnvironmentType() == EnvironmentHelper::PRODUCTION) {
            $config['current_api'] = $config['provider']['prod_api'];
        } else {
            $config['current_api'] = $config['provider']['test_api'];
        }

        $this->gptsApiClient = new GptsApiClient($config, $this->module);

        $this->suppliers = $config['suppliers'];
        return true;
    }

    /**
     * Проверка параметров конфигурации поискового модуля
     * @param $config
     * @return bool
     */
    protected function checkConfig($config)
    {
        if (empty($config)) {
            $this->errorCode = SearcherErrors::GPTS_CONFIG_SECTION_NOT_SET;
            return false;
        }

        $validator = new GptsConfigValidator($this->module);

        try {
            $validator->checkConfigParams($config);
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
     * Получить предложения от GPTS по указанному запросу
     * @param $request array
     */
    public function find($request)
    {
        $gptsRequest = GptsRequestsFactory::createSearchRequest($request->requestType, $this->module);

        if (!$gptsRequest->initFromRequest($request->toArray())) {
            throw new KmpInvalidArgumentException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_REQUEST_OFFERS_THROUGH_GPTS,
                ['request params' => $request->toArray()]
            );
        }
        try {
            $pars = $gptsRequest->getRequestParams();
            $result = $this->gptsApiClient->makeRequest($pars, true);
        } catch (KmpInvalidArgumentException $ke) {
            throw new KmpInvalidArgumentException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_REQUEST_OFFERS_THROUGH_GPTS,
                $gptsRequest->getRequestParams()
            );
        }
        /** реконнект, т.к. после долгого ожидания запроса соединение могло отвалиться */
        Yii::app()->db->setActive(false);
        Yii::app()->db->setActive(true);

        $result = $this->makeResponseObjects($result, $gptsRequest);

        return $result;
    }

    /**
     * Получить поставщиков указанного типа предложений
     * @param $offerType
     * @return int[] массив ID поставщиков по версии КТ
     */
    public function getOfferSuppliers($offerType)
    {
        if (empty($offerType)) {
            return false;
        }

        if (isset($this->suppliers[$offerType])) {
            return $this->suppliers[$offerType];
        }

        return false;
    }

    /**
     * Создание объекта ответа от GPTS
     * @param array $params
     * @param GptsRequest $gptsRequest
     */
    private function makeResponseObjects($stream, &$gptsRequest)
    {
//        $containerNode = '';
        $offers = [];
        $responseType = $gptsRequest->requestType;

        switch ($responseType) {
            case 1:
                /* В данном случае мы получим от GPTS поток для парсинга */
//                $hotelOffers = $params;
                $dateFrom = $gptsRequest->startDate;
                $dateTo = $gptsRequest->endDate;

                $cityId = CitiesMapperHelper::getCityIdBySupplierCityID(
                    CitiesMapperHelper::GPTS_SUPPLIER_ID,
                    $gptsRequest->cityId
                );
                $gptsCityId = $gptsRequest->cityId;

                $offersListener = new SearchAccommodationListener(
                    function ($offer) use (&$offers, $dateFrom, $dateTo, $gptsCityId) {
                        $o = $this->makeHotelOffer($offer, $dateFrom, $dateTo, $gptsCityId);
                        if ($o !== false) {
                            $offers[] = $o;
                        }
                    }
                );
                break;
            case 2:
                $offersListener = new SearchAviaListener(function ($offer) use (&$offers) {
                    $gptsResponse = new FlightGptsResponse($this->module, 2);
                    $gptsResponse->initParams($offer);
                    $offers[] = $gptsResponse->toOffer();
                });
//                $containerNode = 'offers';
//
//                foreach ($params[$containerNode] as $offer) {
//                    $gptsResponse = GptsResponsesFactory::createSearchResponse($responseType, $this->module);
//                    $gptsResponse->initParams($offer);
//                    $offers[] = $gptsResponse->toOffer();
//                }
                break;
        }

        $parser = new JsonStreamingParser\Parser($stream, $offersListener);
        $parser->parse();
        fclose($stream);

        // если ничего не нашли, то будем логировать запрос
        if (count($offers) == 0) {
            LogHelper::logExt(
                __CLASS__,
                __METHOD__,
                'Формирование результатов поиска',
                'Нет результатов поиска',
                $gptsRequest->getRequestParams(),
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
        }

        return $offers;
    }

    /**
     * Создание отельного оффера
     * @param array $offer структура предложения отеля из GPTS
     * @param string $dateFrom дата начала предложения (заезд)
     * @param string $dateTo дата окончания предложения (выезд)
     * @param int $gptsCityId ID города (GPTS)
     * @return HotelGptsResponse оффер отеля от GPTS
     */
    private function makeHotelOffer($offer, $dateFrom, $dateTo, $gptsCityId)
    {
        if (empty($offer['info']['supplierCode']) || empty($offer['info']['hotelCode'])) {
            LogHelper::logExt(
                __CLASS__, __FUNCTION__,
                'parse hotel offers', 'broken offer',
                ['offer' => $offer],
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            return false;
        }

        // костыль для собственных отелей
        // приходят в виде "company.1045539",
        // а мы отрезаем "company."
        $offer['info']['supplierCode'] = str_replace('company.', '', $offer['info']['supplierCode']);

        /**
         * @todo т.к. GPTS не присылет пока даты начала/окончания проживания в номере,
         * заполняем их из данных поискового запроса
         */
        foreach ($offer['roomOffers'] as &$roomOffer) {
            $roomOffer['dateFrom'] = $dateFrom;
            $roomOffer['dateTo'] = $dateTo;
        }

        $gptsResponse = new HotelGptsResponse($this->module, 'HotelGptsResponse');
        $gptsResponse->initParams($offer);

        $hotelInfo = HotelsMapperHelper::getHotelInfoBySupplierCode(
            $offer['info']['supplierCode'],
            $offer['info']['hotelCode'],
            $gptsCityId,
            ['hotelId', 'cityId']
        );

        // если отеля не нашли, создаем новый отель через модуль справочников
        if ($hotelInfo === false) {
            try {
                $creationResponse = $this->apiClient->makeRestRequest('supplierService', 'CreateHotelFromSearch', [
                    'hotelInfo' => $offer['info'],
                    'gptsMainCityId' => $gptsCityId
                ]);
                $creationResult = json_decode($creationResponse, true);

                if (!is_array($creationResult) || $creationResult['status'] !== 0) {
                    LogHelper::logExt(
                        __CLASS__, __FUNCTION__,
                        'parse hotel offers', 'hotel creation failed',
                        [
                            'creation_response' => $creationResponse,
                            'creation_result' => $creationResult
                        ],
                        LogHelper::MESSAGE_TYPE_ERROR,
                        $this->namespace . '.errors'
                    );

                    return false;
                } else {
                    $hotelInfo = HotelsMapperHelper::getHotelInfoByKTID((int)$creationResult['body']['hotelId']);
                    if ($hotelInfo === false) {
                        LogHelper::logExt(
                            __CLASS__, __FUNCTION__,
                            'parse hotel offers', 'created hotel wasn\'t found',
                            ['hotelId' => $creationResult['body']['hotelId']],
                            LogHelper::MESSAGE_TYPE_ERROR,
                            $this->namespace . '.errors'
                        );

                        return false;
                    }
                }


            } catch (Exception $e) {
                LogHelper::logExt(
                    __CLASS__, __FUNCTION__,
                    'parse hotel offers', 'cannot create hotel',
                    [
                        'supplierCode' => $offer['info']['supplierCode'],
                        'hotelCode' => $offer['info']['hotelCode'],
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'creationResult' => $creationResult
                    ],
                    LogHelper::MESSAGE_TYPE_ERROR,
                    $this->namespace . '.errors'
                );

                return false;
            }
        }

        //PerfomanceHelper::startCounter();
        return $gptsResponse->toOffer($hotelInfo['hotelId'], $hotelInfo['cityId']);
    }

    /**
     * Загрузка доп услуг в оффер
     * @param HotelOfferResponse $offer
     */
    public function getHotelAddOffers(HotelOfferResponse $offer)
    {
        $result = $this->gptsApiClient->makeRequest([
            'requestType' => 'additionalOptions',
            'offerKey' => $offer->getOfferKey()
        ]);

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'Ответ от GPTS additionalOptions', '',
            [
                'offerKey' => $offer->getOfferKey(),
                'response' => $result
            ],
            LogHelper::MESSAGE_TYPE_INFO, 'system.searcherservice.*'
        );

        $addMealRefSubService = RefSubServicesRepository::getById(1)->getSubServiceConcreteClass();
        $earlyInRefSubService = RefSubServicesRepository::getById(2)->getSubServiceConcreteClass();
        $lateCheckOutRefSubService = RefSubServicesRepository::getById(3)->getSubServiceConcreteClass();

        // запишем варианты платного доп питания
        if (!empty($result['paidMealOptions'])) {
            foreach ($result['paidMealOptions'] as $paidMealOption) {
                $addMealRefSubService->initWithGPTSData($paidMealOption);
                HotelAddOfferResponse::createFromGPTSData($offer, $paidMealOption, $addMealRefSubService); // пока поставим тип 1
            }
        }

        // ранний заезд
        if (!empty($result['earlyCheckIn'])) {
            foreach ($result['earlyCheckIn'] as $earlyCheckIn) {
                $earlyInRefSubService->initWithGPTSData($earlyCheckIn);
                HotelAddOfferResponse::createFromGPTSData($offer, $earlyCheckIn, $earlyInRefSubService); // пока поставим тип 2
            }
        }

        // поздний выезд
        if (!empty($result['lateCheckOut'])) {
            foreach ($result['lateCheckOut'] as $lateCheckOut) {
                $lateCheckOutRefSubService->initWithGPTSData($lateCheckOut);
                HotelAddOfferResponse::createFromGPTSData($offer, $lateCheckOut, $lateCheckOutRefSubService); // пока поставим тип 3
            }
        }
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->errorCode;
    }
}
