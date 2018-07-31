<?php

/**
 * Модель счета в услуге
 *
 * @property $InvoiceID
 * @property $ServiceID
 * @property $ServicePrice
 * @property $Partial
 * @property $CurrencyID
 *
 * @property Currency $Currency
 * @property Invoice $Invoice
 * @property OrdersServices $OrdersServices
 */
class InvoiceService extends CActiveRecord
{
    public function tableName()
    {
        return 'kt_invoices_services';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'OrdersServices' => array(self::BELONGS_TO, 'OrdersServices', 'ServiceID'),
            'Invoice' => array(self::BELONGS_TO, 'Invoice', 'InvoiceID'),
            'Currency' => array(self::BELONGS_TO, 'Currency', 'CurrencyID')
        );
    }

    /**
     * @return OrdersServices|null
     */
    public function getOrdersService()
    {
        return $this->OrdersServices;
    }

    /**
     * @return mixed
     */
    public function getInvoiceID()
    {
        return $this->InvoiceID;
    }

    /**
     * @return Invoice|null
     */
    public function getInvoice()
    {
        return $this->Invoice;
    }

    /**
     * @return mixed
     */
    public function getServicePrice()
    {
        return $this->ServicePrice;
    }

    /**
     * @return mixed
     */
    public function getPartial()
    {
        return $this->Partial;
    }

    /**
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->Currency;
    }

    /**
     * @return float сумма счета
     */
    public function getSum()
    {
        return $this->ServicePrice;
    }

    /**
     * @param mixed $InvoiceID
     */
    public function setInvoiceId($InvoiceID)
    {
        $this->InvoiceID = $InvoiceID;
    }

    /**
     * Задать сумму и валюту счета
     * @param $amount
     * @param $currency
     */
    public function setSum($amount, Currency $currency)
    {
        $this->ServicePrice = $amount;
        $this->CurrencyID = $currency->getId();
    }

    public function bindOrderService(OrdersServices $ordersServices)
    {
        $this->ServiceID = $ordersServices->getServiceID();
    }

    /**
     * Флаг частичности
     * @param $partial
     */
    public function setPartial($partial)
    {
        if ($partial) {
            $this->Partial = 1;
        } else {
            $this->Partial = 0;
        }
    }
}