<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 28.07.17
 * Time: 18:33
 */
class GptsRoomOfferPriceTax extends KFormModel
{
    protected $code;
    protected $amount;
    protected $currency;

    public function setTaxParams($taxAndFee)
    {

        $this->code = StdLib::nvl($taxAndFee['code'], '');
        $this->amount = StdLib::nvl($taxAndFee['amount'], 0);
        $this->currency = StdLib::nvl($taxAndFee['currency'], '');
    }

    public function toArray()
    {
        return [
            'code' => $this->code,      // налог
            'amount' => $this->amount,                   // Величина
            'currency' => $this->currency                 // валюта
        ];

    }
}