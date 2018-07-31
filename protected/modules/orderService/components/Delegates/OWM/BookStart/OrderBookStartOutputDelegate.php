<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/14/16
 * Time: 1:57 PM
 */
class OrderBookStartOutputDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        $this->addResponse('orderStatus', $OrderModel->getStatus());
    }
}