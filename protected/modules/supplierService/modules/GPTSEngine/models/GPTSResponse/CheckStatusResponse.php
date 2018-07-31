<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 28.12.16
 * Time: 17:14
 */
class CheckStatusResponse
{
    protected $processStatus;

    /**
     * @var Currency
     */
    protected $Currency;

    protected $value;

    /**
     * @var string
     */
    protected $processId;

    public function __construct(array $params)
    {
        $this->processId = isset($params['processId']) ? $params['processId'] : null;
        $this->status = isset($params['processStatus']) ? $params['processStatus'] : null;
        $this->value = isset($params['price']['value']) ? $params['price']['value'] : null;

        $this->Currency = CurrencyStorage::findByString($params['price']['currency']);
        if (!$this->Currency) {
            throw new InvalidArgumentException();
        }
    }

    protected function getSSSalesTerms()
    {

    }

    protected function getKTStatus()
    {
        return StatusMapper::getInstance()->getKtByUtkStatus($this->status, 1);
    }

    protected function getGPTS_KTStatus()
    {
        return StatusMapper::getInstance()->getGptsByKtStatus($this->status, 4);
    }

    public function toArray()
    {
        return [
            'status' => $this->getGPTS_KTStatus(), // Статус процесса
            'price' => [
                'currency' => $this->Currency->getCode(),
                'value' => $this->value
            ],
            'processId' => $this->processId
        ];
    }
}