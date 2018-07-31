<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/13/16
 * Time: 11:15 AM
 */
class AccomodationBookingPoll extends AbstractServiceBookingPoll
{
    public function bookingPoll(array $params)
    {
        $q = [
            'orderType' => 'TO',
            'orderId' => $params['gptsOrderId']
        ];

        $apiOrder = new OrdersApi($this->apiClient);
        $GPOrder = new GPOrders($apiOrder->orders_get($q));

        $BookData = new BookData();

        LogHelper::logExt(
            get_class($this),
            'bookingPoll',
            'Опрос GPTS на статус брони',
            "Получен статус {$GPOrder->getServiceStatusByProcessId($params['processId'])}",
            $params,
            'info',
            'system.supplierservice.info'
        );

        switch ($GPOrder->getServiceStatusByProcessId($params['processId'])) {
            case 'Quote':
            case 'Confirmation pending':
            case 'Form.Modify service status on confirmation pending for TO':
                /* в процессе */
                return null;
            case 'Confirmed':
                // попробуем найти в order от ГПТС статусы наших допуслуг
                // запишем их в BookData
                foreach ($params['addServices'] as $addService) {
                    $BookData->addAddService([
                        'status' => $GPOrder->getAddServiceStatusBySubServiceId($params['processId'], $addService['serviceSubType']),
                        'offerId' => $addService['idAddService']
                    ]);

                    LogHelper::logExt(
                        get_class($this),
                        'bookingPoll',
                        'Опрос GPTS на статус брони',
                        "Получен статус доп услуги {$GPOrder->getAddServiceStatusBySubServiceId($params['processId'], $addService['serviceSubType'])}",
                        $params,
                        'info',
                        'system.supplierservice.info'
                    );
                }

                $BookData->setBooked();
                break;
            case 'Rejected':
                $BookData->setErrorCode(BookData::BOOK_ERROR_OFFER_REJECTED);
                break;
            case 'Error':
            case 'Cancelled':
            case 'Form.Repair':
                /* Не забронировано */
                $BookData->setErrorCode(BookData::BOOK_RESULT_NOT_BOOKED);
                break;
            default:
                LogHelper::logExt(
                    get_class($this),
                    'bookingPoll',
                    'Опрос GPTS на статус брони',
                    "Получен неизвестный статус {$GPOrder->getServiceStatusByProcessId($params['processId'])}",
                    $params,
                    'error',
                    'system.supplierservice.error'
                );
                $BookData->setErrorCode(BookData::BOOK_ERROR_NO_VALID_STATUS);
                break;
        }

        LogHelper::logExt(
            get_class($this),
            'bookingPoll',
            'Опрос GPTS на статус брони',
            "Закрываем бронь с параметрами",
            $params,
            'info',
            'system.supplierservice.info'
        );

        $BookData->setServiceId($params['serviceId']);
        $BookData->setGateServiceId($GPOrder->getServiceIdByProcessId($params['processId']));
        $BookData->setGateOrderId($params['gptsOrderId']);
        // эмулируем ответ от бронирования
        $BookData->setAccomodationBookResponse(new AccomodationBookResponse([
            'orderId' => $params['gptsOrderId'],
            'processId' => $params['processId'],
            'refNumber' => $GPOrder->getRefNumByProcessId($params['processId']),
            'gateId' => SupplierFactory::GPTS_ENGINE
        ]));
        return $BookData;
    }

    public function getBookingData()
    {

    }

}