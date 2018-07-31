<?php

/**
 *
 */
class AviaTicketRepository
{
    /**
     * Получение авиа билетов
     * @param $serviceId
     * @return AviaTicket []
     */
    public static function getTicketsByServiceId($serviceId)
    {
        return AviaTicket::model()->findAllByAttributes(array('ServiceID' => $serviceId));
    }

    /**
     * Получение билетов по номерам
     * @param $ticketNumber
     * @return AviaTicket []
     */
    public static function getAllByTicketNumber($ticketNumber)
    {
        return AviaTicket::model()->findAllByAttributes(array('ticketNumber' => $ticketNumber));
    }
}