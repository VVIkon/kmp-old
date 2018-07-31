<?php

/**
 * Репозиторий счетов
 */
class InvoiceServiceRepository
{
    /**
     * Получение всех счетов по ID услуги
     * @param $serviceId
     * @return InvoiceService []|[]
     */
    public static function getAllByServiceId($serviceId)
    {
        return InvoiceService::model()->findAllByAttributes(['ServiceID' => $serviceId]);
    }

    /**
     * Получение всех счетов по ID услуги
     * @param $serviceId
     * @return InvoiceService []|[]
     */
    public static function getAllNotCancelledByServiceId($serviceId)
    {
        return InvoiceService::model()->with(array('Invoice' => array('condition' => 'Status <> ' . Invoice::STATUS_CANCELLED)))->findAllByAttributes(['ServiceID' => $serviceId]);
    }
}