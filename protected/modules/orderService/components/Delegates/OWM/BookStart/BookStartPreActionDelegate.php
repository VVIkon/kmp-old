<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/14/16
 * Time: 2:26 PM
 */
class BookStartPreActionDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_PRE_ACTION;

    public function run(array $params)
    {
        $OrdersService = OrdersServices::model()->findByPk($params['serviceId']);

        if (is_null($OrdersService)) {
            $this->setError(OrdersErrors::SERVICE_NOT_FOUND);
            return null;
        }

        $params['orderModel'] = $params['object'];

        $SWM_FSM = new StateMachine($OrdersService);

        if (!$SWM_FSM->can('SERVICEBOOKSTART', $params)) {
            $this->setError(OrdersErrors::SERVICE_STATUS_IS_BLOCKING_BOOKING);
            return null;
        }
    }
}