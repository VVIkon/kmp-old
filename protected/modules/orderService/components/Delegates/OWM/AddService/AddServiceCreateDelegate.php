<?php

/**
 * Создает сервис и привязывает его к заявке
 */
class AddServiceCreateDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        // десереализуем объект OrderModel
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        // создадим SWM FSM
        $OrdersServices = new OrdersServices();
        $SWM_FSM = new StateMachine($OrdersServices);

        // подготовим параметры для SWM
        if (array_key_exists('serviceType', $params)) {
            $params['serviceParams']['serviceType'] = $params['serviceType'];
        } else {
            $this->setError(OrdersErrors::CANNOT_CREATE_SERVICE);
            return null;
        }

        // запуск SWM FSM
        $params['response']['serviceId'] = null;
        $params['serviceParams']['token'] = $params['token'];
        $params['serviceParams']['usertoken'] = $params['usertoken'];
        $params['serviceParams']['userProfile'] = $params['userProfile'];
        $params['serviceParams']['orderModel'] = $params['object'];
        $params['serviceParams']['orderId'] = $OrderModel->getOrderId();

        $params['serviceParams'] = $SWM_FSM->apply('SERVICECREATE', $params['serviceParams']);

        // если ошибка
        if ($params['serviceParams']['status']) {
            $OrderModel->deleteIfEmpty();
            $this->setError($params['serviceParams']['status']);
        } else {
            // запишем лог
            $this->addLog("В заявку № {$OrderModel->getOrderId()} добавлена услуга {$params['serviceParams']['response']['serviceId']}");
            $this->mergeResponse($params['serviceParams']['response']);
        }
    }
}