<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/7/16
 * Time: 11:44 AM
 */
class TaxesAndFees
{
    protected $code; // (string, optional): Tax or fee code,
    protected $amount; // (number, optional),
    protected $currency; // (string, optional)

    public function __construct(array $params)
    {
        $this->code = isset($params['code']) ? $params['code'] : '';
        $this->amount = isset($params['amount']) ? $params['amount'] : '';
        $this->currency = isset($params['currency']) ? $params['currency'] : '';
    }
}