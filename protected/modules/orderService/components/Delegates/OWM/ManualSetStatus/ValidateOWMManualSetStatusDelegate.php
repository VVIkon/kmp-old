<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 12:53 PM
 */
class ValidateOWMManualSetStatusDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!isset($params['serviceId']) || empty($params['serviceId'])) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return null;
        }
    }
}