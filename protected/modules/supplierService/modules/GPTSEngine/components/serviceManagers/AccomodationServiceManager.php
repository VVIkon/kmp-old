<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/31/16
 * Time: 4:16 PM
 */
class AccomodationServiceManager extends ServiceManager
{
    /**
     * @var BookingApi
     */
    protected $BookingApi;
    /**
     * @var AccomodationsApi
     */
    protected $AccomodationsApi;

    /**
     * @var PrepareBookResponse
     */
    protected $PrepareBookResponse;

    public function __construct(&$module, &$apiClient)
    {
        parent::__construct($module, $apiClient);
        $this->validator = new AccomodationServiceValidator();

        $this->BookingApi = new BookingApi($this->apiClient);
        $this->AccomodationsApi = new AccomodationsApi($this->apiClient);
    }

    public function getOffer($params)
    {
        // TODO: Implement getOffer() method.
    }

    public function serviceBooking($params)
    {
        $BookData = new BookData();
        $BookData->setServiceId($params['serviceId']);

        /**
         * todo подумать что с этим барахлом сделать
         * а именно сервис поставщиков должен знать про оффера
         * те оффера надо переместить в глобальную область видимости
         * или создать свой оффер для поставщика на основе AbstractOffer
         * и по человечи вытащить из него цену
         */
        if (isset($params['supplierOfferData']['salesTerms']['client']['amountBrutto'])) {
            $servicePrice = $params['supplierOfferData']['salesTerms']['client']['amountBrutto'];
        } else {
            throw new Exception('Supplier price not found');
        }
//        /**
//         * Узнаем цену на доп услуги для сравнения цены предложения
//         */
//        $addServicesTotalPrice = 0;
//        if (isset($params['supplierOfferData']['addServices']) && count($params['supplierOfferData']['addServices'])) {
//            foreach ($params['supplierOfferData']['addServices'] as $addServices) {
//                $addServicesTotalPrice += $addServices['salesTermsInfo']['clientCurrency']['client']['amountBrutto'];
//            }
//        }

        /**
         *  PREPARE BOOK
         */
        // сформируем параметры prepareBook
        // заполним данные по доп услугам
        $prepareAccommodationRequests = [];
        $prepareAccommodationRequest = [
            'tourists' => $params['supplierOfferData']['tourists'],
            'orderId' => isset($params['gateOrderId']) ? $params['gateOrderId'] : '',
            'personId' => '',
            'offerKey' => $params['supplierOfferData']['offerKey'],
        ];

        // добавим комментарий
        if (!empty($params['supplierOfferData']['offerKey'])) {
            $prepareAccommodationRequest['comments'] = $params['supplierOfferData']['comment'];
        }

        // обработка доп услуг
        if (isset($params['supplierOfferData']['addServices'])) {
            foreach ($params['supplierOfferData']['addServices'] as $addService) {
                $subServiceConcreteClass = RefSubServices::getSubServiceConcreteClassById($addService['serviceSubType']);
                $subServiceConcreteClass->fillGPTSServiceBookingArr($prepareAccommodationRequest, $addService);
            }
        }

        $prepareAccommodationRequests[] = $prepareAccommodationRequest;

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'Запрос ГПТС на prepareAccommodationBook', '',
            $prepareAccommodationRequests,
            'info',
            'system.supplierservice.*'
        );

