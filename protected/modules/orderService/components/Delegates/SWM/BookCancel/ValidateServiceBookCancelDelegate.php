<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/4/16
 * Time: 4:23 PM
 */
class ValidateServiceBookCancelDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        /*    DEPRECATED in v3.1

        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        $Offer = $OrdersServices->getOffer();

        if (!$Offer->hasCancelAbility()) {
            $this->setError(OrdersErrors::SERVICE_CANCEL_IMPOSSIBLE);
            return null;
        }

        $Invoices = $OrdersServices->getInvoices();

        if (count($Invoices)) {
            foreach ($Invoices as $Invoice) {
                if ($Invoice->getStatus() != Invoice::STATUS_CANCELLED) {
                    $this->setError(OrdersErrors::SERVICE_CANCEL_IMPOSSIBLE_DUE_TO_INVOICES);
                    return null;
                }
            }
        }
        */
    }
}