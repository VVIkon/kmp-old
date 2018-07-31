<?php

/**
 * Class GptsResponsesFactory
 * Реализация фабрики классов ответов от провайдера GPTS
 */
class GptsResponsesFactory
{
    const DEFAULT_RESPONSE_CLASS_NAME = 'GptsResponse';

    const ACCOMMODATION_RESPONSE_TYPE = 1;
    const FLIGHT_RESPONSE_TYPE = 2;
    const TRANSFER_RESPONSE_TYPE = 3;
    const VISA_RESPONSE_TYPE = 4;
    const CAR_RENT_RESPONSE_TYPE = 5;
    const TOUR_RESPONSE_TYPE = 6;
    const RAILWAY_RESPONSE_TYPE = 7;
    const PACKET_RESPONSE_TYPE = 8;
    const INSURANCE_RESPONSE_TYPE = 9;
    const EXCURSION_RESPONSE_TYPE = 10;
    const MEAL_RESPONSE_TYPE = 11;
    const GUIDE_RESPONSE_TYPE = 12;
    const EXTRA_RESPONSE_TYPE = 13;

    private static $responsesClasses = [self::TOUR_RESPONSE_TYPE    => '',
                                        self::FLIGHT_RESPONSE_TYPE        => 'FlightGptsResponse',
                                        self::CAR_RENT_RESPONSE_TYPE      => '',
                                        self::RAILWAY_RESPONSE_TYPE       => '',
                                        self::PACKET_RESPONSE_TYPE        => '',
                                        self::ACCOMMODATION_RESPONSE_TYPE => 'HotelGptsResponse',
                                        self::TRANSFER_RESPONSE_TYPE      => '',
                                        self::VISA_RESPONSE_TYPE          => '',
                                        self::INSURANCE_RESPONSE_TYPE     => '',
                                        self::EXCURSION_RESPONSE_TYPE     => '',
                                        self::MEAL_RESPONSE_TYPE          => '',
                                        self::GUIDE_RESPONSE_TYPE         => '',
                                        self::EXTRA_RESPONSE_TYPE         => ''
    ];

    /**
     * Создание объекта ответа от GPTS в зависимости от указанного типа предложения
     * @param $type string тип предложения
     * @param $module object ссылка на модуль
     * @return mixed объект предложения
     */
    public static function createSearchResponse($type, $module)
    {

        if (!array_key_exists($type, self::$responsesClasses)) {
            return false;
        }

        return new self::$responsesClasses[$type]($module ,$type);
    }

    /**
     * Получить тип поискового ответа от GPTS по наименованию его класса
     * @param $className
     * @return mixed
     */
    public static function getSearchResponseTypeByClassName($className)
    {
        $responseType = array_search($className, self::$requestClasses);
        return $responseType;
    }
}