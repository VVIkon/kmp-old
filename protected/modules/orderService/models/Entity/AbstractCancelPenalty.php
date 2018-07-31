<?php

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 *
 * @property $id    bigint(20) Auto Increment    id записи
 * @property $offerId    bigint(20)    Идентификатор брони
 * @property $dateTimeFrom    datetime NULL    Дата, начала штрафного периода
 * @property $dateTimeTo    datetime NULL    Дата, окончания штрафного периода
 * @property $type    varchar(10)    Тип штрафа - supplier/ client ( штраф поставщика за отмену или штраф КМП для клиента за отмену )
 * @property $descriptionRU    text NULL    Текст условий отмены на русском
 * @property $descriptionEN    text NULL    Текст условий отмены на английском
 * @property $amount    decimal(10,2) NULL    Сумма штрафа
 * @property $currency    varchar(3) NULL    Валюта штрафа
 * @property $commissionCurrency    varchar(3) NULL    Валюта штрафа клиента
 * @property $commissionAmount    decimal(10,2) NULL    Сумма штрафа клиента
 * @property $commissionPercent    decimal(5,2) NULL    0 = commission.amount-абсолютная сумма, 1 = commission.amount- % от price.amount
 */
abstract class AbstractCancelPenalty extends CActiveRecord
{
    use MultiLang, CurrencyTrait;

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function isClient()
    {
        return $this->type == 'client';
    }

    public function isSupplier()
    {
        return $this->type == 'supplier';
    }

    /**
     * @param mixed $offerId
     */
    public function setOfferId($offerId)
    {
        $this->offerId = $offerId;
    }

    /**
     * Получение описания в зависимости от языка
     * @return mixed
     * @throws Exception
     */
    public function getDescription()
    {
        return ($this->getLang() == 'ru') ? $this->descriptionRU : $this->descriptionEN;
    }

    /**
     * запись описания
     * @param $description
     */
    public function setDescription($description)
    {
        $this->descriptionEN = $description;
        $this->descriptionRU = $description;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setCurrencyCode($currency)
    {
        $this->currency = $currency;
    }

    public function getCurrencyCode()
    {
        return $this->currency;
    }

    /**
     * Сохранение из массива
     * @param array $ssCancelPenalty
     */
    public function fromArray(array $ssCancelPenalty)
    {
        $CurrencyRates = CurrencyRates::getInstance();
        if (isset($ssCancelPenalty['description'])) {
            $this->setDescription($ssCancelPenalty['description']);
        }
        $this->dateTimeFrom = StdLib::nvl($ssCancelPenalty['dateFrom']);
        $this->dateTimeTo = StdLib::nvl($ssCancelPenalty['dateTo']);
        $this->currency = isset($ssCancelPenalty['penalty']['currency']) ? $CurrencyRates->getIdByCode($ssCancelPenalty['penalty']['currency']) : null;
        $this->amount = StdLib::nvl($ssCancelPenalty['penalty']['amount']);
    }

    /**
     *
     * @return array
     */
    public function getArray()
    {
        $ssCancelPenalty = [
            'dateFrom' => $this->dateTimeFrom,     // Начало действия периода штрафа
            'dateTo' => $this->dateTimeTo,        // Конец действия периода штрафа
            'description' => $this->getDescription(),      // Текст условий отмены
        ];

        $cancelPenaltyCurrencyCode = CurrencyStorage::findByString($this->currency)->getCode();

        // если задана валюта, в которой хотелось бы получить штраф
        if ($this->Currency) {
            $CurrencyRates = CurrencyRates::getInstance();

            $ssCancelPenalty['penalty'] = [
                'currency' => $this->Currency->getCode(),              // Валюта штрафа
                'amount' => $CurrencyRates->calculateInCurrency($this->amount, $cancelPenaltyCurrencyCode, $this->Currency->getCode()),
            ];
        } else {
            $ssCancelPenalty['penalty'] = [
                'currency' => $cancelPenaltyCurrencyCode,              // Валюта штрафа
                'amount' => $this->amount
            ];
        }

        return $ssCancelPenalty;
    }
}