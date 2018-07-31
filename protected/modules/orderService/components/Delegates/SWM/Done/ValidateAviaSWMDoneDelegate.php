<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/14/16
 * Time: 3:14 PM
 */
class ValidateAviaSWMDoneDelegate extends AbstractDelegate
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

        if ($OrdersServices->getServiceType() == 2) {
            $AviaTickets = AviaTicketRepository::getTicketsByServiceId($params['serviceId']);

            if (count($AviaTickets)) {
                foreach ($AviaTickets as $AviaTicket) {
                    if (!$AviaTicket->isIssued()) {
                        $this->setError(OrdersErrors::ALL_TICKETS_MUST_BE_ISSUED);
                        return null;
                    }
                }
            } else {
                $this->setError(OrdersErrors::SERVICE_HAS_NO_TICKETS);
                return null;
            }
        }
    }
}