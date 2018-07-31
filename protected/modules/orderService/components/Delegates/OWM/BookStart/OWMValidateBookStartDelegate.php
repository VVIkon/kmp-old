<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/6/16
 * Time: 12:57 PM
 */
class OWMValidateBookStartDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        if (!isset($params['serviceId'])) {
            $this->setError(OrdersErrors::SERVICE_ID_NOT_SET);
            return null;
        }

        if (!isset($params['agreementSet']) || !($params['agreementSet'] === true)) {
            $this->setError(OrdersErrors::OFFERS_AND_RATES_AGREEMENT_NOT_CONFIRMED);
            return null;
        }
    }
}