<?php

/**
 * Структура ответа бронирования
 * Class AccomodationBookResponse
 */
class AccomodationBookResponse
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

    protected $refNumber;
    protected $orderId;
    protected $processId;
    protected $status;
    protected $errors;
    protected $warnings;
    protected $gateId;

    public function __construct(array $params)
    {
        $this->refNumber = isset($params['refNumber']) ? $params['refNumber'] : '';
        $this->orderId = isset($params['orderId']) ? $params['orderId'] : '';
        $this->processId = isset($params['processId']) ? $params['processId'] : '';
        $this->status = isset($params['status']) ? $params['status'] : '';
        $this->errors = isset($params['errors']) ? $params['errors'] : [];
        $this->warnings = isset($params['warnings']) ? $params['warnings'] : [];
        $this->gateId = SupplierFactory::GPTS_ENGINE;
    }


    public function hasErrors()
    {
        return in_array($this->status, array(self::STATUS_ERROR, self::STATUS_REJECTED));
    }


    /**
     * Есть ли $message в сообщении об ошибке
     * @param $message
     * @return bool
     */
    public function hasMessageOnError($message){
        foreach ($this->errors as $error) {
            if (preg_match($message, $error)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Вернёт распарсенные коды ошибок
     * @return array
     */
    public function getClearErrorsCode()
    {
        $arr = [];
        foreach ($this->errors as $error) {
            $arr[] = preg_split('/[\s,]+/', $error)[0];
        }
        return $arr;
    }

    public function confirmed()
    {
        return $this->status == self::STATUS_CONFIRMED;
    }

    /**
     * @return mixed|string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @return mixed|string
     */
    public function getRefNumber()
    {
        return $this->refNumber;
    }

    /**
     * @return mixed|string
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * @return int
     */
    public function getGateId()
    {
        return $this->gateId;
    }

    /**
     * @return array|mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function toArray()
    {
        return [
            'engine' => [
                'GPTS_order_ref' => $this->orderId
                , 'GPTS_service_ref' => $this->processId
            ]
            , 'reservationNumber' => $this->refNumber
            , 'gateId' => $this->gateId
        ];
    }
}