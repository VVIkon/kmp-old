<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12/8/16
 * Time: 5:59 PM
 */
class RunSWMSetReservationDelegate extends AbstractDelegate
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

        $OrdersServices = OrdersServicesRepository::findByIdAndOrderId($OrderModel->getOrderId(), $params['serviceId']);

        if (is_null($OrdersServices)) {
            $this->setError(OrdersErrors::SERVICE_NOT_FOUND);
            return;
        }

        $SWM_FSM = new StateMachine($OrdersServices);

        if (!$SWM_FSM->can('SERVICESETRESERVATION')) {
            $this->setError(OrdersErrors::SERVICE_STATUS_IS_BLOCKING_ACTION);
            return null;
        }

        $params['orderModel'] = $OrderModel->serialize();
        $params['orderId'] = $OrderModel->getOrderId();

        $SWMResponse = $SWM_FSM->apply('SERVICESETRESERVATION', $params);

        if ($SWMResponse['status']) {
            $this->setError($SWMResponse['status']);
            return;
        }
    }
}