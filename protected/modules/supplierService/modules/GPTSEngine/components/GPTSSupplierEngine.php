<?php

/** реализация общих методов работы с оффером для GPTS */
class GPTSSupplierEngine extends SupplierEngine
{
    const ENGINE_ID = 5;

    /** @var KModule модуль компонента */
    protected $module;

    /** @var GPTSApiClient клиент запросов к API GPTS */
    protected $apiClient;

    /** @var mixed[] соответствие типа услуги классу, управляющему действиями с ней */
    private static $serviceNames = [
        1 => 'Accomodation',
        2 => 'Flight',
        3 => 'Transfer',
        4 => 'Visa',
        5 => 'CarRent'
    ];

    /**
     * Соответствие значения serviceTypesGPTS значению КТ
     * @param
     * @returm
     */
    private static $serviceTypeGPTS = [
        'ACCOMMODATION' => 1,
        'FLIGHT' => 2,
        'TRANSFER' => 3,
        'VISA' => 4,
        'CARRENT' => 5
    ];
    /**
     * Соответствие статусов услуги КТ - GPTS для Hotel
     * @param
     * @returm
     */
    private static $statusServiceHotelKT_GPTS = [
        0 => 'QUOTED',
        1 => 'CONFIRMATION_PENDING',
        2 => 'CONFIRMED',
        3 => 'CONFIRMED',
        4 => 'CONFIRMED',
        5 => 'CONFIRMED',
        6 => 'REJECTED',                //*
        7 => 'CANCELLED',               //*
        8 => 'CONFIRMED',               //*
        9 => 'ERROR',
    ];
    /**
     * Соответствие статусов услуги КТ - GPTSдля Avia
     * @param
     * @returm
     */
    private static $statusServiceAviaKT_GPTS = [
        0 => 'QUOTED',
        1 => 'CONFIRMATION_PENDING',
        2 => 'PRECONFIRMED',
        3 => 'PRECONFIRMED',
        4 => 'PRECONFIRMED',
        5 => 'PRECONFIRMED',
        6 => 'REJECTED',                //*
        7 => 'CANCELLED',               //*
        8 => 'CONFIRMED',//'ISSUED',                  //*
        9 => 'ERROR',
    ];

    /**
     * Соответствие статусов услуги GPTS - КТ для Hotel
     * @param
     * @returm
     */
    private static $statusGPTSService = [
        'QUOTED' => 0,                  // New
        'CONFIRMATION_PENDING' => 1,    // W_BOOKED
        'CONFIRMATION PENDING' => 1,    // W_BOOKED
        'PRECONFIRMED' => 2,            // BOOKED AVIA
        'CONFIRMED' => 2,               // BOOKED HOTEL
        'PENDING_TICKETING' => 5,       // PAID
        'PENDING TICKETING' => 5,       // PAID
        'ISSUED' => 8,                  // DONE
        'REJECTED' => 6,                // CANCELLED
        'CANCELLATION PENDING' => 6,    // CANCELLED
        'CANCELLATION_PENDING' => 6,    // CANCELLED
        'CANCELED' => 7,                // VOIDED
        'CANCELLED' => 7,               // VOIDED
        'ERROR' => 9,                    // MANUAL
        'FORM.PRINT VOUCHER' => 2,       // BOOKED HOTEL  => 2,               // BOOKED HOTEL
        'FORM.PRECONFIRMED ON UPDATE' => 2 // BOOKED AVIA  => 2
    ];

    /**
     * Соответствие статусов услуги GPTS - КТ для Hotel
     * @param
     * @returm
     */
    private static $statusGPTSSubService = [
        'PENDING_CONFIRMATION' => 1,    // W_BOOKED
        'CONFIRMED' => 2,               // BOOKED HOTEL
        'REJECTED' => 6,                // VOIDED
    ];


    /**
     * Соответствие статусов заявки из GPTS в КТ
     * @param
     * @returm
     */
    private static $statusGPTSOrder = [
        'IN_PROGRESS' => 0, //NEW
        'COMPLETED' => 9,   //DONE
        'REJECTED' => 4,     //ANNULED
        'CANCELLED' => 4     //ANNULED
    ];
    /**
     * Данные модифицированного сервиса
     * @var array
     */
    private $modifiedTourists = [];

    public function __construct(&$module)
    {
        $this->module = $module;
        $this->apiClient = new GPTSApiClient($module->getConfig(), $module);
    }
    
    /**
    * Возвращает начало пути для изображений собственных отелей. 
    * Итоговая ссылка должна выглядеть так: 
    * $prefix . $imgSize . '/' + $url
    * где $prefix - начало пути, возвращаемое данным методом, 
    * $imgSize - желаемая ширина изображения,
    * $url - часть url, присланная в hotelInfo
    * @return {string} - начало пути
    */
    public function getOwnHotelsImagePath() {
        $config = $this->module->getConfig();

        $currentConfig = (EnvironmentHelper::getEnvironmentType() == EnvironmentHelper::PRODUCTION) ?
            $config['prod_api'] : $config['test_api'];

        $apiUrl = $currentConfig['url'];

        return preg_replace('/api\/$/u', 'thumbnails/', $apiUrl);
    }

    private function calculateStatusGPTSService($gptsStatus)
    {
        $status = strtoupper(StdLib::nvl($gptsStatus, ''));
        if (isset(self::$statusGPTSService[$status])) {
            return self::$statusGPTSService[$status];
        } else {
            return 9;  // Если $gptsStatus статус неопределён возвращать MANUAL статус
        }
    }

    /**
     * Маппинг статуса GPTS -> KT для доп услуг
     * @param $gptsStatus
     * @return mixed
     */
    private function calculateStatusGPTSSubService($gptsStatus)
    {
        $status = strtoupper(StdLib::nvl($gptsStatus, ''));
        if (isset(self::$statusGPTSSubService[$status])) {
            return self::$statusGPTSSubService[$status];
        } else {
            return null;
        }
    }

    /**
     * Принудительная аутентификация
     */
    public function reAuthenticate($retry = 0)
    {
        $this->apiClient->getAuthToken($retry);
    }

    /**
     * Вызов команды API GPTS
     * @param string $am - раздел API GPTS
     * @param string $command - команда API
     * @param mixed[] $params - параметры вызова команды
     * @param bool $stream - нужно ли вернуть stream или объект данных
     * @return mixed[] результат работы команды
     */
    public function runApiCommand(
        $am,
        $command,
        $params = [],
        $stream = false,
        $lang = false,
        $retry = 0
    )
    {
        try {
            $am = $am . 'Api';
            $apimodule = new $am($this->apiClient);
        } catch (Exception $e) {
            throw new KmpException(
                get_class(), __FUNCTION__,
                SupplierErrors::API_MODULE_NOT_FOUND,
                ['api' => $am]
            );
        }

        if (is_callable([$apimodule, $command])) {
            try {
                $response = $apimodule->$command($params, $stream, $lang, $retry);
            } catch (KmpException $ke) {
                throw $ke;
            } catch (Exception $e) {
                throw new KmpException(
                    get_class(), __FUNCTION__,
                    SupplierErrors::API_REQUEST_ERROR,
                    ['api' => $am, 'api method' => $command, 'msg' => $e->getMessage()]
                );
            }
            return $response;
        } else {
            throw new KmpException(
                get_class(), __FUNCTION__,
                SupplierErrors::API_METHOD_NOT_FOUND,
                ['api' => $am, 'api method' => $command]
            );
        }
    }

    /**
     *
     * @param $params
     * @param $cancelRules
     * @throws KmpException
     * @return array
     */
    private function modifyCancelRules($cancelRules, $params)
    {
        $modifyRules = [];
        $viewCurrency = StdLib::nvl($params['viewCurrency']);

        $cancelPenaltiesInfo = new CancelPenaltiesInfo();
        // Правила
        foreach ($cancelRules as $cancelRule) {
            $cancelPenaltiesInfo->addCancelRules($cancelRule);
            // Валюта
            $supplierCurrency = StdLib::nvl($cancelRule['price']['currency']);
            if (empty($supplierCurrency)) {
                throw new KmpException(
                    get_class(), __FUNCTION__,
                    SupplierErrors::SUPPLIERCURRENCY_NOT_FOUND,
                    $cancelRules
                );
            }
            try {
                // Если в параметрах имеется валюта просмотра:
                if (isset($viewCurrency) && !is_null(CurrencyStorage::findByString($viewCurrency))) {
                    $cancelPenaltiesInfo->addCurrency('view', CurrencyStorage::findByString($viewCurrency));
                }
                $cancelPenaltiesInfo->addCurrency('local', CurrencyStorage::findByString(643));
                $cancelPenaltiesInfo->addCurrency('supplier', CurrencyStorage::findByString($supplierCurrency));
            } catch (Exception $e) {
                return $e->getCode();
            }
            // Пересчёт
            $modifyRules = $cancelPenaltiesInfo->getCancelRules();
        }
        return $modifyRules;
    }

    /**
     * Получить данные по штрафам
     * @param mixed[] $params структура запроса getOffer
     * @return array|int структура ответа
     */
    public function getCancelRules($params)
    {
        $CancellationApi = new CancellationApi($this->apiClient);
        try {
            $response = $CancellationApi->conditions($params);
        } catch (Exception $e) {
            return $e->getCode();
        }

        return $this->modifyCancelRules($response, $params);
    }


