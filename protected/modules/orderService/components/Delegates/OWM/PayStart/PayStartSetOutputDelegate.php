<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 27.01.17
 * Time: 17:57
 */
class PayStartSetOutputDelegate extends AbstractDelegate
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
        
        $this->addResponse('orderStatus', $OrderModel->getStatus());
    }

}