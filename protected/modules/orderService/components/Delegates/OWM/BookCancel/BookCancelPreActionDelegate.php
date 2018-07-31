<?php

/**
 * Вызывает валидацию SWM для отмены бронирования
 */
class BookCancelPreActionDelegate extends AbstractDelegate
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

        if (!$SWM_FSM->can('SERVICEBOOKCANCEL', $params)) {
            $this->setError(OrdersErrors::SERVICE_STATUS_IS_BLOCKING_BOOK_CANCEL);
            return null;
        }
    }
}