    /**
     * Отмена брони для всех услуг
     * @param array $params
     * @return array|int структура ответа
     */
    public function serviceCancel($params)
    {
        $result = [];

        $ServiceBookData = ServiceBookDataFactory::getServiceBookDataByServiceName($this->getServiceNameByType($params['serviceType']));
        $ServiceBookData->fromArray($params['bookData']);

        // только для отелей проверим штрафы
        if ($params['serviceType'] == 1) {
            // проверка изменились ли штрафы за услугу вызовом orders
            $orderParams = [
                'orderId' => $ServiceBookData->getGPTSOrderId(),
                'orderType' => 'TO'
            ];

            $ordersApi = new OrdersApi($this->apiClient);
            $GPOrder = new GPOrders($ordersApi->orders_get($orderParams));

            $gpClientCancelPenalties = $GPOrder->getServiceClientCancelPenalties($ServiceBookData->getGPTSServiceRef());

            if (isset($params['bookData']['cancelPenalties']['client']) && !$this->isCancelPenaltiesEqual($gpClientCancelPenalties, $params['bookData']['cancelPenalties']['client'])) {
                $result['cancelResult'] = 1;
                $result['cancelData'] = 'Штрафы за отмену изменились, необходимо подтверждение отмены и согласия со штрафами';
                $result['newPenalties'] = [
                    'client' => $gpClientCancelPenalties,
                    'supplier' => $GPOrder->getServiceSupplierCancelPenalties($ServiceBookData->getGPTSServiceRef())
                ];
                return $result;
            }
        }

        // делаем отмену брони
        $CancellationApi = new CancellationApi($this->apiClient);

        try {
            $gptsResponse = $CancellationApi->cancelService([
                'processId' => $ServiceBookData->getGPTSServiceRef()
            ]);
        } catch (KmpException $ke) {
            LogHelper::logExt(
                get_class($this),
                'serviceCancel',
                'Отмена брони',
                "При отмене брони ГПТС ответил ошибку: {$ke->getMessage()}",
                $ke->params,
                'error',
                'system.supplierservice.*'
            );

            return $ke->getCode();
        }

        if (isset($gptsResponse['status']) && $gptsResponse['status'] == 'Canceled') {
            $result['cancelResult'] = 0;
            $result['cancelData'] = null;
            $result['newPenalties'] = null;
            return $result;
        } else {
            LogHelper::logExt(
                get_class($this),
                'serviceCancel',
                'Отмена брони',
                "При отмене брони ГПТС ответил неккоректно",
                [
                    'response' => $gptsResponse,
                ]
                ,
                'error',
                'system.supplierservice.*'
            );

            return SupplierErrors::API_REQUEST_ERROR;
        }
    }

