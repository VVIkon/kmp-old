<?php

class FlightBookingPoll extends AbstractServiceBookingPoll
{
    public function bookingPoll(array $params)
    {
        $q = [
            'orderType' => 'TO',
            'orderId' => $params['gptsOrderId']
        ];

        $apiOrder = new OrdersApi($this->apiClient);
        $orderInfo = new GPOrders($apiOrder->orders_get($q));

        LogHelper::logExt(
            get_class(), __METHOD__,
            'Опрос ГПТС при бронировании АВИА', 'Статус заявки ГПТС',
            $orderInfo->getInfo(),
            'info',
            'system.supplierservice.*'
        );

        $BookData = new BookData();
        $BookData->setServiceId($params['serviceId']);

        switch ($orderInfo->getServiceStatusByProcessId($params['processId'])) {
            case 'Quote':
            case 'Confirmation pending':
            case 'Form.Modify service status on confirmation pending for TO':
                /* в процессе */
                return null;
            case 'PreConfirmed':
                $BookData->setBooked();
                break;
            case 'Rejected':
                $BookData->setBookResult(BookData::BOOK_RESULT_NOT_BOOKED);
                break;
            case 'Error':
            case 'Cancelled':
            case 'Form.Repair':
                /* Не забронировано */
                $BookData->setBookResult(BookData::BOOK_RESULT_NOT_BOOKED);
                break;
            default:
                LogHelper::logExt(
                    get_class($this),
                    'bookingPoll',
                    'Опрос GPTS на статус брони',
                    "Получен неизвестный статус {$orderInfo->getServiceStatusByProcessId($params['processId'])}",
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

        $BookData->setGateServiceId($orderInfo->getServiceIdByProcessId($params['processId']));
        $BookData->setBookData([
            'pnrData' => [
                'engine' => [
                    'type' => SupplierFactory::GPTS_ENGINE, /** @todo здесь надо как-то определять, наверно? */
                    'GPTS_service_ref' => $params['processId'],
                    'GPTS_order_ref' => $params['gptsOrderId']
                ],
                'lastTicketingDate' => $orderInfo->getLastTicketingDateByServiceId($BookData->getGateServiceId()),
                'baggage' => $params['baggage'],
                'supplierCode' => $orderInfo->getSupplierCode(),
                'PNR' => $orderInfo->getRefNumByProcessId($params['processId'])
            ],
            'segments' => []
        ]);
        $BookData->setGateOrderId($params['gptsOrderId']);

        return $BookData;
    }
}