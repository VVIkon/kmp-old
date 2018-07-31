<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 21.12.16
 * Time: 18:11
 */
class UTKInvoiceRepository
{
    /**
     * @param $invoiceId
     * @return UTKInvoice|null
     */
    public static function getByInvoiceId($invoiceId)
    {
        $InvoiceServices = InvoiceService::model()->with('OrdersServices.OrderModel', 'Invoice')->findAllByAttributes(['InvoiceID' => $invoiceId]);

        if (is_null($InvoiceServices)) {
            return null;
        }

        $UTKInvoice = new UTKInvoice();
        $UTKInvoice->setInvoiceServices($InvoiceServices);

        return $UTKInvoice;
    }
}