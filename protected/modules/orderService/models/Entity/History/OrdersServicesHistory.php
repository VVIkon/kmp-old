<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/26/16
 * Time: 3:45 PM
 */
class OrdersServicesHistory extends History
{
    public function setObjectData(OrdersServices $OrdersServices)
    {
        $this->setObjectId($OrdersServices->getServiceID());
        $this->setObjectType('service');
        $this->setObjectStatus($OrdersServices->getStatus());
    }
}