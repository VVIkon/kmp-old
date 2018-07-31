<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/29/16
 * Time: 1:05 PM
 */
class RunSWMTouristToServiceDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        $OrdersServices = OrdersServicesRepository::findById($params['serviceId']);

        if (is_null($OrdersServices)) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            $this->addLog("Не найдена услуга с ID {$params['serviceId']}", 'error');
            return null;
        }

        $SWM_FSM = new StateMachine($OrdersServices);

        if (!$SWM_FSM->can('SERVICETOURISTTOSERVICE')) {
            $this->setError(OrdersErrors::SERVICE_STATUS_IS_BLOCKING_ACTION);
            $this->addLog("Не удалось запустить SERVICEDONE для услуги № {$params['serviceId']}", 'error');
            return null;
        }

        $SWM_TouristToServiceParams = [
            'orderModel' => $params['object'],
            'usertoken' => $params['usertoken'],
            'userProfile' => $params['userProfile'],
            'touristData' => $params['touristData'],
            'orderId' => $OrderModel->getOrderId()
        ];

        $SWMResponse = $SWM_FSM->apply('SERVICETOURISTTOSERVICE', $SWM_TouristToServiceParams);

        // если возникла ошибка - транслируем ее
        if ($SWMResponse['status']) {
            $this->setError($SWMResponse['status']);
            return null;
        }

        // если все в порядке - запишем ответ в общий ответ
        $this->mergeResponse($SWMResponse['response']);
    }
}