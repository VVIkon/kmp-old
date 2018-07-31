<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/29/16
 * Time: 5:23 PM
 */
class BookStartDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        // восстановим объект сервиса
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);
        $Offer = $OrdersService->getOffer();

        $BookData = new BookData();
        $BookData->setServiceId(StdLib::nvl($params['serviceId']));
        $BookData->setGateOrderId(StdLib::nvl($params['orderId']));

        //  Оценка таймлимита бронирования услуги
//        $interval = (int)(strtotime($OrdersService->getDateCreate()) > strtotime($Offer->getTimeLimitBookingDate()) );
        $interval = (int)(strtotime("now") > strtotime($Offer->getTimeLimitBookingDate()) );
        if ($interval > 0){
            $BookData->setErrorCode($BookData::BOOKING_TIME_LIMIT_IS_OVER);
        }

        if (!$BookData->hasErrors()) {
            // сформируем параметры для букинга
            $ServiceBooking = new ServiceBooking();
            $ServiceBooking->setOrder($OrderModel);
            $touristData = $OrdersService->getServiceTouristsArray();
            $ServiceBooking->setTourist($touristData);

            $AllOrdersServicesInOrder = $OrderModel->getOrderServices();
            foreach ($AllOrdersServicesInOrder as $OrdersServiceInOrder) {
                if ($OrdersServiceInOrder->getServiceID() != $OrdersService->getServiceID()) {
                    $ServiceBooking->setEngineData($OrdersServiceInOrder->getOffer()->getEngineData());
                    break;
                }
            }

            $error = $ServiceBooking->setService($OrdersService);

            if ($error) {
                $this->setError($error);
                return null;
            }

            // добавим комментарий при бронировании - список туристов через запятую
            $orderTourists = $OrdersService->getOrderTourists();
            $touristsFIOs = [];
            foreach ($orderTourists as $orderTourist) {
                $touristsFIOs[] = (string)$orderTourist->getTourist();
            }
            $touristsFIOsString = implode(', ', $touristsFIOs);

            $this->addNotificationData('comment', "Участники поездки: $touristsFIOsString, начало поездки {$OrdersService->getDateStart()}");

            // заполним параметры для букинга
            $bookingParams = $ServiceBooking->toArray();
            $bookingParams['usertoken'] = $params['usertoken'];

            // сделаем запрос сервис букинг
            $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
            $serviceBookingResponse = $apiClient->makeRestRequest('supplierService', 'ServiceBooking', $bookingParams);


            if (!is_string($serviceBookingResponse)) {
                $BookData->setErrorCode($BookData::BOOK_ERROR_NO_VALID_BOOK_ANSWER);
            } else {
                $serviceBookingResponse = json_decode($serviceBookingResponse, true);
            }

//LogHelper::logExt(get_class($this), __METHOD__, '----------Point-1', '', ['$bookingParams'=>$bookingParams, '$serviceBookingResponse'=>$serviceBookingResponse], 'info', 'system.searcherservice.info');

            if ((isset($serviceBookingResponse['errors']) && $serviceBookingResponse['errors']) && (isset($serviceBookingResponse['body']) && count($serviceBookingResponse['body']) === 0)) {
                $BookData->setErrorCode($serviceBookingResponse['errorCode']);

                // -------- Ошибки полученные из GPTS --------//
                //Получаю массив ошибок из GPTS
                $responseErrorArray = $BookData->getClearErrorsCode($serviceBookingResponse['errors']);
                // Выбираю только те ошибки, которые описаны в BookData->$mapingGPTSErrorCode
                $mapedResponseArray = $BookData->getMapingErrorArray($responseErrorArray);
//LogHelper::logExt(get_class($this), __METHOD__, '----------Point-2', '', ['$BookData'=>$BookData->getBookDataArray()], 'info', 'system.searcherservice.info');


                // Если в массиве есть коды, беру самый первый
                if (isset($mapedResponseArray[0])) {

                    LogHelper::logExt(__CLASS__, __METHOD__, 'BookStart', $BookData->getErrorDescription($mapedResponseArray[0]), $serviceBookingResponse, 'error', 'system.supplierservice.*');
                    $BookData->setErrorCode($mapedResponseArray[0]);
                }
            } else {
                $BookData->fromArray($serviceBookingResponse['body']);
            }
        }
        $this->addLog("BookData", 'info', $BookData->getBookDataArray());

//LogHelper::logExt(get_class($this), __METHOD__, '----------Point-3', '', ['$BookData'=>$BookData->getBookDataArray()], 'info', 'system.searcherservice.info');

        if ($BookData->hasErrors()) {
            $OrdersServiceHistory = new OrdersServicesHistory();
            $OrdersServiceHistory->setOrderData($OrderModel);
            $OrdersServiceHistory->setObjectData($OrdersService);
            $OrdersServiceHistory->setActionResult(1);
            if($BookData->offerIsOver()){
                $this->addLog("Допустимый лимит времени на бронирование услуги № {$OrdersService->getServiceID()} ({$OrdersService->getServiceName()}), истёк", 'warning');
                // здесь пишем, что допустимый лимит времени на бронирование истёк
                $OrdersServiceHistory->setCommentTpl('{{186}}');
            }elseif ($BookData->offerRejected()) {
                $this->addLog("Не удалось забронировать услугу № {$OrdersService->getServiceID()} ({$OrdersService->getServiceName()}), оффер отклонен", 'warning');
                // здесь пишем, что предложение отклонено
                $OrdersServiceHistory->setCommentTpl('{{142}}');
            } elseif ( $BookData->hasUnknownErrors()) {
                $this->addLog("Не удалось забронировать услугу № {$OrdersService->getServiceID()} ({$OrdersService->getServiceName()}), ошибка пришедшая из GPTS", 'warning');
                // здесь пишем, что ошибка бронирования вызвана ошибкой пришедшей из GPTS
                $OrdersServiceHistory->setCommentTpl('{{143}}');
            }else {
                $this->addLog("Не удалось забронировать услугу № {$OrdersService->getServiceID()} ({$OrdersService->getServiceName()}), ошибка бронирования", 'warning');
                // здесь пишем, что ошибка бронирования
                $OrdersServiceHistory->setCommentTpl('{{143}}');
            }
            $OrdersServiceHistory->setCommentParams([]);

            $this->addOrderAudit($OrdersServiceHistory);
        } else {
            $this->addLog("Услуга № {$OrdersService->getServiceID()} забронирована!");
            if ($BookData->getSupplierMessagesImploded()) {
                $this->addLog("Сообщения поставщика: {$BookData->getSupplierMessagesImploded()}");
            }
        }

        $this->setObjectToContext($BookData);
    }
}