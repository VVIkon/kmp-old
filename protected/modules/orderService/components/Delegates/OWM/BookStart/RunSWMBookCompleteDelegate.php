<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/6/16
 * Time: 12:32 PM
 */
class RunSWMBookCompleteDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $BookData = $this->getObjectFromContext('BookData');

        // сохраним OrderID_GPTS
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);
        if ($BookData->getGateOrderId()) {
            $OrderModel->setOrderIDGP($BookData->getGateOrderId());
            $OrderModel->save();
        }

        if ($BookData->hasResultToCompleteBooking()) {
            $OrdersService = OrdersServices::model()->findByPk($params['serviceId']);

            if (is_null($OrdersService)) {
                $this->setError(OrdersErrors::SERVICE_NOT_FOUND);
                return null;
            }

//            var_dump($BookData->getHotelSupplierOrderId());
////            var_dump($BookData);
//            exit;

            $this->params['object'] = $OrderModel->serialize();

            $params['orderModel'] = $params['object'];
            $params['BookData'] = $BookData->serialize();
            $params['orderId'] = $OrderModel->getOrderId();

            $SWM_FSM = new StateMachine($OrdersService);

            if (!$SWM_FSM->can('SERVICEBOOKCOMPLETE')) {
                $this->setError(OrdersErrors::SERVICE_STATUS_IS_BLOCKING_BOOKING);
                return null;
            }

            $SWMResponse = $SWM_FSM->apply('SERVICEBOOKCOMPLETE', $params);

            $this->addResponse('serviceStatus', isset($SWMResponse['response']['serviceStatus']) ? $SWMResponse['response']['serviceStatus'] : '');
        }
    }
}