    /**
     *
     * @param $gpCancelPenalties
     * @param $ktCancelPenalties
     * @return bool
     */
    private function isCancelPenaltiesEqual($gpCancelPenalties, $ktCancelPenalties)
    {
        if (count($gpCancelPenalties) != count($ktCancelPenalties)) {
            return false;
        }

        foreach ($gpCancelPenalties as $gpCancelPenalty) {
            // флаг, который узкавыет, что конкретный штраф GP совпал с нашим
            $isMatchedPenalty = false;

            $GPFromDate = (new DateTime($gpCancelPenalty['dateFrom']))->format('Y-m-d');
            $GPToDate = (new DateTime($gpCancelPenalty['dateTo']))->format('Y-m-d');
            $GPAmountInRUB = CurrencyRates::getInstance()->calculateInCurrency($gpCancelPenalty['penalty']['amount'], $gpCancelPenalty['penalty']['currency'], 'RUB');

            foreach ($ktCancelPenalties as $ktCancelPenalty) {
                $KTFromDate = (new DateTime($ktCancelPenalty['dateFrom']))->format('Y-m-d');
                $KTToDate = (new DateTime($ktCancelPenalty['dateTo']))->format('Y-m-d');
                $KTAmountInRUB = CurrencyRates::getInstance()->calculateInCurrency($ktCancelPenalty['penalty']['amount'], $ktCancelPenalty['penalty']['currency'], 'RUB');

                if ($GPAmountInRUB == $KTAmountInRUB && $GPFromDate == $KTFromDate && $GPToDate == $KTToDate) {
                    $isMatchedPenalty = true;
                }
            }

            if (!$isMatchedPenalty) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получить информацию по офферу
     * @param mixed[] $params структура запроса getOffer
     * @return mixed[] структура ответа
     */
    public function getOffer($params)
    {
        $serviceManager = $this->getServiceManagerClass((int)$params['serviceType']);
        $offerInfo = $serviceManager->getOffer($params);

        return $offerInfo;
    }

    /**
     * Запуск процесса бронирования оффера
     * @param mixed[] $params структура запроса serviceBooking
     * @return mixed[] структура ответа
     */
    public function serviceBooking($params)
    {
        $serviceManager = $this->getServiceManagerClass((int)$params['serviceType']);
        $bookStatus = $serviceManager->serviceBooking($params);
        return $bookStatus;
    }

    /**
     * Запуск процесса выписки билета
     * @param $params
     * @return mixed
     */
    public function issueTickets($params)
    {
        $serviceManager = $this->getServiceManagerClass($params['serviceType']);
        $ticketsInfo = $serviceManager->issueTickets($params);

        return $ticketsInfo;
    }

    /**
     * Запуск процесса получения маршрутной квитанции
     * @param $params
     */
    public function getEtickets($params)
    {
        $serviceManager = $this->getServiceManagerClass($params['serviceType']);
        return $serviceManager->getEtickets($params);
    }

    /**
     * Получение правил тарифа для оффера.
     * @param array параметры команды
     * @return array правила тарифа
     */
    public function getFareRule($params)
    {
        // Команда определена только для услуги авиаперелета
        $serviceManager = $this->getServiceManagerClass(2);
        return $serviceManager->getFareRule($params);
    }

    /**
     * Функция предназначена для проверки статуса услуги у поставщика
     * @param array $params команды
     * @return array правила тарифа
     */
    public function getServiceStatus($params)
    {
        if (!empty($params['inServiceData'])) {
            $outServiceData = [];

            foreach ($params['inServiceData'] as $inServiceData) {
                if (empty($inServiceData['serviceId']) || empty($inServiceData['serviceType']) || empty($inServiceData['serviceData'])) {
                    throw new KmpException(
                        get_class(), __FUNCTION__,
                        SupplierErrors::INPUT_PARAMS_ERROR,
                        ['serviceType' => $params]
                    );
                }

                $serviceName = $this->getServiceNameByType($inServiceData['serviceType']);
                $ServiceBookData = ServiceBookDataFactory::getServiceBookDataByServiceName($serviceName);
                $ServiceBookData->fromArray($inServiceData['serviceData']);

                $serviceRef = $ServiceBookData->getGPTSServiceRef();

                $BookingApi = new BookingApi($this->apiClient);
                $checkStatusResponse = $BookingApi->checkStatus([
                    'processId' => $serviceRef
                ]);

                if (!is_array($checkStatusResponse)) {
                    throw new KmpException(
                        get_class(), __FUNCTION__,
                        SupplierErrors::SUPPLIER_GET_SERVICE_STATUS_FAIL,
                        ['GPTSResponse' => $checkStatusResponse]
                    );
                }

                try {
                    $CheckStatusResponse = new CheckStatusResponse($checkStatusResponse);

                    $outServiceData[] = [
                        'serviceId' => $inServiceData['serviceId'],
                        'supplierServiceData' => $CheckStatusResponse->toArray()
                    ];
                } catch (InvalidArgumentException $ke) {
                    throw new KmpException(
                        get_class(), __FUNCTION__,
                        SupplierErrors::SUPPLIER_GET_SERVICE_STATUS_FAIL,
                        ['GPTSResponse' => $checkStatusResponse]
                    );
                }
            }

            return $outServiceData;
        } else {
            throw new KmpException(
                get_class(), __FUNCTION__,
                SupplierErrors::INPUT_PARAMS_ERROR,
                ['serviceType' => $params]
            );
        }
    }

    /**
     * Команда опроса статуса бронирования услуги через GPTS API
     * @param mixed[] $params - параметры команды
     * @return null
     */
    public function startBookingPoll($params)
    {
        LogHelper::logExt(
            get_class($this),
            'startBookingPoll',
            'Опрос на статус брони',
            '',
            $params,
            'trace',
            'system.supplierservice.trace'
        );

        $ServiceBookingPollClass = $this->getServiceBookingPoll($params['serviceType']);
        $ServiceBookingPollClass->init($this->apiClient);
        $BookData = $ServiceBookingPollClass->bookingPoll($params);

//        LogHelper::logExt(
//            get_class($this),
//            'startBookingPoll',
//            "Результат опроса брони, еще попыток {$params['bookingPollAttempts']}",
//            'Данные BookData',
//            $BookData->getBookDataArray(),
//            'trace',
//            'system.supplierservice.trace'
//        );

        if (is_null($BookData)) {
            // если есть еще попытки для опроса поставщика
            if ($params['bookingPollAttempts']) {
                $params['bookingPollAttempts']--;

                sleep($params['bookingPollAttemptTime']);
                $this->startBookingPoll($params);
                return;
            } else {
                $BookData = new BookData();
                $BookData->setWBooking();

                LogHelper::logExt(
                    get_class($this),
                    'Опрос статуса брони',
                    "Опрос завершился по окончанию числа попыток",
                    '',
                    '',
                    'trace',
                    'system.supplierservice.trace'
                );
            }
        }

        $bcparams = [
            'orderId' => (int)$params['orderId'],
            'action' => 'BookComplete',
            'actionParams' => $BookData->getBookDataArray(),
            'usertoken' => '0f7369671632f427'   // костыльный токен пользователя, который никогда не протухает
        ];

        LogHelper::logExt(
            get_class($this),
            'startBookingPoll',
            'Вызов OWM.BookComplete',
            "Параметры вызова OWM.BookComplete",
            $bcparams,
            'trace',
            'system.supplierservice.trace'
        );

        $supplierModule = YII::app()->getModule('supplierService');
        $apiClient = new ApiClient($supplierModule);
        $apiClient->makeRestRequest('orderService', 'OrderWorkflowManager', $bcparams);
    }

    /**
     * Создание соответствующего обработчика услуги
     * @param int $serviceType Id типа сервиса
     * @return ServiceManager Обработчик услуги
     * @throws KmpException ошибка создания обработчика
     */
    private function getServiceManagerClass($serviceType)
    {
        $serviceName = $this->getServiceNameByType($serviceType);
        $serviceManagerClassName = $serviceName . 'ServiceManager';

        if (class_exists($serviceManagerClassName)) {
            return new $serviceManagerClassName($this->module, $this->apiClient);
        } else {
            throw new KmpException(
                get_class(), __FUNCTION__,
                SupplierErrors::SERVICE_TYPE_NOT_DETERMINED,
                ['serviceType' => $serviceType]
            );
        }
    }

    /**
     * Получает название сервиса по коду
     * @param $serviceType
     * @return string
     */
    public function getServiceNameByType($serviceType)
    {
        if (!array_key_exists($serviceType, self::$serviceNames)) {
            throw new KmpException(
                get_class(), __FUNCTION__,
                SupplierErrors::SERVICE_TYPE_NOT_DETERMINED,
                ['serviceType' => $serviceType]
            );
        } else {
            return self::$serviceNames[$serviceType];
        }
    }

    /**
     * В зависимоти от типа сервиса разный запрос бронирования
     * @param $serviceType
     * @return AbstractServiceBookingPoll
     */
    private function getServiceBookingPoll($serviceType)
    {
        $serviceName = $this->getServiceNameByType($serviceType);
        $ServiceBookingPoll = $serviceName . 'BookingPoll';

        if (class_exists($ServiceBookingPoll)) {
            return new $ServiceBookingPoll();
        } else {
            throw new KmpException(
                get_class(), __FUNCTION__,
                SupplierErrors::SERVICE_TYPE_NOT_DETERMINED,
                ['serviceType' => $serviceType]
            );
        }
    }

    /**
     *
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function serviceModify($params)
    {
        $ServiceManager = $this->getServiceManagerClass((int)$params['serviceType']);

        if ($ServiceManager instanceof AccomodationServiceManager) {
            $ans = [
                'modifyResult' => 0,
                'bookData' => []
            ];

            try {
                $ans['modifyResult'] = 0;
                $ans['bookData']['newSalesTerms'] = $ServiceManager->serviceModify($params['serviceData']);
            } catch (ServiceModifyNotAvailableException $e) {
                // невозможно изменить бронь
                $ans['modifyResult'] = 2;

                LogHelper::logExt(
                    __CLASS__, __METHOD__,
                    'serviceModify', $e->getMessage(),
                    '',
                    'info',
                    'system.supplierservice.info'
                );
            } catch (KmpException $e) {
                LogHelper::logExt(
                    $e->__get('class'), $e->__get('method'),
                    'serviceModify', 'ГПТС ответил ошибку',
                    $e->__get('params'),
                    'error',
                    'system.supplierservice.*'
                );

                $ans['modifyResult'] = 1;
            } catch (Exception $e) {
                LogHelper::logExt(
                    __CLASS__, __METHOD__,
                    'serviceModify', $e->getMessage(),
                    '',
                    'error',
                    'system.supplierservice.*'
                );

                $ans['modifyResult'] = 1;
            }

            return $ans;
        } else {
            throw new Exception('Модификация бронирования открыта только для Отелей', SupplierErrors::MODIFY_SERVICE_FOR_HOTELS_ONLY);
        }
    }


    /**
     * Возвращает индекс ключа $strIndex в котором есть параметр $strParam (language="ru")
     * Иначе возвращает $retCode
     * @param $loc
     * @returm int
     */
    private function getIndexForParameters(array $locs, $strIndex, $strParam, $retCode = -1)
    {
        StdLib::nvl($locs, []);
        foreach ($locs as $key => $loc) {
            if (isset($loc[$strIndex]) && $loc[$strIndex] == $strParam) {
                return $key;
            }
        }
        return $retCode;
    }

    /**
     * Формирование результата по Отелю
     * @param
     * @returm
     */
    private function generateOutService_Hotel($ord, $servID, $kt_serviceID, $addServicesToCheck)
    {
        $orderService = [];
        $serviceTourists = [];
        $supplierServiceData = [];
        $hotelOffer = [];
        $tourists = [];
        $services = StdLib::nvl($ord['services'], []);
        $addServices = [];

        foreach ($services as $service) {
            if ($servID != $service['serviceId']) {
                continue;
            }
            // Ценообразователи и штрафы поставщика
            $salesTerm = StdLib::nvl($service['salesTerms']);
            $indSupplier = $this->getIndexForParameters($salesTerm, 'type', 'SUPPLIER', -1);
            $supplierSalesTerm = [
                'amountNetto' => StdLib::nvl($salesTerm[$indSupplier]['price']['amount']),    // Нетто-цена
                'amountBrutto' => StdLib::nvl($salesTerm[$indSupplier]['price']['amount']),    // Брутто-цена
                'currency' => StdLib::nvl($salesTerm[$indSupplier]['price']['currency']),       // Валюта предложения
                'commission' => [                 // Информация о комиссии (может быть в своей собственной валюте)
                    'currency' => StdLib::nvl($salesTerm[$indSupplier]['price']['currency']),   // Валюта комиссии
                    'amount' => StdLib::nvl($salesTerm[$indSupplier]['price'][''], 0),   // Сумма комиссии, является частью amountBrutto
                    'percent' => StdLib::nvl($salesTerm[$indSupplier]['price'][''], 0)    // 0 = commission.amount-абсолютная сумма, 1 = commission.amount- % от price.amountNetto
                ],
            ];

            $cancelPenalties = StdLib::nvl($salesTerm[$indSupplier]['cancelPenalty'], []);
            $priceSupplier = StdLib::nvl($salesTerm[$indSupplier]['price'], []);
            $supplierPenalty = [];
            foreach ($cancelPenalties as $cancelPenalty) {
                $supplierPenalty[] = [
                    'dateFrom' => StdLib::nvl($cancelPenalty['startDateTime']),             // Начало действия периода штрафа
                    'dateTo' => StdLib::nvl($cancelPenalty['endDateTime']),                 // Конец действия периода штрафа
                    'description' => StdLib::nvl($cancelPenalty['description']),            // Текст условий отмены
                    'penalty' => [
                        'currency' => StdLib::nvl($cancelPenalty['currency'], $priceSupplier['currency']),  // Валюта штрафа
                        'amount' => StdLib::nvl($cancelPenalty['amount'], $priceSupplier['amount']),        // Сумма штрафа
                        'commission' => [                                                                   // Данные штрафа клиента
                            'currency' => StdLib::nvl($cancelPenalty['commission']['currency']),          // Валюта штрафа
                            'amount' => StdLib::nvl($cancelPenalty['commission']['amountDue']),           // Сумма штрафа
                            'percent' => StdLib::nvl($cancelPenalty['commission']['commission'])          // Признак, в чём указан штраф, 1 = %, 0 = сумма
                        ]
                    ]
                ];
            };

            // Ценообразователи и штрафы слиента
            $indClient = $this->getIndexForParameters($salesTerm, 'type', 'CLIENT', -1);
            $clientSalesTerm = [
                'amountNetto' => StdLib::nvl($salesTerm[$indSupplier]['price']['amount'], 0) - StdLib::nvl($salesTerm[$indClient]['price']['commission'], 0),    // Нетто-цена
                'amountBrutto' => StdLib::nvl($salesTerm[$indClient]['price']['amount']),    // Брутто-цена
                'currency' => StdLib::nvl($salesTerm[$indClient]['price']['currency']),       // Валюта предложения
                'commission' => [                 // Информация о комиссии (может быть в своей собственной валюте)
                    'currency' => StdLib::nvl($salesTerm[$indClient]['price']['currency']),   // Валюта комиссии
                    'amount' => StdLib::nvl($salesTerm[$indClient]['price'][''], 0),   // Сумма комиссии, является частью amountBrutto
                    'percent' => StdLib::nvl($salesTerm[$indClient]['price'][''], 0)    // 0 = commission.amount-абсолютная сумма, 1 = commission.amount- % от price.amountNetto
                ]
            ];

            $cancelPenalties = StdLib::nvl($salesTerm[$indClient]['cancelPenalty'], []);
            $priceClient = StdLib::nvl($salesTerm[$indClient]['price'], []);
            $clientPenalty = [];
            foreach ($cancelPenalties as $cancelPenalty) {
                $clientPenalty[] = [
                    'dateFrom' => StdLib::nvl($cancelPenalty['startDateTime']),         // Начало действия периода штрафа
                    'dateTo' => StdLib::nvl($cancelPenalty['endDateTime']),             // Конец действия периода штрафа
                    'description' => StdLib::nvl($cancelPenalty['description']),    // Текст условий отмены
                    'penalty' => [
                        'currency' => StdLib::nvl($cancelPenalty['currency'], $priceClient['currency']),  // Валюта штрафа
                        'amount' => StdLib::nvl($cancelPenalty['amount'], $priceClient['amount']),        // Сумма штрафа
                        'commission' => [                                                           // Данные штрафа клиента
                            'currency' => StdLib::nvl($cancelPenalty['commission']['currency']),    // Валюта штрафа
                            'amount' => StdLib::nvl($cancelPenalty['commission']['amount']),        // Сумма штрафа
                            'percent' => StdLib::nvl($cancelPenalty['commission']['percent'])       // Признак, в чём указан штраф, 1 = %, 0 = сумма
                        ]
                    ]
                ];
            };

            $orderService = [
                'serviceId' => StdLib::nvl($kt_serviceID),               // Идентификатор сущности
                'serviceId_Utk' => StdLib::nvl($service['']),           // ID сервиса в УТК
                'serviceId_Gp' => StdLib::nvl($service['serviceId']),            // ID сервиса в GPTS
                'ktService' => StdLib::nvl($service['']),               // Признак порождения услуги в KT
                'status' => $this->calculateStatusGPTSService($service['status']),  // Статус
//                'status' => StatusMapper::getInstance()->getGptsByNameKtStatus(StdLib::nvl($service['status']), 4),

                'serviceType' => 1,  // Тип услуги
                'serviceName' => StdLib::nvl($service['serviceName']),  // Название (формируемое для UI)
                'offerId' => StdLib::nvl($service['']),                 // Идентификатор предложения (сохранённого в услуге)
                'dateStart' => StdLib::nvl($service['startDateTime']),  // Начало действия сервиса
                'dateFinish' => StdLib::nvl($service['endDateTime']),   // Окончание действия сервиса
                'dateAmend' => StdLib::nvl($service['']),               // Таймлимит на оплату
                'dateOrdered' => StdLib::nvl($service['']),             // Дата создания
                'agencyProfit' => StdLib::nvl($service['']),            // Комиссия агента в валюте себестоимости услуги

                'salesTerms' => [
                    "supplier" => $supplierSalesTerm,                   // штрафы поставщика, структуры ss_cancelPenalty
                    "client" => $clientSalesTerm,                       // штрафы для клиента , структуры ss_cancelPenalty
                ],

                'discount' => StdLib::nvl($service[''], 0),                // Предоставленная по услуге скидка (валюта продажи)
                'agreementSet' => StdLib::nvl($service['']),            // Признак, что клиент согласился с условиями оферты и тарифов
                'offline' => StdLib::nvl($service['']),                 // Признак офлайновой услуги
                'modificationAllowed' => StdLib::nvl($service['modificationAllowed']),     // Признак возможности модификации услуги
                'cancellationAllowed' => StdLib::nvl($service['cancellationAllowed']),     // Признак возможности отмены услуги

                'countryId' => CountriesMapperHelper::getCountryIdBySupplierId(5, StdLib::nvl($service['serviceDetails'][0]['countryId'], 0)),  // ID страны оказания услуги из справочника стран kt_ref_countries
                'cityId' => CitiesMapperHelper::getCityIdBySupplierCityID(5, StdLib::nvl($service['serviceDetails'][0]['cityId'], 0)),          // ID города оказания услуги из справочника стран kt_ref_cities

                'servicePenalties' => [                                 // Начисленные в услуге штрафы, структура so_servicePenalties
                    'description' => StdLib::nvl($service['']),
                    'supplier' => [
                        'localcurrency' => [
                            'amount' => StdLib::nvl($service['']),         // Сумма штрафа в локальной валюте
                            'currency' => StdLib::nvl($service['']),     // локальная валюта
                        ],
                        'viewcurrency' => [
                            'amount' => StdLib::nvl($service['']),         // Сумма штрафа в запрашиваемой валюте
                            'currency' => StdLib::nvl($service['']),     // Валюта просмотра
                        ],
                        'paymentcurrency' => [
                            'amount' => StdLib::nvl($service['']),         // Сумма штрафа в валюте платежа
                            'currency' => StdLib::nvl($service['']),     // Валюта платежа
                        ]
                    ],                   // штрафы поставщика, структуры ss_cancelPenalty
                    'client' => [
                        'localcurrency' => [
                            'amount' => StdLib::nvl($service['']),         // Сумма штрафа в локальной валюте
                            'currency' => StdLib::nvl($service['']),     // локальная валюта
                        ],
                        'viewcurrency' => [
                            'amount' => StdLib::nvl($service['']),         // Сумма штрафа в запрашиваемой валюте
                            'currency' => StdLib::nvl($service['']),     // Валюта просмотра
                        ],
                        'paymentcurrency' => [
                            'amount' => StdLib::nvl($service['']),         // Сумма штрафа в валюте платежа
                            'currency' => StdLib::nvl($service['']),     // Валюта платежа
                        ]
                    ],                       // штрафы для клиента , структуры ss_cancelPenalty

                ],
                'addServices' => [],           //Массив дополнительных услуг
                'addServicesAvailable' => StdLib::nvl($service['']),     //Возможность добавления доп.услуги к основной: true - можно  добавить эту доп.услугу к основной; false - нельзя добавить эту доп.услугу к основной
            ];
            $supplierId = StdLib::nvl($service['supplierId'], 0);
            $travelers = StdLib::nvl($service['travelers'], []);
            $sostav['adults'] = 0;
            $sostav['children'] = 0;

            foreach ($travelers as $traveler) {
                $serviceTourists[] = StdLib::nvl($traveler['travelerId']);

                if (StdLib::nvl($traveler['isChild'], false) == true) {
                    $sostav['children'] = $sostav['children'] + 1;
                } else {
                    $sostav['adults'] = $sostav['adults'] + 1;
                }

            }
            // Код поставщика из справочника поставщиков услуг kt_ref_suppliers
            $supplierId_KT = StdLib::nvl(SupplierHelper::getSupplierIdBySupplierGP(StdLib::nvl($service['supplierId'], 0)), 5);
            $serviceDetails = StdLib::nvl($service['serviceDetails'], []);
            foreach ($serviceDetails as $servDetail) {
                // поиск отеля в таблице ho_hotelMatch
                $hotel = null;
                if (isset($servDetail['hotelId'])) {
                    $hotel = HotelsMapperHelper::getHotelIDGromGPTS($servDetail['hotelId']);
                    if (empty($hotel) && isset($servDetail['hotelCode'])) {
                        $hotel = HotelsMapperHelper::getHotelInfoBySupplierHotelCode(5, $servDetail['hotelCode'], $servDetail['cityId']);
                    }
                }

                if (StdLib::nvl($hotel['hotelId'], 0) == 0) {
                    throw new KmpException(__CLASS__, __FUNCTION__,
                        SupplierErrors::HOTEL_NOT_FOUND,
                        $servDetail
                    );
                }

                $hotelOffer = [
                    'offerId' => StdLib::nvl($servDetail['']),                 // Идентификатор предложения
                    'supplierCode' => $supplierId_KT,
                    'offerKey' => StdLib::nvl($servDetail['']),                // Идентификатор предложения в шлюзе
                    'hotelId' => $hotel['hotelId'],
                    'dateFrom' => StdLib::nvl($service['startDateTime']),                // Дата заезда
                    'dateTo' => StdLib::nvl($service['endDateTime']),                  // Дата выезда
                    'salesTermsInfo' => [   // ценовые компоненты предложения в разных валютах, структура ss_salesTermsInfo
                        'supplierCurrency' => [                              // Ценовые компоненты услуги, структура     ss_salesTerms
                            'supplier' => $supplierSalesTerm,                   // штрафы поставщика, структуры ss_cancelPenalty
                            'client' => $clientSalesTerm,                       // штрафы для клиента , структуры ss_cancelPenalty
                        ]
                    ],
                    'salesTermsBreakdownInfo' => StdLib::nvl($servDetail['']), // (необязательное) ценовые компоненты предложения в разных валютах с разбивкой по дням, структура ss_salesTermsBreakdownInfo
                    'available' => StdLib::nvl($servDetail['mealTypeCode']),               // Доступно при поиске (false = проверка доступности при бронировании)
                    'specialOffer' => StdLib::nvl($servDetail['']),            // Признак специального предложения

                    'cancelAbility' => StdLib::nvl($service['cancellationAllowed']),           // Возможность отмены
                    'modifyAbility' => StdLib::nvl($service['modificationAllowed']),           // Возможность изменения брони

                    'adults' => $sostav['adults'],                  // Количество взрослых в номере
                    'children' => $sostav['children'],                // Количество детей в номере

                    'checkInTime' => StdLib::nvl($servDetail['checkIn']),             // Заезд после
                    'checkOutime' => StdLib::nvl($servDetail['checkOut']),             // Выезд до

                    'fareName' => StdLib::nvl($servDetail['']),                // Название тарифа (с версии GPTS 6.11)
                    'fareDescription' => StdLib::nvl($servDetail['']),         // Описание тарифа (с версии GPTS 6.11)

                    'mealOptionsAvailable' => StdLib::nvl($servDetail['']),    // Наличие доп.питания (с версии GPTS 6.11). false - отсутствует, true - есть в наличии.
                    'additionalMeal' => StdLib::nvl($servDetail['']),          // sl_addServiceOffer
                    'availableRooms' => StdLib::nvl($servDetail['']),          // Количество доступных номеров для данного предложения (с версии GPTS 6.11)
                    'travelPolicy' => StdLib::nvl($servDetail[''])             // Признаки корпоративных правил в предложении ss_TP_OfferValue
                ];
                $rooms = StdLib::nvl($servDetail['rooms']);
                $hotelOffer['roomType'] = StdLib::nvl($rooms[0]['roomTypeName']);            // Тип предложения (Можно использовать как имя)
                $hotelOffer['roomTypeDescription'] = StdLib::nvl($rooms[0]['roomTypeName']); // Описание типа предложения (Почему то всё совпадает с типом)
                $hotelOffer['mealType'] = StdLib::nvl($rooms[0]['mealTypeName']);            // Тип питания из справочника типов питания КТ
                $hotelOffer['roomServices'] = [];                                   // Массив услуг в номерах, структуры ss_roomService

                // проверим наличие и статусы доп услуг
                if (isset($servDetail['earlyCheckIn'])) {
                    foreach ($addServicesToCheck as $addServiceToCheck) {
                        if ($addServiceToCheck['serviceSubType'] == 2) { // Ранний заезд
                            $addServices[] = [
                                'addServiceId' => $addServiceToCheck['idAddService'],
                                'status' => $this->calculateStatusGPTSSubService($servDetail['earlyCheckIn']['status'])
                            ];
                        }
                    }
                }

                if (isset($servDetail['lateCheckOut'])) {
                    foreach ($addServicesToCheck as $addServiceToCheck) {
                        if ($addServiceToCheck['serviceSubType'] == 3) { // поздний выезд
                            $addServices[] = [
                                'addServiceId' => $addServiceToCheck['idAddService'],
                                'status' => $this->calculateStatusGPTSSubService($servDetail['lateCheckOut']['status'])
                            ];
                        }
                    }
                }
            }

            $orderService['addServices'] = $addServices;

            $cancelPenalties = [
                'supplier' => $supplierPenalty,  // штрафы поставщика, структуры ss_cancelPenalty
                'client' => $clientPenalty,      // штрафы для клиента , структуры ss_cancelPenalty
            ];

            $hotelVoucher = [
                'voucherID' => StdLib::nvl($service['']),               // Идентификатор ваучера
                'reservationId' => StdLib::nvl($service['refNum']),     // Идентификатор брони
                'serviceId' => StdLib::nvl($service['serviceId']),      // Идентификатор услуги
                'documentId' => StdLib::nvl($service['']),              // Идентификатор документа заявки, хранящего ваучер
                'receiptUrl' => StdLib::nvl($service[''])               //"http://kmp.travel/files/dvPRCZGYCdYc6hJO" // ссылка на файл ваучера, денормализованные данные из внешних/связанных сущностей (списка файлов заявки)
            ];

            $hotelReservations = [
                'reservationId' => StdLib::nvl($service['refNum']),                    // Идентификатор брони
                'tourists' => StdLib::nvl($serviceTourists, []),                     // Массив идентификаторов туристов, для которых создана бронь
                'reservationNumber' => StdLib::nvl($service['refNum']),             // Номер брони от поставщика
                'supplierCode' => StdLib::nvl($service['supplierCode']),            // идентификатор поставщика
                'status' => 1, // статус брони, 1 = Действует, 2 = Отменена
                'cancelAbility' => StdLib::nvl($service['cancellationAllowed']),    // возможность отменить бронь
                'modifyAbility' => StdLib::nvl($service['modificationAllowed']),    // возможность изменить данные брони
                'hotelVouchers' => $hotelVoucher,
                'addServicesReservations' => $addServices
            ];

            $supplierServiceData = [
                'hotelOffer' => $hotelOffer,                            // Предложение проживания, структура ss_hotelOffer
                'cancelPenalties' => $cancelPenalties,                  // Условия отмены (Штрафы за отмену), структура ss_cancelPenalties
                'hotelReservation' => $hotelReservations,                  // Брони проживания и ваучеры, массив структур so_hotelReservation
                'engineData' => [
//                    'reservationId' => StdLib::nvl($service['refNum']),   // Идентификатор брони
//                    'offerId' => StdLib::nvl($service['']),         // Идентификатор офера
//                    'offerKey' => StdLib::nvl($service['']),        // Ключ офера
//                    'gateId' => 5,                                  // тип внутреннего шлюза через который произведено бронирование услуги. 5 = GPTS (идентификаторы из kt_ref_gateways)
//                    'data' => [                                     // (необязательное) Данные шлюза для брони структура ss_еngineData
                    'GPTS_order_ref' => StdLib::nvl($ord['orderId']),
                    'GPTS_service_ref' => StdLib::nvl($service['processId'])
//                    ]
                ]               // Данные шлюзов для броней, структуры ss_engineData
            ];
        }

        return [
            'orderService' => $orderService,                // услуга, структура sl_orderService
            'serviceTourists' => $serviceTourists,           // Привязанные к услуге туристы
            'supplierServiceData' => $supplierServiceData   // Данные услуги, структура supplierServiceData
        ];
    }

    /**
     * Формирование результата по Авиа
     * @param
     * @returm
     *
     */
    private function generateOutService_Avia($ord, $servID, $kt_serviceID, $addServices)
    {
        $orderService = [];
        $serviceTourists = [];
        $supplierServiceData = [];
        $aviaOffer = [];
        $services = StdLib::nvl($ord['services'], []);

        foreach ($services as $service) {
            if ($servID != $service['serviceId']) {
                continue;
            }
            // Ценообразователи и штрафы поставщика
            $salesTerm = StdLib::nvl($service['salesTerms']);
            $indSupplier = $this->getIndexForParameters($salesTerm, 'type', 'SUPPLIER', -1);

            $supplierSalesTerm = [
                'amountNetto' => StdLib::nvl($salesTerm[$indSupplier]['price']['amount']),    // Нетто-цена
                'amountBrutto' => StdLib::nvl($salesTerm[$indSupplier]['price']['amount']),    // Брутто-цена
                'currency' => StdLib::nvl($salesTerm[$indSupplier]['price']['currency']),       // Валюта предложения
                'commission' => [                 // Информация о комиссии (может быть в своей собственной валюте)
                    'currency' => StdLib::nvl($salesTerm[$indSupplier]['price']['currency']),   // Валюта комиссии
                    'amount' => StdLib::nvl($salesTerm[$indSupplier]['price']['amount'], 0),   // Сумма комиссии, является частью amountBrutto
                    'percent' => StdLib::nvl($salesTerm[$indSupplier]['price']['percent'], 0)    // 0 = commission.amount-абсолютная сумма, 1 = commission.amount- % от price.amountNetto
                ],
            ];

            $cancelPenalties = StdLib::nvl($salesTerm[$indSupplier]['cancelPenalty'], []);
            $priceSupplier = StdLib::nvl($salesTerm[$indSupplier]['price'], []);
            $supplierPenalty = [];

            foreach ($cancelPenalties as $cancelPenalty) {
                $supplierPenalty[] = [
                    'dateFrom' => StdLib::nvl($cancelPenalty['startDateTime']),             // Начало действия периода штрафа
                    'dateTo' => StdLib::nvl($cancelPenalty['endDateTime']),                 // Конец действия периода штрафа
                    'description' => StdLib::nvl($cancelPenalty['description']),            // Текст условий отмены
                    'penalty' => [
                        'currency' => StdLib::nvl($cancelPenalty['currency'], $priceSupplier['currency']),  // Валюта штрафа
                        'amount' => StdLib::nvl($cancelPenalty['amount'], $priceSupplier['amount']),        // Сумма штрафа
                        'commission' => [                                                                   // Данные штрафа клиента
                            'currency' => StdLib::nvl($cancelPenalty['commission']['currency']),          // Валюта штрафа
                            'amount' => StdLib::nvl($cancelPenalty['commission']['amountDue']),           // Сумма штрафа
                            'percent' => StdLib::nvl($cancelPenalty['commission']['commission'])          // Признак, в чём указан штраф, 1 = %, 0 = сумма
                        ]
                    ]
                ];
            };

            // Ценообразователи и штрафы слиента
            $indClient = $this->getIndexForParameters($salesTerm, 'type', 'CLIENT', -1);
            $clientSalesTerm = [
                'amountNetto' => StdLib::nvl($salesTerm[$indSupplier]['price']['amount'], 0) - StdLib::nvl($salesTerm[$indClient]['price']['commission'], 0),    // Нетто-цена
                'amountBrutto' => StdLib::nvl($salesTerm[$indClient]['price']['amount']),        // Брутто-цена
                'currency' => StdLib::nvl($salesTerm[$indClient]['price']['currency']),         // Валюта предложения
                'commission' => [                                                               // Информация о комиссии (может быть в своей собственной валюте)
                    'currency' => StdLib::nvl($salesTerm[$indClient]['price']['currency']),     // Валюта комиссии
                    'amount' => StdLib::nvl($salesTerm[$indClient]['price']['amount'], 0),      // Сумма комиссии, является частью amountBrutto
                    'percent' => StdLib::nvl($salesTerm[$indClient]['price']['percent'], 0)      // 0 = commission.amount-абсолютная сумма, 1 = commission.amount- % от price.amountNetto
                ]
            ];

            $cancelPenalties = StdLib::nvl($salesTerm[$indClient]['cancelPenalty'], []);
            $priceClient = StdLib::nvl($salesTerm[$indClient]['price'], []);
            $clientPenalty = [];

            foreach ($cancelPenalties as $cancelPenalty) {
                $clientPenalty[] = [
                    'dateFrom' => StdLib::nvl($cancelPenalty['startDateTime']),         // Начало действия периода штрафа
                    'dateTo' => StdLib::nvl($cancelPenalty['endDateTime']),             // Конец действия периода штрафа
                    'description' => StdLib::nvl($cancelPenalty['description']),    // Текст условий отмены
                    'penalty' => [
                        'currency' => StdLib::nvl($cancelPenalty['currency'], $priceClient['currency']),  // Валюта штрафа
                        'amount' => StdLib::nvl($cancelPenalty['amount'], $priceClient['amount']),        // Сумма штрафа
                        'commission' => [                                                           // Данные штрафа клиента
                            'currency' => StdLib::nvl($cancelPenalty['commission']['currency']),    // Валюта штрафа
                            'amount' => StdLib::nvl($cancelPenalty['commission']['amount']),        // Сумма штрафа
                            'percent' => StdLib::nvl($cancelPenalty['commission']['percent'])       // Признак, в чём указан штраф, 1 = %, 0 = сумма
                        ]
                    ]
                ];
            };

            $orderService = [
                'serviceId' => StdLib::nvl($kt_serviceID),               // Идентификатор сущности
                'serviceId_Utk' => StdLib::nvl($service['serviceId_Utk']),           // ID сервиса в УТК
                'serviceId_Gp' => StdLib::nvl($service['serviceId']),            // ID сервиса в GPTS
                'ktService' => StdLib::nvl($service['ktService']),               // Признак порождения услуги в KT
                'status' => $this->calculateStatusGPTSService($service['status']),  // Статус
//                'status' => StatusMapper::getInstance()->getGptsByNameKtStatus(StdLib::nvl($service['status']), 4),
                'serviceType' => 2,  // Тип услуги
                'serviceName' => StdLib::nvl($service['serviceName']),  // Название (формируемое для UI)
                'offerId' => StdLib::nvl($service['offerId']),                 // Идентификатор предложения (сохранённого в услуге)
                'dateStart' => StdLib::nvl($service['startDateTime']),  // Начало действия сервиса
                'dateFinish' => StdLib::nvl($service['endDateTime']),   // Окончание действия сервиса
                'dateAmend' => StdLib::nvl($service['dateAmend']),               // Таймлимит на оплату
                'dateOrdered' => StdLib::nvl($service['dateOrdered']),             // Дата создания
                'agencyProfit' => StdLib::nvl($service['agencyProfit']),            // Комиссия агента в валюте себестоимости услуги
                'salesTerms' => [                                       // Ценовые компоненты услуги, структура     ss_salesTerms
                    "supplier" => $supplierSalesTerm,                   // штрафы поставщика, структуры ss_cancelPenalty
                    "client" => $clientSalesTerm,                       // штрафы для клиента , структуры ss_cancelPenalty
                ],
                'discount' => StdLib::nvl($service['discount'], 0),                // Предоставленная по услуге скидка (валюта продажи)
                'agreementSet' => StdLib::nvl($service['agreementSet']),            // Признак, что клиент согласился с условиями оферты и тарифов
                'offline' => StdLib::nvl($service['offline']),                 // Признак офлайновой услуги
                'modificationAllowed' => StdLib::nvl($service['modificationAllowed']),     // Признак возможности модификации услуги
                'cancellationAllowed' => StdLib::nvl($service['cancellationAllowed']),     // Признак возможности отмены услуги
                'countryId' => CountriesMapperHelper::getCountryIdBySupplierId(5, StdLib::nvl($service['serviceDetails'][0]['countryId'])),  // ID страны оказания услуги из справочника стран kt_ref_countries
                'cityId' => CitiesMapperHelper::getCityIdBySupplierCityID(5, StdLib::nvl($service['serviceDetails'][0]['cityId'])),          // ID города оказания услуги из справочника стран kt_ref_cities
                'servicePenalties' => [// Начисленные в услуге штрафы, структура so_servicePenalties
                    "description" => StdLib::nvl($service['']),
                    "supplier" => [
                        "localcurrency" => [
                            "amount" => StdLib::nvl($service['']),         // Сумма штрафа в локальной валюте
                            "currency" => StdLib::nvl($service['']),     // локальная валюта
                        ],
                        "viewcurrency" => [
                            "amount" => StdLib::nvl($service['']),         // Сумма штрафа в запрашиваемой валюте
                            "currency" => StdLib::nvl($service['']),     // Валюта просмотра
                        ],
                        "paymentcurrency" => [
                            "amount" => StdLib::nvl($service['']),         // Сумма штрафа в валюте платежа
                            "currency" => StdLib::nvl($service['']),     // Валюта платежа
                        ]
                    ],                   // штрафы поставщика, структуры ss_cancelPenalty
                    "client" => [
                        "localcurrency" => [
                            "amount" => StdLib::nvl($service['']),         // Сумма штрафа в локальной валюте
                            "currency" => StdLib::nvl($service['']),     // локальная валюта
                        ],
                        "viewcurrency" => [
                            "amount" => StdLib::nvl($service['']),         // Сумма штрафа в запрашиваемой валюте
                            "currency" => StdLib::nvl($service['']),     // Валюта просмотра
                        ],
                        "paymentcurrency" => [
                            "amount" => StdLib::nvl($service['']),         // Сумма штрафа в валюте платежа
                            "currency" => StdLib::nvl($service['']),     // Валюта платежа
                        ]
                    ],                       // штрафы для клиента , структуры ss_cancelPenalty
                ],
                'addService' => StdLib::nvl($service['addService'], []),           //Массив дополнительных услуг
                'addServicesAvailable' => StdLib::nvl($service['addServicesAvailable']),     //Возможность добавления доп.услуги к основной: true - можно  добавить эту доп.услугу к основной; false - нельзя добавить эту доп.услугу к основной
            ];

            $supplierId = StdLib::nvl($service['supplierId'], 0);
            $travelers = StdLib::nvl($service['travelers'], []);
            $sostav['adults'] = 0;
            $sostav['children'] = 0;
            $sostav['infant'] = 0;
            $sostav['InfantWithPlace'] = 0;

            foreach ($travelers as $traveler) {
                $serviceTourists[] = StdLib::nvl($traveler['travelerId']);
                if (StdLib::nvl($traveler['isChild'], false) == true) {
                    $sostav['children'] = $sostav['children'] + 1;
                } else {
                    $sostav['adults'] = $sostav['adults'] + 1;
                }
            }

            $tickets = [];
            $aviaTicketReceipt = [];

            $serviceDetails = StdLib::nvl($service['serviceDetails'], []);

            foreach ($serviceDetails as $servDetail) {
                $aviaOffer = [
                    'offerId' => StdLib::nvl($servDetail['']),                 // Идентификатор предложения
                    'offerKey' => StdLib::nvl($servDetail['']),                // Идентификатор предложения в шлюзе
                    'supplierCode' => SupplierHelper::getSupplierIdBySupplierGP(StdLib::nvl($service['supplierId'], 0)), // Код поставщика из справочника поставщиков услуг kt_ref_suppliers
                    'touristsAges' => [ // данные о возрастном составе туристов в предложении
                        'adult' => $sostav['adults'], // Количество взрослых
                        'child' => $sostav['children'], // Количество детей
                        'infant' => $sostav['infant'], // Количество младенцев
                        'InfantWithPlace' => $sostav['InfantWithPlace']// Количество предоставляемых для младенцев мест
                    ],
                    'lastPayDate' => StdLib::nvl($servDetail['']),
                    'lastTicketingDate' => StdLib::nvl($servDetail['lastTicketingDate']),
                    'flightTariff' => StdLib::nvl($servDetail['']),
                    'fareType' => StdLib::nvl($servDetail['']),
                    'salesTermsInfo' => [   // ценовые компоненты предложения в разных валютах, структура ss_salesTermsInfo
                        'supplierCurrency' => [                              // Ценовые компоненты услуги, структура     ss_salesTerms
                            "supplier" => $supplierSalesTerm,                   // штрафы поставщика, структуры ss_cancelPenalty
                            "client" => $clientSalesTerm,                       // штрафы для клиента , структуры ss_cancelPenalty
                        ]
                    ],
                    'travelPolicy' => [ // Признаки корпоративных правил в предложении ss_TP_OfferValue
                        'minimalPrice' => StdLib::nvl($servDetail['']), // true,
                        'travelPolicyFailCodes' => StdLib::nvl($servDetail['']), //["Code 100",...,"Code 201"],
                        'priorityOffer' => StdLib::nvl($servDetail['']), // true,
                        'overnightFlight' => StdLib::nvl($servDetail['']), // true,
                        'nightTransfer' => StdLib::nvl($servDetail['']) //true
                    ]
                ];

                // ITINERARY
                $flightSegments = [];
                $itinerarie = [];

                $itineraries = StdLib::nvl($servDetail['itineraries'], []);
                foreach ($itineraries as $itiner) {
                    $flSegments = StdLib::nvl($itiner['flightSegments'], []);
                    $segmentNum = 1;
                    $flightSegments = [];

                    foreach ($flSegments as $flSegment) {
                        $flightSegmentName = StdLib::nvl($flSegment['departureAirportCode']) . '-' . StdLib::nvl($flSegment['arrivalAirportCode']);
                        $duration = intval(abs(strtotime(StdLib::nvl($flSegment['arrivalDate'], 0)) - strtotime(StdLib::nvl($flSegment['departureDate'], 0))) / 60);

                        $stops = [];
                        if (!empty($flSegment['stopLocations'])) {
                            foreach ($flSegment['stopLocations'] as $stop) {
                                $stops[] = [
                                    'stopAirportCode' => StdLib::nvl($flSegment['stopAirportCode']),  // Код аэропорта остановки
                                    'stopDuration' => StdLib::nvl($flSegment['stopDuration']) // Длительность остановки
                                ];
                            }
                        }

                        $flightSegments[] = [
                            'tripId' => null,                    // Идентификатор трипа
                            'flightSegmentName' => $flightSegmentName,                  // : "MOW-MEL"// Название сегмента
                            'segmentNum' => $segmentNum++,                              // Номер сегмента в трипе
                            'validatingAirline' => StdLib::nvl($servDetail['validatingAirlines'][0]),         // "EK", // Код валидирующей авиакомпании
                            'marketingAirline' => StdLib::nvl($flSegment['marketingAirline']),          // "EK", // Код маркетинговой авиакомпании
                            'operatingAirline' => StdLib::nvl($flSegment['operatingAirline']),          // "EK", // Код оперирующей авиакомпании
                            'flightNumber' => StdLib::nvl($flSegment['flightNumber']),              // "134", // Номер рейса
                            'aircraftName' => StdLib::nvl($flSegment['aircraftName']), // "Boening-747", // Название самолёта
                            'aircraftCode' => StdLib::nvl($flSegment['aircraftCode']),
                            'categoryClass' => [
                                'classType' => !empty($flSegment['serviceClass'])
                                    ? strtoupper($flSegment['serviceClass']) : null, // "ECONOMY", Класс обслуживания
                                'code' => $flSegment['bookingClass']
                            ],
                            'duration' => StdLib::nvl($duration, 0),                  //315, // Длительность сегмента в минутах
                            'departureAirportCode' => StdLib::nvl($flSegment['departureAirportCode']),      // "DME", // Код аэропорта отправления
                            'departureCityName' => StdLib::nvl($flSegment['']),        // "Moscow", // Город отправления
                            'departureDate' => StdLib::nvl($flSegment['departureDate']),             // "2016-02-29T16:40", // дата/время вылета "YYYY-MM-DDT16:40:00.000+03:00"  по рекомендуемому в js стандарту ISO 8601   https://www.ietf.org/rfc/rfc3339.txt
                            'departureTerminal' => StdLib::nvl($flSegment['departureTerminal']),         // Терминал аэропорта отправления
                            'arrivalAirportCode' => StdLib::nvl($flSegment['arrivalAirportCode']),        //  "DXB", // Код аэропорта прибытия
                            'arrivalCityName' => StdLib::nvl($flSegment['']),           // "Dubai", // Город прибытия
                            'arrivalDate' => StdLib::nvl($flSegment['arrivalDate']),               // "2016-02-29T22:55", // дата/время прибытия
                            'arrivalTerminal' => StdLib::nvl($flSegment['arrivalTerminal']),           // "3", // Терминал аэропорта прибытия
                            'mealCode' => StdLib::nvl($flSegment['mealCode']),                  // "LUNCH", // Код предоставляемого питания (код из справочника авиа-питаний fl_meal)
                            'baggageMeasureCode' => StdLib::nvl($flSegment['baggageMeasureCode']),        // "kg", // Единицы измерения багажа
                            'baggageMeasureQuantity' => StdLib::nvl($flSegment['baggageMeasureQuantity']),    // 20, // Допустимое количество багажа
                            'stopQuantity' => StdLib::nvl($flSegment['stopQuantity']),             // 1, // Количество остановок
                            'stops' => $stops
                        ];
                    }

                    $itinerarie[] = [
                        'tripID' => StdLib::nvl($servDetail['']),               // Идентификатор трипа
                        'routeName' => StdLib::nvl($service['serviceName']),    //$routeName['departureAirportCode'] .'-'. $routeName['arrivalAirportCode'],                                      // "DME - MEL", // Название трипа = коды аэропортов начальной и конечной точки трипа. Вычисляемый атрибут.
                        'duration' => StdLib::nvl($servDetail['']),             // Продолжительность трипа в минутах (все перелёты + остановки + пересадки)
                        'segments' => $flightSegments
                    ];
                }

                $aviaOffer['itinerary'] = $itinerarie;
                $tickets = StdLib::nvl($servDetail['tickets']);

                $aviaTicketReceipt = [
                    'ticketNumbers' => StdLib::nvl($servDetail['']), // ["550-3467876","550-234234"], // Номера билетов, для которых создана квитанция
                    'serviceId' => StdLib::nvl($servDetail['']),     // Идентификатор услуги
                    'documentId' => StdLib::nvl($servDetail['']),    // Идентификатор документа заявки, хранящего МК
                    'receiptUrl' => StdLib::nvl($servDetail[''])      //"http://kmp.travel/gptour-test//api/downloadVoucher/?voucher=dvPRCZGYCdYc6hJO" // ссылка на файл маршрутной квитанции
                ];
            }

            $cancelPenalties = [
                "supplier" => $supplierPenalty,  // штрафы поставщика, структуры ss_cancelPenalty
                "client" => $clientPenalty,      // штрафы для клиента , структуры ss_cancelPenalty
            ];

            $firstSegment = $servDetail['itineraries'][0]['flightSegments'][0];
            $lastTrip = $servDetail['itineraries'][(count($servDetail['itineraries']) - 1)];
            $lastSegment = $lastTrip['flightSegments'][(count($lastTrip['flightSegments']) - 1)];
            $routeName = $firstSegment['departureAirportCode'] . ' - ' . $lastSegment['arrivalAirportCode'];

            $aviaReservations = [];
            $aviaReservations[] = [
                'PNR' => StdLib::nvl($service['refNum']), // Номер брони от поставщика
                'supplierCode' => StdLib::nvl($service['supplierCode']), // код поставщика
                'status' => 1, // статус: 1 = действует, 2 = Отменён
                'segments' => [
                    'tripID' => null,
                    'flightSegmentName' => $routeName
                ]
            ];

            $supplierServiceData = [
                'aviaOffer' => $aviaOffer,                            // Предложение проживания, структура ss_hotelOffer
                'cancelPenalties' => $cancelPenalties,                  // Условия отмены (Штрафы за отмену), структура ss_cancelPenalties
                'aviaReservations' => $aviaReservations,                  // Брони проживания и ваучеры, массив структур so_hotelReservation
                'engineData' => [
                    'reservationId' => StdLib::nvl($service['refNum']),   // Идентификатор брони
                    'offerId' => StdLib::nvl($service['']),         // Идентификатор офера
                    'offerKey' => StdLib::nvl($service['']),        // Ключ офера
                    'gateId' => 5,                                  // тип внутреннего шлюза через который произведено бронирование услуги. 5 = GPTS (идентификаторы из kt_ref_gateways)
                    'data' => [                                     // (необязательное) Данные шлюза для брони структура ss_еngineData
                        'GPTS_order_ref' => StdLib::nvl($ord['orderId']),
                        'GPTS_service_ref' => StdLib::nvl($service['processId'])
                    ]
                ],  // Данные шлюзов для броней, структуры ss_engineData
                'aviaTickets' => $tickets,
                'aviaTicketReceipts' => $aviaTicketReceipt
            ];
        }

        return [
            'orderService' => $orderService,                // услуга, структура sl_orderService
            'serviceTourists' => $serviceTourists,          // Привязанные к услуге туристы
            'supplierServiceData' => $supplierServiceData   // Данные услуги, структура supplierServiceData
        ];
    }

    private function generateOrder($ord)
    {
        $order = [
            'orderId' => null,                 // ID, он же номер заявки
            'orderId_Utk' => StdLib::nvl($ord['orderId_Utk']),             // № заявки в УТК
            'orderId_Gp' => StdLib::nvl($ord['orderId']),              // № заявки в GPTS
            'orderDate' => StdLib::nvl($ord['created']),               //Дата-время создания заявки
            'status' => self::$statusGPTSOrder[strtoupper(StdLib::nvl($ord['status'], ''))],  // Статус
            'agentId' => CompanyMapperHelper::getAgentByAgentIDGP(StdLib::nvl($ord['agentCompanyId']))['AgentID'],              // ID клиента
            'agencyName' => CompanyMapperHelper::getAgentByAgentIDGP(StdLib::nvl($ord['agentCompanyId']))['Name'],               // Название компании
            'userId' => UsersHelper::getUserIdByUserGP(StdLib::nvl($ord['agent']['id'], 0)),             // ID пользователя (KT), создавшего заявку
            'archive' => StdLib::nvl($ord['active'], 0) == 1 ? 0 : 1,                 // Признак архивной записи.
            'dolc' => StdLib::nvl($ord['dolc']),                    // Дата время последнего изменения заявки
            'vip' => StdLib::nvl($ord['vip']),                     // признак VIP-заявки
            'contractId' => StdLib::nvl($ord['contractId']),              // Идентификатор договора, для которого создана заявка
            'blocked' => StdLib::nvl($ord['blocked']),                 // Блокирующее поле. 0- заявка в работе ,  1 - заявка заблокирована
            'comment' => StdLib::nvl($ord['comment']),                 //  Комментарий к заявке
            'companyManager' => CompanyMapperHelper::getResponsManagerID(StdLib::nvl($ord['agentCompanyId'], 0))['CompanyManagerID'],          // Идентификатор менеджера компании для заявки
            'kmpManager' => CompanyMapperHelper::getResponsManagerID(StdLib::nvl($ord['agentCompanyId'], 0))['KMPManagerID']                       // Идентификатор менеджера КМП для заявки
        ];
        return $order;
    }

    private function generateTourists($ord)
    {
        $tourists = [];
        $tourist = [];
        $services = StdLib::nvl($ord['services'], []);
        foreach ($services as $service) {
            $supplierId = StdLib::nvl($service['supplierId'], 0);
            $travelers = StdLib::nvl($service['travelers'], []);
            foreach ($travelers as $traveler) {
                if ($this->getIndexForParameters($tourist, 'touristIdGP', $traveler['travelerId'], -1) > -1) {
                    continue;
                }

                $tourist = [
                    'touristId' => StdLib::nvl($traveler['']),           // ID туриста (убрал $traveler['travelerId'])
                    'touristIdUTK' => StdLib::nvl($traveler['']),        // ID туриста в УТК
                    'touristIdGP' => StdLib::nvl($traveler['travelerId']),        // ID туриста в УТК
                    'maleFemale' => (StdLib::nvl($traveler['prefix'], 'Mr') == 'Mr' ? 1 : 0),          // Пол туриста, в терминах КТ (kt_tourist_base)
                    'dateOfBirth' => StdLib::nvl($traveler['dateOfBirth']),  // ДР
                    'email' => StdLib::nvl($traveler['email']),                   // email
                    'phone' => StdLib::nvl($traveler['phone']),                   // телефон
                ];

                $names = StdLib::nvl($traveler['name']);
                $ind = $this->getIndexForParameters($names, 'language', 'ru', 0); // Ищет индекс русской локали, иначе = 0
                $tourist['firstName'] = StdLib::nvl($names[$ind]['firstName']);      // Имя
                $tourist['middleName'] = StdLib::nvl($names[$ind]['middleName']);  // Отчество
                $tourist['lastName'] = StdLib::nvl($names[$ind]['lastName']);      // Фамилия

                // Если поставщик A&A или Academservice то брать русскую локаль
                $ind = 0;
                $docType = 18; // Другой документ.
                if ($supplierId == 240 || $supplierId == 226) {
                    $ind = $this->getIndexForParameters($names, 'language', 'ru', 0); // Ищет индекс русской локали
                }

                $touristDocs = [
                    'touristId' => StdLib::nvl($traveler['']),     // ID туриста  (убрал $traveler['travelerId'])
                    'docType' => $docType,                                         // Тип документа из справочника типов документов kt_toursts_doc_type
                    'firstName' => StdLib::nvl($names[$ind]['firstName']),   // Имя
                    'middleName' => StdLib::nvl($names[$ind]['middleName']), // Отчество
                    'lastName' => StdLib::nvl($names[$ind]['lastName']),     // Фамилия
                    'docSerial' => StdLib::nvl($ord['']),           // Серия документа
                    'docNumber' => StdLib::nvl($traveler['passports'][0]['number']),           // номер документа
                    'docDate' => StdLib::nvl($ord['']),             // Дата выдачи
                    'docExpiryDate' => StdLib::nvl($traveler['passports'][0]['expiryDate']),       // Дата окончания действия
                    'issuedBy' => StdLib::nvl($ord['']),            // Кем выдан
                    'address' => StdLib::nvl($ord['']),             // Адрес регистрации
                    'citizenshipId' => CountriesMapperHelper::getCountryIdBySupplierId(5, StdLib::nvl($traveler['citizenshipId']))  // ID страны гражданства туриста (из справочника стран КТ kt_ref_countries)
                ];
                $tourist['touristdocs'] = $touristDocs;

                $tourists[] = $tourist;
            }
        }
        return $tourists;
    }

    /**
     * "suplierOrder": {
     * "gateId": 5,
     * "GPTS_order_ref": 12499,
     * "service": [
     * {
     * "serviceID": 44,
     * "serviceType": 1,
     * "GPTS_service_ref": 5555
     * },
     * {
     * "serviceID": 55,
     * "serviceType": 2,
     * "GPTS_service_ref": 6666
     * },
     * {
     * "serviceID": 77,
     * "serviceType": 2,
     * "GPTS_service_ref": 777
     * }
     * ]
     * }
     * @param
     * @returm
     */
    public function getSupplierGetOrder($params)
    {
        $services = $params['service'];
        $orderParams = [
            'orderId' => $params['GPTS_order_ref'],
            'orderType' => 'TO'
        ];

        $ordersApi = new OrdersApi($this->apiClient);
        $ords = $ordersApi->orders_get($orderParams);
        if (empty($ords)) {
            throw new KmpException(__CLASS__, __FUNCTION__,
                SupplierErrors::ORDER_NOT_FOUND,
                [
                    'GPTS_order_ref' => $params['GPTS_order_ref'],
                    'orderType' => 'TO'
                ]
            );
        }
        $ord = StdLib::nvl($ords[0]);
        $serviceInfo = [];
        // Цикл по услуге
        foreach ($services as $service) {
            // параметры услуги
            $ktServiceID = $service['serviceID'];
            $serviceType = $service['serviceType'];
            $gptsService = $service['GPTS_service_ref'];
            if ($serviceType == 1) {
                $serviceInfo[] = $this->generateOutService_Hotel($ord, $gptsService, $ktServiceID, $params['addServices']);
            } elseif ($serviceType == 2) {
                $serviceInfo[] = $this->generateOutService_Avia($ord, $gptsService, $ktServiceID, $params['addServices']);
            } else {
                $serviceInfo[] = null;
            }
        }
        $orderInfo['order'] = $this->generateOrder($ord);
        $orderInfo['tourists'] = $this->generateTourists($ord);
        $orderInfo['services'] = $serviceInfo;
        return $orderInfo;
    }

// ================================================================================================================

    private function fillSalesTerm($salesTermPars, $cancelPenalties)
    {
        $outPars = null;
        if (isset($salesTermPars)) {
            if (isset($salesTermPars['supplier'])) {
                $supplier = $salesTermPars['supplier'];
                $outPar['type'] = 'SUPPLIER';
                if (isset($supplier['amountBrutto'])) {
                    $outPar['price']['amount'] = $supplier['amountBrutto'];
// Вычисляемая наценка учитывается в Client
//                    $outPar['price']['markup']['percent'] = 1;//StdLib::nvl($supplier[''], 0);
//                    $outPar['price']['markup']['base'] = 10; //
                    //$outPar['price']['markup']['base'] = StdLib::nvl($supplier[''], 0);
                }
                if (isset($supplier['commission']['amount']))
                    $outPar['price']['commission']['amount'] = $supplier['commission']['amount'];
                if (isset($supplier['commission']['percent']))
                    $outPar['price']['commission']['percent'] = $supplier['commission']['percent'];

                // допишен штрафы в цены для ГПТС
                if (isset($cancelPenalties['supplier'])) {
                    foreach ($cancelPenalties['supplier'] as $cancelPenalty) {
                        $startDate = new DateTime($cancelPenalty['dateFrom']);
                        $endDate = new DateTime($cancelPenalty['dateTo']);

                        $outPar['cancelPenalty'][] = [
                            'amount' => $cancelPenalty['penalty']['amount'],
                            'startDateTime' => $startDate->format('Y-m-d\TH:i:s'),
                            'endDateTime' => $endDate->format('Y-m-d\TH:i:s')
                        ];
                    }
                }

                $outPars[] = $outPar;
                unset($outPar);
            }
            if (isset($salesTermPars['client'])) {
                $client = $salesTermPars['client'];
                $outPar['type'] = 'CLIENT';
                if (isset($client['amountBrutto']))
                    $outPar['price']['amount'] = $client['amountBrutto'];

                if (isset($supplier['amountBrutto']) && isset($client['amountBrutto'])) {
                    $outPar['price']['markup']['amount'] = null;
                    $outPar['price']['markup']['percent'] = null;
                    $outPar['price']['markup']['base'] = 'perOrder';
                }

                if (isset($client['commission']['amount']))
                    $outPar['price']['commission']['amount'] = $client['commission']['amount'];
                if (isset($client['commission']['percent']))
                    $outPar['price']['commission']['percent'] = $client['commission']['percent'];

                // допишен штрафы в цены для ГПТС
                if (isset($cancelPenalties['client'])) {
                    foreach ($cancelPenalties['client'] as $cancelPenalty) {
                        $startDate = new DateTime($cancelPenalty['dateFrom']);
                        $endDate = new DateTime($cancelPenalty['dateTo']);

                        $outPar['cancelPenalty'][] = [
                            'amount' => $cancelPenalty['penalty']['amount'],
                            'startDateTime' => $startDate->format('Y-m-d\TH:i:s'),
                            'endDateTime' => $endDate->format('Y-m-d\TH:i:s')
                        ];
                    }
                }

                $outPars[] = $outPar;
            }
        }
        return $outPars;
    }

    private function fillTravelers($travelersPars)
    {
        $outPars = null;
        if (isset($travelersPars)) {
            foreach ($travelersPars as $travelersPar) {
                $travalerId = StdLib::nvl($travelersPar['touristId']);
                $travalerIdGP = StdLib::nvl($travelersPar['touristIdGP']);

                if (empty($travalerId) || empty($travalerIdGP)) {
                    throw new KmpException(
                        get_class(), __FUNCTION__,
                        SupplierErrors::API_METHOD_NOT_FOUND,
                        ['touristIdGP' => $travalerIdGP, 'touristId' => $travalerId]
                    );
                }
                $outPar['travelerId'] = $travalerIdGP;
                if (isset($travelersPar['dateOfBirth']))
                    $outPar['type'] = StdLib::yarsFromBirthday($travelersPar['dateOfBirth']) > 17 ? 'adult' : 'child';
                if (isset($travelersPar['maleFemale']))
                    $outPar['prefix'] = ($travelersPar['maleFemale'] == 1) ? 'Mr' : 'Ms';       //‘Mr’, ‘Ms’ or ‘Mrs’
                if (isset($travelersPar['firstName']))
                    $outPar['firstName'] = $travelersPar['firstName'];
                if (isset($travelersPar['middleName']))
                    $outPar['middleName'] = $travelersPar['middleName'];
                if (isset($travelersPar['lastName']))
                    $outPar['lastName'] = $travelersPar['lastName'];
                if (isset($travelersPar['dateOfBirth']))
                    $outPar['dateOfBirth'] = $travelersPar['dateOfBirth'];
                if (isset($travelersPar['delete']))
                    $outPar['delete'] = $travelersPar['delete'];


                $outPars[] = $outPar;
                $outPar['touristId'] = $travalerId;
                $this->modifiedTourists[] = $outPar;

            }
        }
        return $outPars;
    }

    private function fillFlight($flights)
    {
        $outPars = null;
        if (isset($flights)) {
            $outPars['lastTicketingDate'] = StdLib::nvl($flights['lastTicketingDate']);
            foreach ($flights as $flight) {
                if (isset($flights['departureAirportCode']))
                    $outPars['segments']['departureAirportCode'] = $flights['departureAirportCode'];
                if (isset($flights['departureDate']))
                    $outPars['segments']['departureDate'] = $flights['departureDate'];
                if (isset($flights['arrivalAirportCode']))
                    $outPars['segments']['arrivalAirportCode'] = $flights['arrivalAirportCode'];
                if (isset($flights['arrivalDate']))
                    $outPars['segments']['arrivalDate'] = $flights['arrivalDate'];
            }
        }
        return $outPars;
    }

    /**
     * Преобразование входных параметров услуги в параметры GPTS
     * @param $par
     * @return array
     */
    private function fillGPTSParams($par)
    {
        $outPar = [];
        if (isset($par)) {
            if (isset($par['engineData'])) {
                $engineData = StdLib::nvl($par['engineData']);
                $outPar['processId'] = StdLib::nvl($engineData['data']['GPTS_service_ref'], 0);
//                $outPar['processId'] = StdLib::nvl($engineData['data'][0]['GPTS_processId'], 0);
            }

            if (isset($outPar['processId']) && isset($par['orderService'])) {
                $orderService = $par['orderService'];
                $serviceType = StdLib::nvl($orderService['serviceType'], 0);
                //$serviceId_Gp = StdLib::nvl($orderService['orderService'], 0);

                if ($serviceType == 1) {
                    $outPar['status'] = self::$statusServiceHotelKT_GPTS[StdLib::nvl($orderService['status'])];
                } elseif ($serviceType == 2) {
                    $outPar['status'] = self::$statusServiceAviaKT_GPTS[StdLib::nvl($orderService['status'])];
                }

                if (isset($par['supplierServiceData']['supplierReservationNum']))
                    $outPar['refNum'] = $par['supplierServiceData']['supplierReservationNum'];

                $fst = $this->fillSalesTerm(StdLib::nvl($orderService['salesTerms']), StdLib::nvl($orderService['cancelPenalties']));
                if (isset($fst))
                    $outPar['salesTerm'] = $fst;

                $ft = $this->fillTravelers(StdLib::nvl($par['tourists'], []));
                if (isset($ft))
                    $outPar['travelers'] = $ft;

                if (isset($par['sendNotification']))
                    $outPar['sendNotification'] = $par['sendNotification'];

                if (isset($par['orderService']['dateStart']))
                    $outPar['accommodation']['startDate'] = DateTime::createFromFormat('Y-m-d H:i:s', $par['orderService']['dateStart'])->format('Y-m-d\TH:i:s.u');

                if (isset($par['orderService']['dateFinish']))
                    $outPar['accommodation']['endDate'] = DateTime::createFromFormat('Y-m-d H:i:s', $par['orderService']['dateFinish'])->format('Y-m-d\TH:i:s.u');

                $ff = $this->fillFlight(StdLib::nvl($par['flight']));
                if (isset($ff))
                    $outPar['flight'] = $ff;

//                $outPar['excursion']['startDateTime'] = null;
//                $outPar['carRent']['startDateTime'] = null;
//                $outPar['carRent']['endDateTime'] = null;
//                $outPar['transfer']['startDateTime'] = null;
//                $outPar['transfer']['departure'] = null;
//                $outPar['transfer']['arrival'] = null;
//                $outPar['train']['startDateTime'] = null;
//                $outPar['train']['endDateTime'] = null;
//                $outPar['insurance']['startDateTime'] = null;
//                $outPar['insurance']['endDateTime'] = null;
//                $outPar['visa']['startDate'] = null;
            }
        }
        return $outPar;
    }


    /**
     * Поиск услуги в которой происходили изменения
     * @param $services
     * @param $processID
     * @return bool
     */
    private function getCurrentService($services, $processID)
    {
        foreach ($services as $service) {
            if ($service['processId'] == $processID) {
                return $service['travelers'];
            }
        }
        return false;
    }

    /**
     * Если $fieldName найден в $travelers со значением $val и в массиве $modifiedInto нет такого travelerId, то записываем его
     * @param $modifiedInto
     * @param $val
     * @param $travelers
     * @param $fieldName
     */
    private function findModification(&$modifiedInto, $val, $travelers, $fieldName)
    {
        foreach ($travelers as $traveler) {
            if ($traveler['name'][0][$fieldName] == $val) {
                if (!in_array($traveler['travelerId'], $modifiedInto)) {
                    $modifiedInto[] = $traveler['travelerId'];
                }
            }
        }
    }

    /**
     * Сохранение данных по туристу после модификации в GPTS в пределах услуги
     * @param array $retInfo - ответ из ГПТС
     */
    private function modifiedServiceDataAfterGPTSSaving(array $retInfo, $procID)
    {
        $newRelations = [];
        if (is_array($retInfo) && count($retInfo) > 0) {
            // Если модификация туриста есть
            if (count($this->modifiedTourists) > 0) {
                // По processID нахожу текущую услугу в ответе
                $travelers = $this->getCurrentService(StdLib::nvl($retInfo['services'], []), $procID);
                if ($travelers != false) { // Если нашлась услуга получаю список туристов
                    $modifiedInto = []; // Сюда складываю все touristIdGP туристов у которых есть модификация следующих полей
                    // Если модификация поля найдена, то заполняем $modifiedInto
                    foreach ($this->modifiedTourists as &$tourist) {
                        if (array_key_exists('lastName', $tourist)) {
                            $this->findModification($modifiedInto, StdLib::nvl($tourist['lastName']), $travelers, 'lastName');
                        }
                        if (array_key_exists('middleName', $tourist)) {
                            $this->findModification($modifiedInto, StdLib::nvl($tourist['middleName']), $travelers, 'middleName');
                        }
                        if (array_key_exists('firstName', $tourist)) {
                            $this->findModification($modifiedInto, StdLib::nvl($tourist['firstName']), $travelers, 'firstName');
                        }
                        if (array_key_exists('dateOfBirth', $tourist)) {
                            $this->findModification($modifiedInto, StdLib::nvl($tourist['dateOfBirth']), $travelers, 'dateOfBirth');
                        }
                        if (array_key_exists('type', $tourist)) {
                            $this->findModification($modifiedInto, StdLib::nvl($tourist['type']), $travelers, 'type');
                        }

                        // по каждому
                        foreach ($modifiedInto as $modify) {
                            if (!StdLib::nvl($tourist['modified'], 0)) {
                                $newRelations['touristId'] = $tourist['touristId'];
                                $newRelations['touristId_GP'] = $modify;
                                $newRelations['modified'] = true;
                                $tourist['modified'] = 1;
                            }
                        }
                    }
                }
            } else {
                return ['modified' => true];
            }
        }
        return $newRelations;
    }

    /**
     * Процедура редактирования услуги: формирование параметров и сохранение в GP и обработка ответа
     * Допущения: Редактирование происходит только по одной услуге, поэтому
     * $processId (GPTS_processId) - один
     * @param $params
     * @return bool|mixed[]
     */
    public function setServiceData($params)
    {
        //$processId = StdLib::nvl($params['engineData']['data'][0]['GPTS_processId']);
        $processId = StdLib::nvl($params['engineData']['data']['GPTS_service_ref']);
        if (empty($processId)) {
            return false;
        }
        $gptsParams = $this->fillGPTSParams($params);
        $manualModApi = new ManualModificationAPI($this->apiClient);

        if ($gptsParams['status'] == 'ERROR') {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'ServiceData', 'GPTS параметры запроса для перевода в ручник',
                [
                    'processId' => $processId,
                    'status' => 'ERROR'
                ],
                'info',
                'system.supplierservice.*'
            );
            $manualModApi->setManualMofification([
                'processId' => $processId,
                'status' => 'ERROR'
            ]);
        }

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'ServiceData', 'GPTS параметры запроса',
            $gptsParams,
            'info',
            'system.supplierservice.*'
        );
        $retInfo = $manualModApi->setManualMofification($gptsParams);
        return $this->modifiedServiceDataAfterGPTSSaving($retInfo, $processId);
    }
}
