<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/18/16
 * Time: 1:06 PM
 */
class BookChangeSetOutputDelegate extends AbstractDelegate
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