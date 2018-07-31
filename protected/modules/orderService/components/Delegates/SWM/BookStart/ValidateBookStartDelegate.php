<?php

/**
 * валидация по количеству человек из оффера и туристам
 */
class ValidateBookStartDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // посмотрим сколько туристов в услуге
//        $touristsCnt = count($OrdersService->getOrderTourists());

        // посмотрим сколько туристов в оффере
        $Offer = $OrdersService->getOffer();
        $OrderTourists = $OrdersService->getOrderTourists();
        $Tourists = [];

        if (count($OrderTourists)) {
            foreach ($OrderTourists as $OrderTourist) {
                $Tourists[] = $OrderTourist->getTourist();
            }
        };

        if (!$Offer->checkTouristAges($Tourists, true)) {
            $this->setError(OrdersErrors::NO_LINKED_TOURISTS_TO_SERVICES);
            return null;
        }

        if ($OrdersService->inStatus(OrdersServices::STATUS_MANUAL) && $params['userProfile']['userType'] != 1) {
            $this->setError(OrdersErrors::BOOKING_IN_MANUAL_STATUS);
            return null;
        }

        // необходимо проверить не бронировалась ли услуга ранее
        if (!$OrdersService->getOffer()->canBeBooked()) {
            $this->setError(OrdersErrors::SERVICE_ALREADY_BOOKED);
            return null;
        }
    }

}