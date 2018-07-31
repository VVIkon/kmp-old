<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 5:03 PM
 */
class ValidateOWMManualDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!isset($params['serviceId'])) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return null;
        }
    }
}