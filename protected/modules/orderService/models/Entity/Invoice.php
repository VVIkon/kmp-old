<?php

/**
 * Модель счета
 *
 * @property $InvoiceID
 * @property $InvoiceID_UTK
 * @property $InvoiceDate
 * @property $HardcopyURL
 * @property $InvoiceAmount
 * @property $CurrencyID
 * @property $Status
 *
 * @property InvoiceService[] $InvoiceServices
 */
class Invoice extends CActiveRecord implements Serializable
{
    const STATUS_WAIT = 1;
    const STATUS_SET = 2;
    const STATUS_P_PAID = 3;
    const STATUS_PAID = 4;
    const STATUS_CANCELLED = 5;

    /**
     * @var Currency
     */
    private $Currency;

    public function tableName()
    {
        return 'kt_invoices';
    }

    public function relations()
    {
        return array(
            'InvoiceServices' => array(self::HAS_MANY, 'InvoiceService', 'InvoiceID'),
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }


    public function init()
    {
        $this->Currency = CurrencyStorage::findByString($this->CurrencyID);
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * Установка цены в валюте
     * @param $amount
     * @param Currency $currency
     */
    public function setAmount($amount, Currency $currency)
    {
        $this->Currency = $currency;
        $this->InvoiceAmount = $amount;
        $this->CurrencyID = $currency->getId();
    }

    public function wait()
    {
        $this->Status = self::STATUS_WAIT;
    }

    /**
     * Отмена счета
     */
    public function cancel()
    {
        $this->Status = self::STATUS_CANCELLED;
        $this->save();

        $InvoiceServices = $this->InvoiceServices;

        if (count($InvoiceServices)) {
            foreach ($InvoiceServices as $invoiceService) {
                $orderService = $invoiceService->getOrdersService();
                $orderService->calculateRestPaymentAmount();
                $orderService->save();
            }
        }
    }

    /**
     * @return mixed
     */
    public function getInvoiceIDUTK()
    {
        return $this->InvoiceID_UTK;
    }

    /**
     * @return mixed
     */
    public function getInvoiceId()
    {
        return $this->InvoiceID;
    }

    /**
     *
     * @return DateTime
     */
    public function getInvoiceDate()
    {
        return new DateTime($this->InvoiceDate);
    }

    public function getInvoiceStatus()
    {
        return $this->Status;
    }

    public function getCurrency()
    {
        return CurrencyStorage::findByString($this->CurrencyID);
    }

    public function save($runValidation = true, $attributes = null)
    {
        return parent::save(false);
    }

    public function __toString()
    {
        return $this->InvoiceAmount . ' ' . $this->Currency->getCode();
    }

    /**
     * Сериализация для передачи через контекст делегатов
     * @return string
     */
    public function serialize()
    {
        return serialize([
            $this->InvoiceID,
            $this->InvoiceID_UTK,
            $this->InvoiceDate,
            $this->HardcopyURL,
            $this->InvoiceAmount,
            $this->CurrencyID,
            $this->Status
        ]);
    }

    /**
     * Десериализация
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list(
            $this->InvoiceID,
            $this->InvoiceID_UTK,
            $this->InvoiceDate,
            $this->HardcopyURL,
            $this->InvoiceAmount,
            $this->CurrencyID,
            $this->Status
            ) = unserialize($serialized);

        $this->isNewRecord = false;
    }

    public function fromUtkArray($arr)
    {
        if (!empty($arr['invoiceDate'])) {
            $Date = new DateTime($arr['invoiceDate']);
            $this->InvoiceDate = $Date->format('Y-m-d H:i:s');
        }

        $this->InvoiceID_UTK = !empty($arr['invoiceIdUTK']) ? $arr['invoiceIdUTK'] : null;
    }

    /**
     * Хитрое удаление счета
     * после удаления счета нужно пересчитать
     * остатки к платежу всех услуг
     */
    public function delete()
    {
        // списочек ID услуг, которые надо будет перешерстить
        $OrderServicesIdsToCalcRestPaymentAmount = [];

        // все счето-услуги
        $InvoiceServices = $this->InvoiceServices;

        // найдем все IDшники затрагиваемых услуг
        if (count($InvoiceServices)) {
            foreach ($InvoiceServices as $InvoiceService) {
                $OrderServicesIdsToCalcRestPaymentAmount[] = $InvoiceService->getOrdersService()->getServiceID();
            }
        }

        // сносим все к чертям
        parent::delete();

        // делаем пересчет остатков для выставления счета
        if (count($OrderServicesIdsToCalcRestPaymentAmount)) {
            foreach ($OrderServicesIdsToCalcRestPaymentAmount as $OrderServicesIdToCalcRestPaymentAmount) {
                $OrderService = OrdersServicesRepository::findById($OrderServicesIdToCalcRestPaymentAmount);

                $OrderService->calculateRestPaymentAmount();
                $OrderService->save();
            }
        }
    }
}