        // сделаем запрос prepareAccommodationBook
        $apiResponse = $this->AccomodationsApi->prepareAccommodationBook($prepareAccommodationRequests);

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'Ответ ГПТС на prepareAccommodationBook', '',
            $apiResponse,
            'info',
            'system.supplierservice.*'
        );

        // инициализация ответа букинга
        // проверим валидность результата
        if (isset($apiResponse['bookingKey']) && isset($apiResponse['prepareBookResponses']) && count($apiResponse['prepareBookResponses'])) {
            $bookingKey = $apiResponse['bookingKey'];

            // заполним объект-ответ от шлюза
            $PrepareBookResponse = new PrepareBookResponse($apiResponse['prepareBookResponses'][0]);
        } else {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'prepareAccommodationBook', '',
                $apiResponse,
                'error',
                'system.supplierservice.errors'
            );

            $BookData->setErrorCode(BookData::BOOK_ERROR_NO_VALID_RESULT);
            return $BookData->getBookDataArray();
        }

        // теперь работаем с ответом
        if ($PrepareBookResponse->offerRejected()) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'prepareAccommodationBook', 'Оффер отклонен',
                $apiResponse,
                'error',
                'system.supplierservice.error'
            );

            $BookData->setErrorCode(BookData::BOOK_ERROR_OFFER_REJECTED);
            return $BookData->getBookDataArray();
        }

        if ($PrepareBookResponse->hasErrors()) {    // Вернулась из  GPTS status=6
            $BookData->setErrorCode(BookData::BOOK_ERROR_RESPONSE_HAS_ERRORS);

            // Парсим ошибку GPTS и добываем сообщение SupplierMessages
            $responseErrorArray = $BookData->getClearErrorsCodeWithDescription($PrepareBookResponse->getErrors());
            if (isset($responseErrorArray[448])){ // Ищем системную 448 ошибку
                $BookData->setErrorCode(BookData::BOOKING_PREPARATION_ERROR_448);
                $BookData->setSupplierMessages($responseErrorArray[448]);
//            }else {
//                $BookData->setErrorCode(BookData::BOOKING_PREPARATION_ERROR);
            }

            return $BookData->getBookDataArray();
        }

        // заполним штрафы и сообщения поставщика
        $BookData->setCancelPenalties($PrepareBookResponse->getCancellations());
        $BookData->setSupplierMessages($PrepareBookResponse->getVendorMessages());

        // сравним цены предложения от шлюза и из нашего оффера
        $SupplierPrice = $PrepareBookResponse->findClientPrice();

        if ($SupplierPrice !== false) {
            $supplierPriceAmount = $SupplierPrice->getAmount();

//            if ($supplierPriceAmount != ($offerPrice + $addServicesTotalPrice)) {
            if ($supplierPriceAmount != $servicePrice) {
                $BookData->setNewOfferData($PrepareBookResponse->getSalesTermsAsArray());

                LogHelper::logExt(
                    __CLASS__, __METHOD__,
                    'Новые ценовые предложения', '',
                    $BookData->getBookDataArray(),
                    'info',
                    'system.supplierservice.info'
                );

                return $BookData->getBookDataArray();
            }
        } else {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'prepareAccommodationBook', 'Не найдены цены для сравнения предложений',
                $apiResponse,
                'error',
                'system.supplierservice.*'
            );

            $BookData->setErrorCode(BookData::BOOK_ERROR_NO_VALID_RESULT);
            return $BookData->getBookDataArray();
        }

        /**
         *
         *  PAYMENT OPTIONS
         *
         */
        $this->BookingApi->paymentOptions([
            'bookingKey' => $bookingKey
        ]);

        /**
         *
         * BOOK
         *
         */
        $bookParams = [
            'bookingKey' => $bookingKey,
            'paymentMethodId' => $this->config['paymentMethodId']
        ];

        $bookResponseArray = $this->BookingApi->book($bookParams);

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'AccommodationBook', 'Результаты бронирования',
            $bookResponseArray,
            'info',
            'system.supplierservice.info'
        );
//        var_dump($bookResponseArray);
//        exit;

        // проверим валидность результата букинга
        if (isset($bookResponseArray[0])) {
            $bookResponseArray = $bookResponseArray[0];
        } else {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'AccommodationBook', 'Нет валидного результата бронирования',
                $bookResponseArray,
                'error',
                'system.supplierservice.*'
            );

            $BookData->setErrorCode(BookData::BOOK_ERROR_NO_VALID_BOOK_ANSWER);
            return $BookData->getBookDataArray();
        }

        $AccomodationBookResponse = new AccomodationBookResponse($bookResponseArray);

        // запишем GPTSOrderID
        $BookData->setGateOrderId($AccomodationBookResponse->getOrderId());

