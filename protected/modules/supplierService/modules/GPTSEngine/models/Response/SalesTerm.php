<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/7/16
 * Time: 11:45 AM
 */
class SalesTerm
{
    const TYPE_SUPPLIER = 'SUPPLIER';
    const TYPE_CLIENT = 'CLIENT';

    protected $types = [SalesTerm::TYPE_SUPPLIER, SalesTerm::TYPE_CLIENT];

    protected $type; // (string, optional) = ['SUPPLIER' or 'CLIENT']: Type of price. Possible values:
    protected $Price; // (Price, optional): Details on price,
    protected $originalCurrency; // (string, optional): Original supplier currency

    public function __construct(array $params)
    {
        $this->type = isset($params['type']) ? $params['type'] : '';
        $this->originalCurrency = isset($params['originalCurrency']) ? $params['originalCurrency'] : '';

        if (isset($params['price'])) {
            $this->Price = new Price($params['price']);
        } else {
            $this->Price = new Price([]);
        }
    }

    protected function setType($type)
    {
        if (in_array($type, $this->types)) {
            $this->type = $type;
        }
    }

    /**
     * @return mixed|string
     */
    public function getType()
    {
        return strtolower($this->type);
    }

    /**
     * @return Price
     */
    public function getPrice()
    {
        return $this->Price;
    }

    public function getSSSalesTerm()
    {
        return $this->Price->getSSSalesTerm();
    }

    public function toArray()
    {
        return [
            'originalCurrency' => $this->originalCurrency,
            'price' => $this->Price->toArray()
        ];
    }
}