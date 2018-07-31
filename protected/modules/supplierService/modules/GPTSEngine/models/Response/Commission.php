<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/7/16
 * Time: 11:46 AM
 */
class Commission
{
    protected $currency; // (string, optional): Commission currency,
    protected $amount; // (number, optional): Amount of commission,
    protected $percent; // (number, optional): Percent of commission if it is not number

    public function __construct($params)
    {
        $this->currency = isset($params['currency']) ? $params['currency'] : '';
        $this->amount = isset($params['amount']) ? $params['amount'] : '';
        $this->percent = isset($params['percent']) ? $params['percent'] : '';
    }

    public function toArray()
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'percent' => $this->percent
        ];
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getPercent()
    {
        return $this->percent;
    }
}