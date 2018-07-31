<?php

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Объект штрафов для услуги
 *
 * @property $id    int(11) Auto Increment    id записи
 * @property $serviceId    bigint(20)    Идентификатор сервиса
 * @property $createdAt    timestamp [CURRENT_TIMESTAMP]    Дата время записи
 * @property $descriptionRU    text NULL    Текст условий отмены на русском
 * @property $descriptionEN    text NULL    Текст условий отмены на английском
 * @property $supplierAmount    decimal(10,2) NULL    Сумма штрафа поставщика
 * @property $supplierCurrency    varchar(3) NULL    Валюта поставщика
 * @property $clientAmount    decimal(10,2) NULL    Сумма штрафа клиента
 * @property $clientCurrency    varchar(3) NULL    Валюта клиента
 */
class OrderServicePenalty extends CActiveRecord
{
    use MultiLang;

    /**
     * @var Currency []
     */
    protected $currenciesToConvertTo = [];

    protected $descriptionRU;
    protected $descriptionEN;
    protected $supplierAmount;
    protected $supplierCurrency;
    protected $clientAmount;
    protected $clientCurrency;

    public function tableName()
    {
        return 'kt_service_penalties';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function getDescription()
    {
        return ($this->getLang() == 'ru') ? $this->descriptionRU : $this->descriptionEN;
    }

    /**
     * @return mixed
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param mixed $serviceId
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;
    }

    /**
     *
     * @param OrdersServices $service
     */
    public function bindService(OrdersServices $service)
    {
        $this->serviceId = $service->getServiceID();
    }

    public function fromClientCancelPenalty(AbstractCancelPenalty $cancelPenalty)
    {
        $this->descriptionRU = $cancelPenalty->getDescription(); // Текст условий отмены на русском
        $cancelPenalty->setLang('en');
        $this->descriptionEN = $cancelPenalty->getDescription(); // Текст условий отмены на английском
        $this->supplierAmount = null;                            // Сумма штрафа поставщика
        $this->supplierCurrency = null;                          // Валюта поставщика
        $this->clientAmount = $cancelPenalty->getAmount();                              // Сумма штрафа клиента
        $this->clientCurrency = $cancelPenalty->getCurrencyCode();                            //
    }

    /**
     * Когда создали
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return new DateTime($this->createdAt);
    }

    /**
     * @param $penalties
     * @throws InvalidArgumentException
     */
    public function fromArray($penalties)
    {
        // валюта поставщика должна совпадать с новой
        if (!$this->isNewRecord && $this->supplierCurrency && isset($penalties['supplier']['currency'])) {
            $supplierCurrency = CurrencyStorage::findByString($this->supplierCurrency);
            $newCurrency = CurrencyStorage::findByString($penalties['supplier']['currency']);

            if (!$supplierCurrency->getId() != $newCurrency->getId()) {
                throw new InvalidArgumentException('Валюты поставщика в штрафах должны совпадать', OrdersErrors::CANNOT_CHANGE_SUPPLIER_PENALTY_CURRENCY);
            }
        }

        $this->clientAmount = isset($penalties['client']['amount']) ? $penalties['client']['amount'] : null;
        $this->clientCurrency = isset($penalties['client']['currency']) ? $penalties['client']['currency'] : null;

        $this->supplierAmount = isset($penalties['supplier']['amount']) ? $penalties['supplier']['amount'] : null;
        $this->supplierCurrency = isset($penalties['supplier']['currency']) ? $penalties['supplier']['currency'] : null;
    }

    /**
     * Массив в виде so_servicePenalty
     * @return array
     */
    public function toArray()
    {
        $rval = [];

        $rval['description'] = $this->getDescription();
        $rval['createdAt'] = $this->getCreatedAt()->format('Y-m-d H:i:s');

        foreach ($this->currenciesToConvertTo as $currencyName => $currency) {
            $rval['supplier'][$currencyName]['amount'] = CurrencyRates::getInstance()->calculateInCurrencyByIds($this->supplierAmount, $this->supplierCurrency, $currency->getId());
            $rval['supplier'][$currencyName]['currency'] = $currency->getCode();
        }

        foreach ($this->currenciesToConvertTo as $currencyName => $currency) {
            $rval['client'][$currencyName]['amount'] = CurrencyRates::getInstance()->calculateInCurrencyByIds($this->clientAmount, $this->clientCurrency, $currency->getId());
            $rval['client'][$currencyName]['currency'] = $currency->getCode();
        }

        return $rval;
    }

    /**
     * Установка валюты, в которую конвертировать штрафы
     * @param $name string имя
     * @param Currency $Currency
     */
    public function addCurrencyToConvert($name, Currency $Currency)
    {
        $this->currenciesToConvertTo[$name] = $Currency;
    }

    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('clientAmount', new Assert\NotNull(array('message' => OrdersErrors::INCORRECT_PENALTIES)));
        $metadata->addPropertyConstraint('supplierAmount', new Assert\NotNull(array('message' => OrdersErrors::INCORRECT_PENALTIES)));
        $metadata->addPropertyConstraint('clientCurrency', new Assert\NotNull(array('message' => OrdersErrors::INCORRECT_PENALTIES)));
        $metadata->addPropertyConstraint('supplierCurrency', new Assert\NotNull(array('message' => OrdersErrors::INCORRECT_PENALTIES)));
    }
}