<?php

/**
 * Class OfferFindersFactory
 * Реализация фабрики классов для формирования задания поиска предложений
 */
class OfferFindersFactory
{
    const DEFAULT_OFFER_CLASS_NAME = 'OfferFinder';

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

    private static $offerFinderClasses = [self::TOUR_OFFER_TYPE    => '',
                                        self::FLIGHT_OFFER_TYPE        => 'FlightOfferFinder',
                                        self::CAR_RENT_OFFER_TYPE      => '',
                                        self::RAILWAY_OFFER_TYPE       => '',
                                        self::PACKET_OFFER_TYPE        => '',
                                        self::ACCOMMODATION_OFFER_TYPE => 'AccomodationOfferFinder',
                                        self::TRANSFER_OFFER_TYPE      => '',
                                        self::VISA_OFFER_TYPE          => '',
                                        self::INSURANCE_OFFER_TYPE     => '',
                                        self::EXCURSION_OFFER_TYPE     => '',
                                        self::MEAL_OFFER_TYPE          => '',
                                        self::GUIDE_OFFER_TYPE         => '',
                                        self::EXTRA_OFFER_TYPE         => ''
    ];

    /**
     * Создание объекта поиска предложения в зависимости от указанного типа услуги
     * @param $type string тип услуги
     * @return mixed объект предложения
     */
    public static function createOfferFinder($type, $module)
    {

        if (!array_key_exists($type, self::$offerFinderClasses)) {
            return false;
        }

        return new self::$offerFinderClasses[$type]($module);
    }

    public static function getOfferTypeByClassName($className)
    {
        $offerType = array_search($className, OfferFindersFactory::$offerFinderClasses);
        return $offerType;
    }
}