//        var_dump($AccomodationBookResponse);
//        exit;

        if ($AccomodationBookResponse->hasErrors()) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'AccommodationBook', 'Ошибки при бронировании',
                $AccomodationBookResponse->toArray(),
                'error',
                'system.supplierservice.*'
            );
            $BookData->setErrorCode(BookData::BOOK_ERROR_NO_VALID_RESULT);

            // -------- Ошибки полученные из GPTS --------//
            //Получаю массив ошибок из GPTS
            $responseErrorArray = $AccomodationBookResponse->getClearErrorsCode();
            // Выбираю только те ошибки, которые описаны в BookData->$mapingGPTSErrorCode
            $mapedResponseArray = $BookData->getMapingErrorArray($responseErrorArray);
            // Если в массиве есть коды, беру самый первый
            if (isset($mapedResponseArray)){
                LogHelper::logExt(__CLASS__, __METHOD__, 'AccommodationBook', $BookData->getErrorDescription($mapedResponseArray[0]), $bookResponseArray, 'error', 'system.supplierservice.*');
                $BookData->setErrorCode($mapedResponseArray[0]);
            }
            return $BookData->getBookDataArray();
        }

        // запишем результат бронирования
        $BookData->setAccomodationBookResponse($AccomodationBookResponse);

        // идентифицируем услугу через запрос Orders
        $q = [
            'orderType' => 'TO',
            'orderId' => $AccomodationBookResponse->getOrderId()
        ];

        $apiOrder = new OrdersApi($this->apiClient);
        $GPOrders = new GPOrders($apiOrder->orders_get($q));
        $BookData->setGateServiceId($GPOrders->getServiceIdByProcessId($AccomodationBookResponse->getProcessId()));

        // сразу есть подтверждение брони
        // запишем данные букинга и сразу вернем результат
        if ($AccomodationBookResponse->confirmed()) {
            $BookData->setBooked();

            // попробуем найти в order от ГПТС статусы наших допуслуг
            // запишем их в BookData
            foreach ($params['supplierOfferData']['addServices'] as $addService) {
                $BookData->addAddService([
                    'status' => $GPOrders->getAddServiceStatusBySubServiceId($AccomodationBookResponse->getProcessId(), $addService['serviceSubType']),
                    'offerId' => $addService['offerId']
                ]);
            }
        } else { // запустим асинхронный опрос
            $jobParams = [
                'serviceType' => $params['serviceType'], // 1
                'orderId' => $params['orderId'],
                'serviceId' => $params['serviceId'],
                'processId' => $AccomodationBookResponse->getProcessId(),
                'gptsOrderId' => $AccomodationBookResponse->getOrderId(),
                'addServices' => $params['supplierOfferData']['addServices'],
                'usertoken' => $params['usertoken']
            ];

            $this->module->runBookingPollTask($jobParams);
            $BookData->setBookRun();
        }

        return $BookData->getBookDataArray();
    }

    /**
     * Получение ваучера проживания
     * @param mixed[] $params
     * @return array
     */
    public function getEtickets($params)
    {
        $ticketsApi = new TicketsApi($this->apiClient);

        $serviceRef = preg_replace('/\/[^\/]*\//', '', $params['data']['GPTS_service_ref']);

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
                [
                    'ticket' => $params,
                    'getEticketsResponse' => $result
                ]
            );
        }

        $eticketResult = $ticketsApi->getETicketByUrl($result['downloadLink'][0]);
        $result['downloadLink'] = $result['downloadLink'][0];

        if (empty($eticketResult['code']) || $eticketResult['code'] != 200) {
            throw new KmpException(
                get_class(),
                __FUNCTION__,
                SupplierErrors::CANNOT_GET_ETICKET,
                [
                    'ticket' => $params,
                    'getETicketByUrlResponse' => $eticketResult
                ]
            );
        }

        return [
            'receiptUrl' => $result,
        ];
    }

    /**
     * Модификация брониварония
     * @param mixed[] $params
     * @return mixed
     * @throws Exception
     */
    public function serviceModify($params)
    {
        // проверим входные параметры
        if (!(isset($params['orderService']) && isset($params['tourists']) && is_array($params['tourists']) && isset($params['engineData']))) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'serviceModify', 'Ошибка входных парамеров',
                $params,
                'error',
                'system.supplierservice.*'
            );
            throw new Exception('Ошибка входных парамеров', SupplierErrors::INPUT_PARAMS_ERROR);
        }

        // инициализация запроса
        $AccommodationPrepareModifyRQ = new AccommodationPrepareModifyRQ();

        if (!$AccommodationPrepareModifyRQ->init($params)) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'serviceModify', 'Ошибка входных парамеров',
                $params,
                'error',
                'system.supplierservice.*'
            );
            throw new Exception('Ошибка входных парамеров', SupplierErrors::INPUT_PARAMS_ERROR);
        }

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'serviceModify', 'Делаем запрос в ГПТС для изменения бронирования',
            $AccommodationPrepareModifyRQ->toArray(),
            'info',
            'system.supplierservice.*'
        );

