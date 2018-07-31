<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/14/16
 * Time: 1:49 PM
 */
class ValidateOWMDoneDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!isset($params['serviceId'])) {
            $this->setError(OrdersErrors::SERVICE_ID_NOT_SET);
            return null;
        }

        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        if ($OrderModel->inStatus(OrderModel::STATUS_BOOKED) && !in_array($params['userProfile']['userType'], [1, 3])) {
            $this->setError(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
            return null;
        }
    }
}