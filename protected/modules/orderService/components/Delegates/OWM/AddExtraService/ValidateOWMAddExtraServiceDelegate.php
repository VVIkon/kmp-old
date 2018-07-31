<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 14.07.17
 * Time: 17:29
 */
class ValidateOWMAddExtraServiceDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (empty($params['viewCurrency'])) {
            $this->setError(OrdersErrors::CURRENCY_NOT_SET);
            return null;
        }

        $viewCurrency = CurrencyStorage::findByString($params['viewCurrency']);

        if (is_null($viewCurrency)) {
            $this->setError(OrdersErrors::CURRENCY_INCORRECT);
            return null;
        }

        if (empty($params['serviceId']) || empty($params['addServiceOfferId']) || !isset($params['required'])) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return null;
        }
    }
}