//        var_dump($AccommodationPrepareModifyRQ->toArray());
//        exit;

        // запрос 1 шаг
        $prepareAccommodationModifyResponse = $this->AccomodationsApi->prepareAccommodationModify($AccommodationPrepareModifyRQ->toArray());

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'serviceModify', 'Ответ GPTS при изменении бронирования',
            $prepareAccommodationModifyResponse,
            'info',
            'system.supplierservice.*'
        );

        // проверка ответа
        if (is_array($prepareAccommodationModifyResponse)) {
            $PrepareModifyResponse = new PrepareModifyResponse($prepareAccommodationModifyResponse);
        } else {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'serviceModify', 'Невалидный ответ ГПТС',
                $prepareAccommodationModifyResponse,
                'error',
                'system.supplierservice.*'
            );
            throw new Exception('Ошибка при подготовке', SupplierErrors::PREPARE_ACCOMODATION_MODIFY_ERROR);
        }

        // проверим есть ли возможность изменения брони
        if (!$PrepareModifyResponse->isAvailable()) {
            throw new ServiceModifyNotAvailableException('Изменение брони невозможно');
        }

        // дя след этапа проверим есть ли bookingKey
        if (!$PrepareModifyResponse->getBookingKey()) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'serviceModify', 'Невалидный ответ ГПТС',
                $prepareAccommodationModifyResponse,
                'error',
                'system.supplierservice.*'
            );
            throw new Exception('Ошибка при подготовке, нет bookingKey', SupplierErrors::PREPARE_ACCOMODATION_MODIFY_ERROR);
        }

        // запрос шаг 2
        $accommodationModifyResponse = $this->AccomodationsApi->modifyAccommodationService([
            'bookingKey' => $PrepareModifyResponse->getBookingKey()
        ]);

        if (is_array($accommodationModifyResponse)) {
            $ModifyResponse = new ModifyResponse($accommodationModifyResponse);
        } else {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'serviceModify', 'Невалидный ответ ГПТС',
                $accommodationModifyResponse,
                'error',
                'system.supplierservice.*'
            );
            throw new Exception('Ошибка при изменении брони', SupplierErrors::ACCOMODATION_MODIFY_ERROR);
        }

        if ($ModifyResponse->hasErrors()) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'serviceModify', 'При изменении возникла ошибка',
                $accommodationModifyResponse,
                'error',
                'system.supplierservice.*'
            );
            throw new Exception('Ошибка при изменении брони', SupplierErrors::ACCOMODATION_MODIFY_ERROR);
        }

        return $PrepareModifyResponse->getNewSalesTerms();
    }
}