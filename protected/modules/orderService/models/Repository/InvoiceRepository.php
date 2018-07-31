<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 21.12.16
 * Time: 16:51
 */
class InvoiceRepository
{
    /**
     * Поиск счета по ID
     * @param $invoiceId
     * @return Invoice|null
     */
    public static function getInvoiceById($invoiceId)
    {
        return Invoice::model()->findByPk($invoiceId);
    }

    /**
     * Получение счета по любому ID
     * @param $utkId
     * @param $ktId
     * @return Invoice|null
     */
    public static function getByIds($utkId, $ktId)
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition("InvoiceID = '$ktId'");
        $criteria->addCondition("InvoiceID_UTK = '$utkId'", 'OR');

        return Invoice::model()->find($criteria);
    }
}