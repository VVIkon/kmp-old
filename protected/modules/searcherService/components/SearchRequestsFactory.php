<?php

/**
 * Class SearchRequestsFactory
 * Реализация фабрики классов поисковых запросов
 */
class SearchRequestsFactory
{
    const DEFAULT_REQUEST_TYPE_CLASS_NAME = 'SearchRequest';

    const ACCOMMODATION_REQUEST_TYPE = 1;
    const FLIGHT_REQUEST_TYPE = 2;
    const TRANSFER_REQUEST_TYPE = 3;
    const VISA_REQUEST_TYPE = 4;
    const CAR_RENT_REQUEST_TYPE = 5;
    const TOUR_REQUEST_TYPE = 6;
    const RAILWAY_REQUEST_TYPE = 7;
    const PACKET_REQUEST_TYPE = 8;
    const INSURANCE_REQUEST_TYPE = 9;
    const EXCURSION_REQUEST_TYPE = 10;
    const MEAL_REQUEST_TYPE = 11;
    const GUIDE_REQUEST_TYPE = 12;
    const EXTRA_REQUEST_TYPE = 13;

    private static $searchRequestClasses = [
                                        self::FLIGHT_REQUEST_TYPE => 'FlightSearchRequest',
    ];

    /**
     * Создание объекта поискового запроса в зависимости от указанного типа услуги
     * @param $type string тип услуги
     * @return mixed объект предложения
     */
    public static function createSearchRequest($type, $module)
    {
        if (!array_key_exists($type, self::$searchRequestClasses)) {
            return false;
        }

        return new self::$searchRequestClasses[$type]($module);
    }

    /**
     * Получить тип услуги по наименованию класса запроса
     * @param $className
     * @return mixed
     */
    public static function getSearchRequestTypeByClassName($className)
    {
        $requestType = array_search($className, SearchHandlersFactory::$searchRequestClasses);
        return $requestType;
    }
}