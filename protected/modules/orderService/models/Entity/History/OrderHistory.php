<?php

/**
 * Модель истории заявок
 * User: Sergey Kamenev
 * Date: 7/14/16
 * Time: 1:01 PM
 */
class OrderHistory extends History
{
    public function setObjectData(OrderModel $OrderModel)
    {
        $this->setObjectType('order');
        $this->setObjectId($OrderModel->getOrderId());
        $this->setObjectStatus($OrderModel->getStatus());
    }
}