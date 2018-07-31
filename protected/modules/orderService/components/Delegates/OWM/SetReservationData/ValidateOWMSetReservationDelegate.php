<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12/9/16
 * Time: 10:39 AM
 */
class ValidateOWMSetReservationDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!isset($params['reservationData']) || !isset($params['serviceId']) || empty($params['reservationData'])) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return;
        }
    }
}