<?php

/**
 * Class OffersFactory
 * Реализация фабрики классов предложения услуги
 */
class OffersFactory
{
    const DEFAULT_OFFER_CLASS_NAME = 'Offer';

    const ACCOMMODATION_OFFER_TYPE = 1;
    const FLIGHT_OFFER_TYPE = 2;
    const TRANSFER_OFFER_TYPE = 3;
    const VISA_OFFER_TYPE = 4;
    const CAR_RENT_OFFER_TYPE = 5;
    const TOUR_OFFER_TYPE = 6;
    const RAILWAY_OFFER_TYPE = 7;
    const PACKET_OFFER_TYPE = 8;
    const INSURANCE_OFFER_TYPE = 9;
    const EXCURSION_OFFER_TYPE = 10;
    const MEAL_OFFER_TYPE = 11;
    const GUIDE_OFFER_TYPE = 12;
    const EXTRA_OFFER_TYPE = 13;

    private static $_offersClasses = [
        self::TOUR_OFFER_TYPE => 'ToursOffer',
        self::FLIGHT_OFFER_TYPE => 'FlightOffer',
        self::CAR_RENT_OFFER_TYPE => 'CarRentOffer',
        self::RAILWAY_OFFER_TYPE => 'RailwayOffer',
        self::PACKET_OFFER_TYPE => 'PacketOffer',
        self::ACCOMMODATION_OFFER_TYPE => 'AccommodationOffer',
        self::TRANSFER_OFFER_TYPE => 'TransferOffer',
        self::VISA_OFFER_TYPE => 'VisaOffer',
        self::INSURANCE_OFFER_TYPE => 'InsuranceOffer',
        self::EXCURSION_OFFER_TYPE => 'ExcursionOffer',
        self::MEAL_OFFER_TYPE => 'MealOffer',
        self::GUIDE_OFFER_TYPE => 'GuideOffer',
        self::EXTRA_OFFER_TYPE => 'ExtraOffer'
    ];

    /**
     * Создание объекта предложения в зависимости от указанного типа услуги
     * @param $type string тип услуги
     * @return mixed объект предложения
     */
    public static function createOffer($type)
    {

        if (!array_key_exists($type, self::$_offersClasses)) {
            /*$className = self::DEFAULT_OFFER_CLASS_NAME;
            return new $className;*/
            return false;
        }

        return new self::$_offersClasses[$type];
    }
}