<?php

/** Класс, содержащий методы работы с услугой перелета */
class FlightServiceManager extends ServiceManager
{

    public function __construct(&$module, &$apiClient)
    {
        parent::__construct($module, $apiClient);
        $this->validator = new FlightServiceValidator();
    }

    /**
     * Получение информации по предложению
     * @param mixed[] $params параметры запроса
     * @return mixed[]|false структура ответа или false в случае ошибки
     */
    public function getOffer($params)
    {
        if ($this->validator->checkGetOffer($params['supplierOfferData'])) {
            $apiFlights = new FlightsApi($this->apiClient);

            $query = [
                'offerKey' => $params['supplierOfferData']['offerKey']
            ];

            $flightInfo = $apiFlights->flightInfo($query);

            /** обработка *странного* овтета в виде пустого массива itinerary */
            if (count($flightInfo['itinerary']) === 0) {
                throw new KmpException(
                    get_class(), __FUNCTION__,
                    SupplierErrors::OFFER_UNAVAILABLE,
                    ['offerKey' => $params['supplierOfferData']['offerKey']]
                );
            }

            /** @todo временная реализация - параметров extra быть не должно */
            $flightInfo = array_merge(
                $flightInfo,
                ['extra' => $params['extra']],
                ['supplierOfferData' => $params['supplierOfferData']]
            );

            $flightOffer = new FlightOffer($flightInfo);

            return $flightOffer->getStructure();
        } else {
            return false;
        }
    }


