<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/7/16
 * Time: 11:48 AM
 */
class CancellationItemWithConversion
{
    /**
     * @var Price
     */
    protected $ConvertedPrice; // (Price, optional): Penalty in requested currency,

    /**
     * @var Price
     */
    protected $Price; // (Price): Penalty in original currency obtained from supplier,

    protected $description; // (string, optional): Penalty text description,
    protected $dateFrom; // (string): Penalty period start,
    protected $dateTo; // (string): Penalty period end

    public function __construct(array $params)
    {
        $this->dateTo = isset($params['dateTo']) ? $params['dateTo'] : '';
        $this->dateFrom = isset($params['dateFrom']) ? $params['dateFrom'] : '';
        $this->description = isset($params['description']) ? $params['description'] : '';

        if (isset($params['convertedPrice'])) {
            $this->ConvertedPrice = new Price($params['convertedPrice']);
        }

        if (isset($params['price'])) {
            $this->Price = new Price($params['price']);
        }
    }

    public function toArray()
    {
        return [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'description' => $this->description,
            'penalty' => $this->Price->toArray(),
        ];
    }
}