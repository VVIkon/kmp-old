<?php

/**
 * Запускает SWM отмена бронирования
 */
class RunSWMBookCancelDelegate extends AbstractDelegate
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

        $params['orderModel'] = $params['object'];
        $params['orderId'] = $OrderModel->getOrderId();

        $SWM_FSM = new StateMachine($OrdersService);
        $SWM_Response = $SWM_FSM->apply('SERVICEBOOKCANCEL', $params);

        if ($SWM_Response['status']) {
            $this->setError($SWM_Response['status']);
            return;
        }

        // если после запуска SWM BookCancel нужно перевести услугу в ручник
        if (isset($SWM_Response['response']['runOWMManual'])) {
            $this->addLog('Синхронно переводим услугу в ручник');

            $account = AccountRepository::getAccountById($params['userProfile']['userId']);

            $OWM_FSM = new StateMachine($OrderModel);
            $OWM_FSM->apply('ORDERMANUAL', [
                'serviceId' => $OrdersService->getServiceID(),
                'comment' => 'Пользователь ' . (string)$account . ' запросил отмену брони',
                'userPermissions' => $params['userPermissions'],
                'userProfile' => $params['userProfile'],
                'usertoken' => $params['usertoken']
            ]);

            $OrdersService->refresh();
            $OrderModel->refresh();

            $this->addResponse('orderStatus', $OrderModel->getStatus());
            $this->addResponse('serviceStatus', $OrdersService->getStatus());
        } else {
            $this->mergeResponse($SWM_Response['response']);
        }
    }
}