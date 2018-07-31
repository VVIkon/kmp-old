<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/5/16
 * Time: 4:12 PM
 */
class ValidateAviaServiceBookCancelDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        // только для авиа
        if ($OrdersServices->getServiceType() == 2) {
            // выберем оффер, чтобы посмотреть билеты

            $AviaOffer = $OrdersServices->getOffer();
            $AviaOffer->addCurrency('view', CurrencyStorage::getById(643));

            $AviaOfferArr = $AviaOffer->toArray();

            // если есть билеты - переберем их
            if (!empty($AviaOfferArr['pnr']['tickets']) && is_array($AviaOfferArr['pnr']['tickets'])) {
                foreach ($AviaOfferArr['pnr']['tickets'] as $ticket) {
                    // если билеты не в статусах VOIDED/RETURNED/CHANGED выдадим ошибку
                    if (!in_array($ticket->status, [ServiceFlTicket::STATUS_CHANGED, ServiceFlTicket::STATUS_RETURNED, ServiceFlTicket::STATUS_VOIDED])) {
                        $this->setError(OrdersErrors::SERVICE_CANCEL_IMPOSSIBLE_DUE_TO_TICKETS);
                        return null;
                    }
                }
            }

            // проверим bookData
            if (!count($AviaOffer->getBookData())) {
                $this->setError(OrdersErrors::SERVICE_CANCEL_IMPOSSIBLE_DUE_NO_BOOK_DATA);
                return null;
            }
        }
    }
}