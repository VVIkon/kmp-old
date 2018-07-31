<?php

/**
 * Вызвать ServiceWorkflowManager.BookStart
 */
class RunSWMBookStartDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        $OrdersService = OrdersServices::model()->findByPk($params['serviceId']);

        if (is_null($OrdersService)) {
            $this->setError(OrdersErrors::SERVICE_NOT_FOUND);
            return null;
        }

        $SWM_FSM = new StateMachine($OrdersService);

        if (!$SWM_FSM->can('SERVICEBOOKSTART')) {
            $this->setError(OrdersErrors::SERVICE_STATUS_IS_BLOCKING_BOOKING);
            return null;
        }

        $params['orderModel'] = $params['object'];
        $params['orderId'] = $OrderModel->getOrderId();

        $SWMResponse = $SWM_FSM->apply('SERVICEBOOKSTART', $params);
        // критическую ошибку транслируем и завершаем действие
        if (isset($SWMResponse['status']) && $SWMResponse['status']) {
            $this->setError($SWMResponse['status']);

            // Если из GPTS пришла ошибка которую ныжно показать наружу
            if (isset($SWMResponse['BookData'])) {
                $BookData = new BookData();
                $BookData->unserialize($SWMResponse['BookData']);
                // выведем наружу сообщения поставщика
                if ($BookData->getSupplierMessages()) {
                    $this->setDataToContext('errorMessages', $BookData->getSupplierMessages());
                }
                $this->setObjectToContext($BookData);
            }
            return null;
        }

        // вытащим BookData из контекста SWM
        if (isset($SWMResponse['BookData'])) {
            $BookData = new BookData();
            $BookData->unserialize($SWMResponse['BookData']);

            // выведем наружу сообщения поставщика
            if ($BookData->getSupplierMessages()) {
                $this->addResponse('supplierMessages', $BookData->getSupplierMessages());
            }
            $this->setObjectToContext($BookData);
        } else {
            $this->setError(OrdersErrors::BOOK_DATA_NOT_SET);
            return null;
        }

        // вытащим новые данные если они есть
        if (isset($SWMResponse['response']['newOfferData'])) {
            $this->addResponse('newOfferData', $SWMResponse['response']['newOfferData']);
        }

        if ($BookData->getGateOrderId()) {
            $OrderModel->setOrderIDGP($BookData->getGateOrderId());
        }
        $OrderModel->save();

        $this->params['object'] = $OrderModel->serialize();
        // вытащим статус услуги в ответ
        $this->addResponse('serviceStatus', isset($SWMResponse['response']['serviceStatus']) ? $SWMResponse['response']['serviceStatus'] : '');
        $this->addResponse('BookData', $BookData->getBookDataArray());
    }
}