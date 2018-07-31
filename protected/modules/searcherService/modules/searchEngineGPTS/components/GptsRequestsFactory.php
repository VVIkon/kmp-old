<?php

/**
 * Class GptsRequestsFactory
 * Реализация фабрики классов запросов к провайдеру GPTS
 */
class GptsRequestsFactory
{
    const DEFAULT_OFFER_CLASS_NAME = 'GptsRequest';

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

    private static $requestClasses = [self::TOUR_OFFER_TYPE    => '',
                                        self::FLIGHT_OFFER_TYPE        => 'FlightGptsRequest',
                                        self::CAR_RENT_OFFER_TYPE      => '',
                                        self::RAILWAY_OFFER_TYPE       => '',
                                        self::PACKET_OFFER_TYPE        => '',
                                        self::ACCOMMODATION_OFFER_TYPE => 'HotelGptsRequest',
                                        self::TRANSFER_OFFER_TYPE      => '',
                                        self::VISA_OFFER_TYPE          => '',
                                        self::INSURANCE_OFFER_TYPE     => '',
                                        self::EXCURSION_OFFER_TYPE     => '',
                                        self::MEAL_OFFER_TYPE          => '',
                                        self::GUIDE_OFFER_TYPE         => '',
                                        self::EXTRA_OFFER_TYPE         => ''
    ];

    /**
     * Создание поискового запроса в зависимости от указанного типа предложения
     * @param $type string тип предложения
     * @param $module object ссылка на модуль
     * @return mixed объект предложения
     */
    public static function createSearchRequest($type, $module)
    {

        if (!array_key_exists($type, self::$requestClasses)) {
            return false;
        }

        return new self::$requestClasses[$type]($module ,$type);
    }

    /**
     * Получить тип поискового запроса GPTS по наименованию его класса
     * @param $className
     * @return mixed
     */
    public static function getSearchRequestTypeByClassName($className)
    {
        $requestType = array_search($className, OfferFindersFactory::$requestClasses);
        return $requestType;
    }
}