<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 27.01.17
 * Time: 12:21
 */
class ValidateSWMPayStartDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        if (!$params['statusesOnly']) {
            if (!$OrdersServices->canSetInvoiceServiceWithAmount($params['amount'], CurrencyStorage::findByString($params['currency']))) {
                $this->setError(OrdersErrors::INCORRECT_INVOICE_AMOUNT);
                return;
            }
        }
    }
}