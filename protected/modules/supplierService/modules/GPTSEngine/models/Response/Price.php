<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/7/16
 * Time: 11:43 AM
 */
class Price
{
    protected $currency; // (string): Price currency,
    protected $amount; //  (number): Cost in price currency,

    /**
     * @var Commission
     */
    protected $Commission; //  (Commission, optional): Information on commission for service,

    /**
     * @var TaxesAndFees []
     */
    protected $TaxesAndFees = []; // (array[TaxesAndFees], optional): Information on taxes and fees for service

    public function __construct(array $params)
    {
        $this->currency = isset($params['currency']) ? $params['currency'] : '';
        $this->amount = isset($params['amount']) ? $params['amount'] : '';

        if (isset($params['taxesAndFees']) && count($params['taxesAndFees'])) {
            foreach ($params['taxesAndFees'] as $taxesAndFees) {
                $this->TaxesAndFees[] = new TaxesAndFees($taxesAndFees);
            }
        }

        if (isset($params['commission'])) {
            $this->Commission = new Commission($params['commission']);
        } else {
            $this->Commission = new Commission([]);
        }
    }

    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return array
     */
    public function getSSSalesTerm()
    {
        $ssSalesTerm = [
            'amountNetto' => $this->amount,            // Нетто-цена
            'amountBrutto' => $this->amount,           // Брутто-цена
            'currency' => $this->currency,              // Валюта предложения
        ];

        if ($this->Commission) {
            $ssSalesTerm['commission'] = $this->Commission->toArray();
            $ssSalesTerm['amountNetto'] -= $this->Commission->getAmount();
        }
        return $ssSalesTerm;
    }

    public function toArray()
    {
        $taxesAndFees = [];

        if (count($this->TaxesAndFees)) {
            foreach ($this->TaxesAndFees as $taxesAndFee) {
                $taxesAndFees[] = (array)$taxesAndFee;
            }
        }

        return [
            'currency' => $this->currency,
            'amount' => $this->amount,
            'taxesAndFees' => $taxesAndFees,
            'commission' => $this->Commission->toArray()
        ];
    }
}