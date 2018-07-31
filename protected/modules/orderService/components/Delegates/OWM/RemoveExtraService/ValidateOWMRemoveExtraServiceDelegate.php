<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 18.07.17
 * Time: 18:07
 */
class ValidateOWMRemoveExtraServiceDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (empty($params['serviceId']) || empty($params['addServiceId'])) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return null;
        }
    }
}