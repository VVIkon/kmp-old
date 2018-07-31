<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/5/16
 * Time: 4:41 PM
 */
class ValidateHotelServiceBookCancelDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        // только для проживания
        if ($OrdersServices->getServiceType() == 1) {
            $HotelOffer = $OrdersServices->getOffer();

            // проверим bookData
            if (!count($HotelOffer->getBookData())) {
                $this->setError(OrdersErrors::SERVICE_CANCEL_IMPOSSIBLE_DUE_NO_BOOK_DATA);
                return null;
            }
        }
    }
}