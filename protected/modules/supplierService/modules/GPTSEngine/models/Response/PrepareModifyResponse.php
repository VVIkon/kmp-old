<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/17/16
 * Time: 4:12 PM
 */
class PrepareModifyResponse
{
    protected $bookingKey;
    protected $available;
    protected $startDate;
    protected $endDate;
    /**
     * @var SalesTerm []
     */
    protected $SalesTerms = [];

    public function __construct(array $params)
    {
        $this->bookingKey = isset($params['bookingKey']) ? $params['bookingKey'] : null;
        $this->available = isset($params['available']) ? $params['available'] : false;
        $this->startDate = isset($params['startDate']) ? $params['startDate'] : '';
        $this->endDate = isset($params['endDate']) ? $params['endDate'] : '';

        if (isset($params['salesTerms']) && count($params['salesTerms'])) {
            foreach ($params['salesTerms'] as $salesTerm) {
                $this->SalesTerms[] = new SalesTerm($salesTerm);
            }
        }
    }

    /**
     * @return mixed|string
     */
    public function getBookingKey()
    {
        return $this->bookingKey;
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * @return array
     */
    public function getNewSalesTerms()
    {
        $newSalesTerms = [];

        if (count($this->SalesTerms)) {
            foreach ($this->SalesTerms as $SalesTerm) {
                $newSalesTerms[$SalesTerm->getType()] = $SalesTerm->getSSSalesTerm();
            }
        }

        return $newSalesTerms;
    }
}