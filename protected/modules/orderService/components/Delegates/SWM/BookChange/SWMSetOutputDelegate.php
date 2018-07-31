<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/22/16
 * Time: 6:24 PM
 */
class SWMSetOutputDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_POST_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        $this->addResponse('serviceStatus', $OrdersServices->getStatus());
    }
}