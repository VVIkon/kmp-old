<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/7/16
 * Time: 11:23 AM
 */
class PrepareBookResponse
{
    const STATUS_PENDING = 0;
    const STATUS_CONFIRMATION_PENDING = 1;
    const STATUS_CONFIRMED = 2;
    const STATUS_CANCELLATION_PENDING = 3;
    const STATUS_CANCELED = 4;
    const STATUS_REJECTED = 5;
    const STATUS_ERROR = 6;
    const STATUS_CANCELLATION_REJECTED = 7;
    const STATUS_ON_CONFIRM = 8;
    const STATUS_CANCELED_WITHOUT_FEE = 9;
    const STATUS_QUOTED = 10;
    const STATUS_ISSUED = 11;
    const STATUS_ESTIMATED = 12;

    protected $errorStatuses = [self::STATUS_ERROR];

    protected $warnings; // (array[string], optional): Vendor warnings,
    protected $processId; // (string, optional): Order process id,
    protected $status; // (string, optional): Status values: 0 - PENDING, 1 - CONFIRMATION_PENDING, 2 - CONFIRMED, 3 - CANCELLATION_PENDING, 4 - CANCELED, 5 - REJECTED, 6 - ERROR, 7 - CANCELLATION_REJECTED, 8 - ON_CONFIRM, 9 - CANCELED_WITHOUT_FEE, 10 - QUOTED, 11- ISSUED, 12 - ESTIMATED,
    protected $errors; // (array[string], optional): Vendor errors,
    protected $vendorMessages; // (array[string], optional): Vendor messages,

    /**
     * @var CancellationItemWithConversion []
     */
    protected $Cancellations = []; // (array[CancellationItemWithConversion], optional): Cancellation rules,

    /**
     * @var SalesTerm []
     */
    protected $CurrentSalesTerms = []; // (array[SalesTerm], optional): Current sales terms

    public function __construct(array $params)
    {
        $this->warnings = isset($params['warnings']) ? $params['warnings'] : '';
        $this->processId = isset($params['processId']) ? $params['processId'] : '';
        $this->status = isset($params['status']) ? $params['status'] : '';
        $this->errors = isset($params['errors']) ? $params['errors'] : '';
        $this->vendorMessages = isset($params['vendorMessages']) ? $params['vendorMessages'] : '';

        if (isset($params['cancellations']) && count($params['cancellations'])) {
            foreach ($params['cancellations'] as $cancellation) {
                $this->Cancellations[] = new CancellationItemWithConversion($cancellation);
            }
        }

        if (isset($params['currentSalesTerms']) && count($params['currentSalesTerms'])) {
            foreach ($params['currentSalesTerms'] as $currentSalesTerm) {
                $this->CurrentSalesTerms[] = new SalesTerm($currentSalesTerm);
            }
        }
    }

    public function offerRejected()
    {
        return $this->status == self::STATUS_REJECTED;
    }

    public function confirmed()
    {
        return $this->status == self::STATUS_CONFIRMED;
    }


    public function hasErrors()
    {
        return in_array($this->status, $this->errorStatuses);
    }

    /**
     * @return mixed|string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Ищем цену поставщика в ответе шлюза
     * @return int|Price
     */
    public function findClientPrice()
    {
        if (count($this->CurrentSalesTerms)) {
            foreach ($this->CurrentSalesTerms as $CurrentSalesTerm) {
                if ($CurrentSalesTerm->getType() == 'client') {
                    return $CurrentSalesTerm->getPrice();
                }
            }
        }

        return false;
    }


    public function getNewSaleTerms()
    {

    }

    /**
     * @return CancellationItemWithConversion[]
     */
    public function getCancellations()
    {
        $cancellationsArr = [];

        if (count($this->Cancellations)) {
            foreach ($this->Cancellations as $Cancellation) {
                $cancellationsArr[] = $Cancellation->toArray();
            }
        }

        return $cancellationsArr;
    }

    /**
     *
     * @return array
     */
    public function getSalesTermsAsArray()
    {
        if (count($this->CurrentSalesTerms)) {
            $saleTermsArray = [];

            foreach ($this->CurrentSalesTerms as $CurrentSalesTerm) {
                $saleTermsArray[$CurrentSalesTerm->getType()] = $CurrentSalesTerm->getSSSalesTerm();
            }

            return $saleTermsArray;
        } else {
            return [];
        }
    }

    /**
     * @return mixed|string
     */
    public function getVendorMessages()
    {
        return $this->vendorMessages;
    }

    /**
     * @return mixed|string
     */
    public function getErrors()
    {
        return $this->errors;
    }
}