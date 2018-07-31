<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/14/16
 * Time: 3:44 PM
 */
class ValidateHotelSWMDoneDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        if ($OrdersServices->getServiceType() == 1) {
            $Offer = $OrdersServices->getOffer();

            $HotelReservation = $Offer->getHotelReservation();

            if (!is_null($HotelReservation)) {
                if (!($HotelReservation->hasHotelVoucher() && $HotelReservation->isActive())) {
                    $this->setError(OrdersErrors::RESERVATION_NOT_ACTIVE_OR_HAS_NO_VOUCHERS);
                    return null;
                }
            } else {
                $this->setError(OrdersErrors::SERVICE_HAS_NO_ACTIVE_RESERVATIONS);
                return null;
            }
        }
    }
}