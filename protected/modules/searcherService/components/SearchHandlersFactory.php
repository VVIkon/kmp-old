<?php

/**
 * Class SearchHandlersFactory
 * Реализация фабрики классов обработчков поисковых команд
 */
class SearchHandlersFactory
{
    const DEFAULT_OFFER_CLASS_NAME = 'SearchHandler';

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

    private static $searchHandlerClasses = [self::TOUR_OFFER_TYPE    => '',
                                        self::FLIGHT_OFFER_TYPE        => 'FlightSearchHandler',
                                        self::CAR_RENT_OFFER_TYPE      => '',
                                        self::RAILWAY_OFFER_TYPE       => '',
                                        self::PACKET_OFFER_TYPE        => '',
                                        self::ACCOMMODATION_OFFER_TYPE => 'HotelSearchHandler',
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
    public static function createSearchHandler($type, $module)
    {
        if (!array_key_exists($type, self::$searchHandlerClasses)) {
            return false;
        }

        return new self::$searchHandlerClasses[$type]($module);
    }

    public static function getSearchHandlerTypeByClassName($className)
    {
        $handlerType = array_search($className, SearchHandlersFactory::$searchHandlerClasses);
        return $handlerType;
    }
}