    /**
     * Запуск бронирования авиаперелета
     * @param mixed[] $params параметры запроса
     * @return mixed[]|false структура ответа или false в случае ошибки
     */
    public function serviceBooking($params)
    {
        if ($this->validator->checkServiceBooking($params['supplierOfferData'])) {
            $apiFlights = new FlightsApi($this->apiClient);
            $apiBooking = new BookingApi($this->apiClient);

//            if (!empty($params['supplierOfferData']['engineDataList'])
//                && is_array($params['supplierOfferData']['engineDataList'])
//            ) {
//                foreach ($params['supplierOfferData']['engineDataList'] as $engineData) {
//                    if (!isset($engineData['serviceType']) ||
//                        !isset($engineData['gatewayId']) ||
//                        !isset($engineData['GPTS_order_ref'])
//                    ) {
//                        continue;
//                    }
//
//                    if ($engineData['serviceType'] == $params['serviceType']
//                        && $engineData['gatewayId'] == GPTSSupplierEngine::ENGINE_ID
//                    ) {
//                        $orderId = $engineData['GPTS_order_ref'];
//                    }
//                }
//            }

            $query = [
                'offerKey' => $params['supplierOfferData']['offerKey'],
                'orderId' => isset($params['gateOrderId']) ? $params['gateOrderId'] : '',
                'tourists' => []
            ];
            foreach ($params['supplierOfferData']['tourists'] as $t) {
                /** @todo вынести функцию из orderService->TouristForm ? */
                $age = date('Y') - DateTime::createFromFormat('Y-m-d', $t['birthdate'])->format('Y');

                $phonecleaner = ['(', ')', '-', ' '];

                $query['tourists'][] = [
                    'citizenshipId' => $t['citizenshipId'],
                    'email' => $t['email'],
                    'phone' => str_replace($phonecleaner, '', $t['phone']),
                    'passport' => [
                        'number' => $t['passport']['number'],
                        'expiryDate' => $t['passport']['expiryDate'],
                    ],
                    'prefix' => ($t['sex'] == 0) ? 'Ms' : 'Mr',
                    'type' => ($age > 12) ? 'adult' : 'child',
                    'lastName' => $t['lastName'],
                    'firstName' => $t['firstName'],
                    'birthdate' => $t['birthdate'],
                    'bonusCard' => [
//                        'id' => $t['bonusCard']['id'],
                        'number' => $t['bonusCard']['cardNumber'],
                        'airlineCode' => $t['bonusCard']['airLine']
                    ]
                ];
            }
            $bookPrepare = $apiFlights->prepareFlightBook($query);

            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Ответ ГПТС на prepareFlightBook', '',
                $bookPrepare,
                'info',
                'system.supplierservice.*'
            );

            if (empty($bookPrepare['status']) || empty($bookPrepare['bookingKey'])) {
                //Неизвестная ошибка бронирования
                $BookData = new BookData();
                $BookData->setServiceId(StdLib::nvl($query['serviceId']));
                $BookData->setGateOrderId(StdLib::nvl($query['orderId']));

                LogHelper::logExt(
                    __CLASS__, __METHOD__,
                    'prepareFlightBook', 'Неизвестная ошибка бронирования',
                    [   'offerKey' => $params['supplierOfferData']['offerKey'],
                        'prepFlightBookResponse' => json_encode($bookPrepare)
                    ],
                    'error',
                    'system.supplierservice.error'
                );
                // Парсим ошибку GPTS и добываем сообщение SupplierMessages
                $responseErrorArray = $BookData->getClearErrorsCodeWithDescription($bookPrepare['errors']);
                if (isset($responseErrorArray[448])){ // Ищем системную 448 ошибку
                    $BookData->setErrorCode(BookData::BOOKING_PREPARATION_ERROR_448);
                    $BookData->setSupplierMessages($responseErrorArray[448]);
                }else {
                    $BookData->setErrorCode(BookData::BOOKING_PREPARATION_ERROR);
                }
                return $BookData->getBookDataArray();
            } else {
                switch ((int)$bookPrepare['status']) {
                    case 5: //offer rejected
                        $BookData = new BookData();
                        $BookData->setServiceId(StdLib::nvl($query['serviceId']));
                        $BookData->setGateOrderId(StdLib::nvl($query['orderId']));

                        LogHelper::logExt(
                            __CLASS__, __METHOD__,
                            'prepareFlightBook', 'Оффер отклонен',
                            $bookPrepare,
                            'error',
                            'system.supplierservice.error'
                        );
                        $BookData->setErrorCode(BookData::BOOK_ERROR_OFFER_REJECTED);
                        return $BookData->getBookDataArray();

                        break;
                    case 2: //ok
                        break;
                    default:
                        $BookData = new BookData();
                        $BookData->setServiceId(StdLib::nvl($query['serviceId']));
                        $BookData->setGateOrderId(StdLib::nvl($query['orderId']));

                        LogHelper::logExt(
                            __CLASS__, __METHOD__,
                            'prepareFlightBook', 'Не удалось подготовить данные для бронирования',
                            ['offerKey' => $params['supplierOfferData']['offerKey'], 'prepFlightBookResponse' => json_encode($bookPrepare)],
                            'error',
                            'system.supplierservice.error'
                        );
                        $BookData->setErrorCode(BookData::BOOKING_PREPARATION_FAILED);
                        return $BookData->getBookDataArray();

                        break;
                }
            }

            $baggage = [];

            // вытащим информацию о багаже
            if(isset($bookPrepare['baggageInfo']) && count($bookPrepare['baggageInfo'])){
                foreach ($bookPrepare['baggageInfo'] as $baggageInfo) {
                    $baggage[] = [
                        'measureCode' => $baggageInfo['baggage']['unitCode'],
                        'measureQuantity' => $baggageInfo['baggage']['unitQuantity']
                    ];
                }
            }

            /*===  пощекотать GPTS =====*/
            $query = [
                //'offerKey' => $params['supplierOfferData']['offerKey']
                'bookingKey' => $bookPrepare['bookingKey']
            ];
            $apiBooking->paymentOptions($query);
            /**===== пощекотали ===== */

            $query = [
                'bookingKey' => $bookPrepare['bookingKey'],
                'paymentMethodId' => $this->config['paymentMethodId']
            ];

            $bookStart = $apiBooking->book($query);

            LogHelper::logExt(
                get_class(), __METHOD__,
                'Бронивание авиа', 'Ответ ГПТС по брони АВИА',
                $bookStart,
                'info',
                'system.supplierservice.*'
            );

            if (!count($bookStart) || empty($bookStart[0]['status'])) {
                throw new KmpException(
                    get_class(), __FUNCTION__,
                    SupplierErrors::BOOKING_START_FAILED,
                    [
                        'offerKey' => $params['supplierOfferData']['offerKey'],
                        'bookResponse' => json_encode($bookStart),
                        'bookingKey' => $bookPrepare['bookingKey']
                    ]
                );
            } else {
                $bookStart = $bookStart[0];
            }

//LogHelper::logExt(get_class($this), __METHOD__, '----------bookPrepare-2', '', ['$bookStart'=>$bookStart], 'info', 'system.searcherservice.info');


            // Обработка ошибок из GPTS
            if (isset($bookStart['errors'])) {
                LogHelper::logExt(
                    __CLASS__, __METHOD__,
                    'FlightBook', 'Ошибки при бронировании',
                    $bookStart,
                    'error',
                    'system.supplierservice.*'
                );

                $BookData = new BookData();
                $BookData->setServiceId(StdLib::nvl($params['serviceId']));
                $BookData->setGateOrderId(StdLib::nvl($bookStart['orderId']));
                $BookData->setErrorCode(BookData::BOOK_ERROR_NO_VALID_RESULT);

                // -------- Ошибки полученные из GPTS --------//
                //Получаю массив ошибок из GPTS
                $responseErrorArray = $BookData->getClearErrorsCode($bookStart['errors']);
                // Выбираю только те ошибки, которые описаны в BookData->$mapingGPTSErrorCode
                $mapedResponseArray = $BookData->getMapingErrorArray($responseErrorArray);
                // Если в массиве есть коды, беру самый первый
                if (isset($mapedResponseArray)){
                    LogHelper::logExt(__CLASS__, __METHOD__, 'FlightBook', $BookData->getErrorDescription($mapedResponseArray[0]), $bookStart, 'error', 'system.supplierservice.*');
                    $BookData->setErrorCode($mapedResponseArray[0]);
                }
                return $BookData->getBookDataArray();
            }


            /** TODO temp return*/
            switch ((int)$bookStart['status']) {
                case 0:
                case 1: //в процессе бронирования
                case 5: //отклонено
                    $jobParams = [
                        'orderId' => $params['orderId'],
                        'serviceId' => $params['serviceId'],
                        'gptsOrderId' => $bookStart['orderId'],
                        'processId' => $bookStart['processId'],
                        'serviceType' => $params['serviceType'], // 2
                        'usertoken' => $params['usertoken'],
                        'baggage' => $baggage
                    ];

                    $this->module->runBookingPollTask($jobParams);

                    $response = [
                        'serviceId' => $params['serviceId'],
                        'bookStartResult' => 1
                    ];
                    return $response;
                case 2: //забронировано не запускаем асинхронку, а сразу возвращаем результат
                    $q = [
                        'orderType' => 'TO',
                        'orderId' => $bookStart['orderId']
                    ];

                    $apiOrder = new OrdersApi($this->apiClient);
                    $GPOrder = new GPOrders($apiOrder->orders_get($q));

                    $BookData = new BookData();
                    $BookData->setBooked();
                    $BookData->setServiceId($params['serviceId']);
                    $BookData->setGateOrderId($bookStart['orderId']);
                    $BookData->setGateServiceId($GPOrder->getServiceIdByPnr($bookStart['refNumber']));
                    $BookData->setBookData([
                            'pnrData' => [
                                'engine' => [
                                    'type' => SupplierFactory::GPTS_ENGINE, /** @todo здесь надо как-то определять, наверно? */
                                    'GPTS_service_ref' => $bookStart['processId'],
                                    'GPTS_order_ref' => $bookStart['orderId']
                                ],
                                'lastTicketingDate' => $GPOrder->getLastTicketingDateByServiceId($BookData->getGateServiceId()),
                                'supplierCode' => '',
                                'PNR' => $bookStart['refNumber'],
                                'baggage' => $baggage
                            ],
                            'segments' => [],
                        ]
                    );


                    return $BookData->getBookDataArray();
                default:
                    throw new KmpException(
                        get_class(), __FUNCTION__,
                        SupplierErrors::BOOKING_START_FAILED,
                        [
                            'offerKey' => $params['supplierOfferData']['offerKey'],
                            'bookingKey' => $bookPrepare['bookingKey'],
                            'status' => $bookStart['status'],
                            'bookResponse' => json_encode($bookStart)
                        ]
                    );
                    break;
            }

        } else {
            return false;
        }
    }

    /**
     * Выписка билетов авиаперелёта
     * @param mixed[] $params
     * @return bool|void
     */
    public function issueTickets($params)
    {
        $this->validator->checkIssueTickets($params['bookData']);

        if (!empty($params['bookData']['pnrData'])) {

            return $this->issueTicketsOneSegment($params);

        } elseif (!empty($params['bookData']['segments'])) {

            return $this->issueTicketsMultiSegment($params);
        }

    }

    /**
     * Получение маршрутных квитанций авиаперелёта
     * @param mixed[] $params
     * @return array
     */
    public function getEtickets($params)
    {
        $ticketsApi = new TicketsApi($this->apiClient);

        $serviceRef = preg_replace('/\/[^\/]*\//', '', $params['ticket']['pnrData']['engine']['GPTS_service_ref']);

        $eticketsParams = [
            'processId' => $serviceRef,
            'link' => 'true'
        ];

        $result = $ticketsApi->getEtickets($eticketsParams);

        if (empty($result) || !isset($result['downloadLink'])) {
            throw new KmpException(
                get_class(),
                __FUNCTION__,
                SupplierErrors::CANNOT_GET_ETICKET,
                ['ticket' => $params]
            );
        }

        $eticketResult = $ticketsApi->getETicketByUrl($result['downloadLink'][0]);
        $result['downloadLink'] = $result['downloadLink'][0];

        if (empty($eticketResult['code']) || $eticketResult['code'] != 200) {
            throw new KmpException(
                get_class(),
                __FUNCTION__,
                SupplierErrors::CANNOT_GET_ETICKET,
                ['ticket' => $params]
            );
        }

        return [
            'receiptUrl' => $result,
            'eticketData' => $params
        ];

//        return $result;
    }

    /**
     * Получение билета для односегментного перелёта
     * @param $params
     */
    public function issueTicketsOneSegment($params)
    {
        $ticketsApi = new TicketsApi($this->apiClient);
        $ordersApi = new OrdersApi($this->apiClient);

        $serviceRef = preg_replace('/\/[^\/]*\//', '', $params['bookData']['pnrData']['engine']['GPTS_service_ref']);

        $issueParams = [
            'processId' => $serviceRef
        ];

        $result = $ticketsApi->issue($issueParams);

        // Сообщение об ошибке


        if (empty($result['object']) || $result['object'] != 'Issued') {

            $errMessage = StdLib::nvl($result['parameters'][1]);
            if (!is_null($errMessage) ){
                // Второе значение массива: 'Supplier Error, code: 448, description ETS TCH: T01 THE POINT OF SALE 92005550 IS NOT ACCREDITED'
                $arr = preg_split('/[\,]+/', $errMessage);      // [0] => Supplier Error, [1] =>  code: 448, [2] =>  description ETS TCH: T01 THE POINT OF SALE 92005550 IS NOT ACCREDITED
                if (preg_match('/76/', $arr[1]) || preg_match('/448/', $arr[1])) {
                    $params['comments'] = $arr[2];              // description ETS TCH: T01 THE POINT OF SALE 92005550 IS NOT ACCREDITED
                }

                $responseObj = new IssueTicketsResponse([]);
                $responseObj->setPnrData($params['bookData']);
                $responseObj->setStatus(1);
                $responseObj->setErrorCode(SupplierErrors::TICKET_ISSUING_ERROR_MESSAGE);
                $responseObj->setErrorDescription($arr[1].', '.$arr[2]);
                $response = $responseObj->getResponse();
                return $response;
            }else {
                throw new KmpException(
                    get_class(),
                    __FUNCTION__,
                    SupplierErrors::TICKET_ISSUING_ERROR,
                    [
                        'pnrData' => $params['bookData']['pnrData'],
                        'issueTicketsResponse' => json_encode($result)
                    ]
                );
            }
        }

        $orderParams = [
            'orderType' => 'TO',
            'orderId' => $params['bookData']['pnrData']['engine']['GPTS_order_ref']
        ];

        $orderInfo = $ordersApi->orders_get($orderParams);

        if (empty($orderInfo)) {
            throw new KmpException(
                get_class(),
                __FUNCTION__,
                SupplierErrors::CANNOT_GET_SUPPLIER_ORDER,
                ['orderParams' => $orderParams]
            );
        }

        $this->validator->checkGetOrdersResult($orderInfo);

        $responseObj = new IssueTicketsResponse($orderInfo);
        $responseObj->setPnrData($params['bookData']);
        $response = $responseObj->getResponse();

        return $response;
    }

    /**
     * Получение билета для мультисегментного перелёта
     * @param $params
     */
    public function issueTicketsMultiSegment($params)
    {
    }

    /**
     * Получение правил тарифа для авиаперелета
     * @param array параметры команды
     */
    public function getFareRule($params)
    {
        $parsingRules = $this->module->getConfig('flightFaresParsingRules');
        $fareRules = [];

        if ($parsingRules == false || !is_array($parsingRules)) {
            throw new KmpException(
                __CLASS__, __FUNCTION__,
                SupplierErrors::FARE_PARSING_RULES_NOT_FOUND,
                ['config' => 'flightFaresParsingRules']
            );
        }

        $flightsApi = new FlightsApi($this->apiClient);
        $flightFaresParams = [
            'offerKey' => $params['offerKey']
        ];

        $lang = !empty($params['lang']) ? $params['lang'] : 'ru';

        $result = $flightsApi->flightFares($flightFaresParams, $lang);
        if (empty($result) || empty($result['flightRules'])) {
            throw new KmpException(
                __CLASS__, __FUNCTION__,
                SupplierErrors::CANNOT_GET_FLIGHT_FARES,
                ['offerKey' => $params['offerKey']]
            );
        }

        foreach ($result['flightRules'] as $seg) {
            $fareRule = new FareRule($seg['segment'], $seg['paragraphs']);

            foreach ($parsingRules as $block => $rules) {
                if (
                    !isset($rules['flags']) || !is_array($rules['flags']) ||
                    (!isset($rules['patterns']) && !isset($rules['clear']))
                ) {
                    throw new KmpException(
                        __CLASS__, __FUNCTION__,
                        SupplierErrors::INCORRECT_FARE_PARSING_CONFIG,
                        ['block' => $block]
                    );
                }

                $blockText = $fareRule->getBlockText($block);
                if ($blockText === false) {
                    continue;
                }

                $paternTests = [];

                if (isset($rules['clear'])) {
                    foreach ($rules['clear'] as $rk => $rule) {
                        $count = 0;
                        $reg = '/' . $rule . '/iu';
                        $blockText = preg_replace($reg, '', $blockText, -1, $count);
                        if ($count > 0) {
                            $patternTests[$rk] = true;
                        } else {
                            $patternTests[$rk] = false;
                        }
                    }
                }

                if (isset($rules['patterns'])) {
                    foreach ($rules['patterns'] as $rk => $rule) {
                        $reg = '/' . $rule . '/iu';
                        if (preg_match($reg, $blockText)) {
                            $patternTests[$rk] = true;
                        } else {
                            $patternTests[$rk] = false;
                        }
                    }
                }

                try {
                    foreach ($rules['flags'] as $flag => $func) {
                        $fareRule->setFlag($flag, $func($patternTests));
                    }
                } catch (Exception $e) {
                    throw new KmpException(
                        __CLASS__, __FUNCTION__,
                        SupplierErrors::INCORRECT_FARE_PARSING_FUNCTION_CONFIG,
                        ['error' => $e->getMessage()]
                    );
                }
            }

            $fareRules[] = $fareRule->toArray();
        }

        return ['rules' => $fareRules];
    }

    /**
     * @param mixed[] $params
     * @return mixed
     */
    public function serviceModify($params)
    {

    }

}