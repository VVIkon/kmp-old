<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 4:23 PM
 */
class ServiceSyncStatusSetDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;
    const GPTS_ENGINE = 5;

    /**
     * Исключения - эти статусы ставить не надо, тк
     * они будут доставлены по выполнению бизнес процессов
     * @var array
     */
    private $statusSetExceptions = [
        OrdersServices::STATUS_BOOKED,
    ];

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);
        $serviceId = $OrdersServices->getServiceID();
        $orderId_KT = $OrdersServices->getOrderId();

        $statusOld = $OrdersServices->getStatus();
        $statusNew = StdLib::nvl($params['serviceStatus'], $statusOld);

        $this->params['statusChanged'] = false;
        // Если статус не смапировался ($statusNew=null) или остался прежний - сохранять не надо
        if (!is_null($statusNew) && $statusOld != $statusNew) {

            // если нужно установить статус
            if (!in_array($statusNew, $this->statusSetExceptions)) {
                if (!$OrdersServices->setStatus($statusNew)) {
                    $this->setError(OrdersErrors::INCORRECT_SERVICE_STATUS);
                    return;
                }
                if (!$OrdersServices->save()) {
                    $this->setError(OrdersErrors::DB_ERROR);
                    return;
                }

                LogHelper::logExt(get_class($this), __METHOD__,
                    'Установлен статус услуги', '',
                    [
                        'orderId' => $orderId_KT,
                        'serviceId_KT' => $serviceId,
                        'statusOld' => $statusOld,
                        'statusNew' => $statusNew,
                    ],
                    'info', 'system.orderservice.info'
                );
            }

            $offer = $OrdersServices->getOffer();
            $serviceType = $offer->getServiceType();

            // Если теперь статус услуги = VOIDED или CANCELLED, то необходимо и билет, и PNR, и ваучер, и Reservations перевести в VOIDED (disable)
            if ($statusNew == OrdersServices::STATUS_VOIDED || $statusNew == OrdersServices::STATUS_CANCELLED) {

                if ($serviceType == 1) {    //Hotel
                    $HotelReservations = StdLib::nvl($offer->getHotelReservation(), []);
                    $reservationsChanget = [];
                    foreach ($HotelReservations as $HotelReservation) { // У офера м.б. несколько резервов

                        // ваучеры
                        $hotelVouchers = StdLib::nvl($HotelReservation->getHotelVouchers(), []);
                        $vouchersChanged = [];
                        foreach ($hotelVouchers as $hotelVoucher) { // м.б. несколько ваучеров в резерве
                            $hotelVoucher->setStatus(HotelVoucher::STATUS_VOIDED);
                            $hotelVoucher->save(false);
                            $vouchersChanged[] = $hotelVoucher->getVoucherId();
                        }
                        if (count($vouchersChanged) > 0) {
                            LogHelper::logExt(get_class($this), __METHOD__,
                                'Изменён статус ваучера', '', [
                                    'tickets' => $vouchersChanged,
                                    'status' => 2,
                                ], 'info', 'system.orderservice.info');
                        }

                        // Резерв
                        $HotelReservation->setStatus(HotelReservation::STATUS_DISABLED);
                        $HotelReservation->save(false);
                        $reservationsChanget[] = $HotelReservation->getReservationId();
                    }

                    if (count($reservationsChanget) > 0) {
                        LogHelper::logExt(get_class($this), __METHOD__,
                            'Изменён статус брони', '', [
                                'tickets' => $reservationsChanget,
                                'status' => 2,
                            ], 'info', 'system.orderservice.info');
                    }
                } elseif ($serviceType == 2) {      // Avia
                    $pnrs = StdLib::nvl($offer->getPNRs(), []);
                    $pnrsChanged = [];
                    foreach ($pnrs as $pnr) {
                        // Билеты
                        $AviaTickets = StdLib::nvl($pnr->getAviaTickets(), []);
                        $ticketsChanged = [];
                        foreach ($AviaTickets as $AviaTicket) {
                            $AviaTicket->setStatus(AviaTicket::STATUS_VOIDED);
                            $AviaTicket->save(false);
                            $ticketsChanged[] = $AviaTicket->getTicketNumber();

                        }
                        if (count($ticketsChanged) > 0) {
                            LogHelper::logExt(get_class($this), __METHOD__,
                                'Изменён статус авиабилетов', '', [
                                    'tickets' => $ticketsChanged,
                                    'status' => 2,
                                ], 'info', 'system.orderservice.info');
                        }

                        // PNR
                        $pnr->disable();
                        $pnr->save(false);
                        $pnrsChanged[] = $pnr->getPNR();
                    }
                    if (count($pnrsChanged) > 0) {
                        LogHelper::logExt(get_class($this), __METHOD__,
                            'Изменён статус PNR', '', [
                                'tickets' => $pnrsChanged,
                                'status' => 2,
                            ], 'info', 'system.orderservice.info');
                    }
                }
            } elseif ($statusNew == OrdersServices::STATUS_BOOKED) { // Если услуга забронирована то запускаем BookComplete асинхронно!!!
                $serviceType = $OrdersServices->getServiceType();
                $serviceID = $OrdersServices->getServiceID();

                $serviceParams['usertoken'] = $params['usertoken'];
                $serviceParams['services'][0]['serviceID'] = $serviceID;
                $serviceParams['services'][0]['serviceType'] = $serviceType;

                // добавим доп услуги во входные данные
                $serviceParams['services'][0]['addServices'] = [];

                $addServices = $OrdersServices->getAddServices();
                if (empty($addServices)) {
                    $addServices = [];
                }

                foreach ($addServices as $addService) {
                    $serviceParams['services'][0]['addServices'][] = $addService->toSOAddService();
                }

                $engineData = $offer->getEngineData();

                $engineData['data']['GPTS_service_ref'] = $OrdersServices->getServiceIDGP();

                if (is_array($engineData) && count($engineData) > 0) {
                    $serviceParams['services'][0]['engineData']['gateId'] = $params['gateId'];
                    $serviceParams['services'][0]['engineData']['data'] = $engineData['data'];
                }

                $this->addLog('Запрос SupplierGetOrder', 'info', $serviceParams);

                $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
                $response = $apiClient->makeRestRequest('supplierService', 'SupplierGetOrder', $serviceParams);
                $serviceResponse = json_decode($response, true);

                if (!is_null($serviceResponse)) {
                    $addServicesForBookComplete = [];

                    $addServicesFromResponse = StdLib::nvl($serviceResponse['body']['supplierOrder']['services'][0]['orderService']['addServices'], []);
                    foreach ($addServicesFromResponse as $addServiceFromResponse) {
                        foreach ($addServices as $addService) {
                            if ($addService->getId() == $addServiceFromResponse['addServiceId']) {
                                $addServicesForBookComplete[] = [
                                    'offerId' => $addService->getOfferId(),
                                    'status' => $addServiceFromResponse['status']
                                ];
                            }
                        }
                    }

                    $paramsBookDate['serviceId'] = $serviceID;
                    $paramsBookDate['bookStartResult'] = BookData::BOOK_START_RESULT_BOOKED; //StdLib::nvl($serviceResponse['']);
                    $paramsBookDate['bookErrorCode'] = BookData::BOOK_ERROR_NO_ERROR;
                    $paramsBookDate['bookResult'] = BookData::BOOK_RESULT_BOOKED; //StdLib::nvl($serviceResponse['']);
                    $paramsBookDate['supplierMessages'] = [];
                    $paramsBookDate['newOfferData'] = null;
                    $paramsBookDate['addServices'] = $addServicesForBookComplete;
                    $paramsBookDate['gateServiceId'] = $OrdersServices->getServiceIDGP();

                    if ($serviceType == 1) {  // Hotel
                        $paramsBookDate['gateOrderId'] = StdLib::nvl($serviceResponse['body']['supplierOrder']['order']['orderId_Gp']);
                        $reservationNumber = StdLib::nvl($serviceResponse['body']['supplierOrder']['services'][0]['supplierServiceData']['hotelReservation']['reservationNumber']);
                        $processId = StdLib::nvl($serviceResponse['body']['supplierOrder']['services'][0]['supplierServiceData']['engineData']['GPTS_service_ref']);

                        // проверим новую цену предложения
                        $supplierClientPrice = StdLib::nvl($serviceResponse['body']['supplierOrder']['services'][0]['orderService']['salesTerms']['client']['amountBrutto']);

                        $servicePrice = $OrdersServices->getClientPrice();

                        // если цены не равны, то отправим в BookComplete новые цены
                        if ($servicePrice->getBrutto() != $supplierClientPrice) {
                            $paramsBookDate['newOfferData'] = $serviceResponse['body']['supplierOrder']['services'][0]['orderService']['salesTerms'];
                        } else {
                            $this->addLog('Цена предложения не изменилась, новые ценовые данные не отправляем');
                        }

                        $paramsBookDate['bookData'] = [
                            'hotelReservation' => [
                                'reservationNumber' => $reservationNumber,
                                'gateId' => StdLib::nvl($params['gateId'], 5),
                                'engine' => [
                                    'GPTS_order_ref' => $paramsBookDate['gateOrderId'],
                                    'GPTS_service_ref' => $processId,
                                ],
                            ]
                        ];


                    } else if ($serviceType = 2) {  //Avia
                        $paramsBookDate['gateOrderId'] = StdLib::nvl($serviceResponse['body']['supplierOrder']['order']['orderId_Gp']);
                        $refNumber = StdLib::nvl($serviceResponse['body']['supplierOrder']['services'][0]['supplierServiceData']['aviaReservations']['PNR']);
                        $processId = StdLib::nvl($serviceResponse['body']['supplierOrder']['services'][0]['supplierServiceData']['engineData']['data']['GPTS_service_ref']);

                        $paramsBookDate['bookData'] = [
                            'pnrData' => [
                                'engine' => [
                                    'type' => self::GPTS_ENGINE,
                                    'GPTS_service_ref' => $processId,
                                    'GPTS_order_ref' => $paramsBookDate['gateOrderId']
                                ],
                                'supplierCode' => '',
                                'PNR' => $refNumber
                            ],
                            'segments' => []
                        ];
                    }

                    $BookData = new BookData();
                    $BookData->fromArray($paramsBookDate);

                    $AsyncTask = new AsyncTask();
                    $AsyncTask->setModule(Yii::app()->getModule('orderService'));
                    $bookCompleteParams = [
                        'action' => 'BookComplete',
                        'orderId' => $OrderModel->getOrderId(),
                        'actionParams' => $BookData->getBookDataArray(),
                        'usertoken' => $params['usertoken']
                    ];

                    $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager', $bookCompleteParams);
                    $this->setObjectToContext($AsyncTask);
                }
            }

            $this->params['object'] = $OrdersServices->serialize();
            $OrdersServicesHistory = new OrdersServicesHistory();
            $OrdersServicesHistory->setObjectData($OrdersServices);
            $OrdersServicesHistory->setOrderData($OrderModel);
            $OrdersServicesHistory->setCommentTpl("{{154}} {{{$OrdersServices->getStatusMsgCode()}}}");
            $OrdersServicesHistory->setCommentParams([]);
            $OrdersServicesHistory->setActionResult(0);

            $this->addOrderAudit($OrdersServicesHistory);

            // добавим уведомление
            $this->addNotificationTemplate('manager', [
                'comment' => 'Статус услуги синхронизирован'
            ]);

            $this->params['statusChanged'] = true;
        }

        $this->addResponse('statusChanged', $this->params['statusChanged']);
    }
}