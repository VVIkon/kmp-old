<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/30/16
 * Time: 3:00 PM
 */
class ValidateOWMTouristToServiceDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!isset($params['serviceId']) || !isset($params['touristData']) || !is_array($params['touristData'])) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return;
        }
    }
}