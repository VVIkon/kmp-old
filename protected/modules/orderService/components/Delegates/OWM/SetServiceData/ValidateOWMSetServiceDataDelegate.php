<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12/16/16
 * Time: 2:18 PM
 */
class ValidateOWMSetServiceDataDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!isset($params['orderServiceData']) || !isset($params['serviceId']) || empty($params['orderServiceData'])) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return;
        }
    }
}