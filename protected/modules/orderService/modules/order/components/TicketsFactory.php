<?php

/**
 * Class TicketsFactory
 * Реализация фабрики классов билетов по услуге
 */
class TicketsFactory
{
    const DEFAULT_TICKET_CLASS_NAME = 'ServiceTicket';

    const ACCMD_TICKET_TYPE = 1;
    const FLIGHT_TICKET_TYPE = 2;
    const TRANSFER_TICKET_TYPE = 3;
    const VISA_TICKET_TYPE = 4;
    const CAR_RENT_TICKET_TYPE = 5;
    const TOUR_TICKET_TYPE = 6;
    const RAILWAY_TICKET_TYPE = 7;
    const PACKET_TICKET_TYPE = 8;
    const INSURANCE_TICKET_TYPE = 9;
    const EXCURSION_TICKET_TYPE = 10;
    const MEAL_TICKET_TYPE = 11;
    const GUIDE_TICKET_TYPE = 12;
    const EXTRA_TICKET_TYPE = 13;
    const UTK_TICKET_TYPE = 101;


    private static $ticketsClasses = [
                        self::FLIGHT_TICKET_TYPE   => 'ServiceFlTicket',
    ];

    /**
     * Создание объекта билета в зависимости от указанного типа услуги
     * @param $type int тип услуги
     * @return mixed объект услуги
     */
    public static function createTicket($type) {

        if (!array_key_exists($type, self::$ticketsClasses)) {
            /*$className = self::DEFAULT_SERVICE_CLASS_NAME;
            return new $className;*/
            return false;
        }

        return new self::$ticketsClasses[$type];
    }

    /**
     * Проверка на существования связи типа билета
     * и соответствующего этому типу класса
     * @param $serviceType int тип услуги
     * @return bool
     */
    public static function isTicketTypeExist($ticketType) {

        if (!is_numeric($ticketType)) {
            return false;
        }

        if (!array_key_exists(intval($ticketType),self::$ticketsClasses)) {
            return false;
        }

        return true;
    }
}