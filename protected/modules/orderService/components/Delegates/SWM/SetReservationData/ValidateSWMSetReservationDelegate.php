<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12/9/16
 * Time: 11:15 AM
 */
class ValidateSWMSetReservationDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersServices = OrdersServicesRepository::findByIdAndOrderId($OrderModel->getOrderId(), $params['serviceId']);

        if (is_null($OrdersServices)) {
            $this->setError(OrdersErrors::SERVICE_NOT_FOUND);
            return;
        }
    }
}