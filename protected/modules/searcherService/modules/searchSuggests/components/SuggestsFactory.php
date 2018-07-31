<?php

/**
 * Class SuggestsFactory
 * Фабрика классов реализующих функционал поиска информации
 * для посказок при пользовательском вводе
 */
class SuggestsFactory
{
    const HOTEL_SUGGEST_TYPE = 45;

    const AIRPORT_SUGGEST_TYPE = 2;
    const ACCOMODATION_CITY_SUGGEST_TYPE = 1;


    private static $suggestClasses = [
        self::AIRPORT_SUGGEST_TYPE => 'AirportSuggest',
        self::ACCOMODATION_CITY_SUGGEST_TYPE => 'CitySuggest',
        self::HOTEL_SUGGEST_TYPE => 'HotelSuggest'
    ];

    /**
     * Создать класс подсказок
     * @param $type
     * @return bool
     */
    public static function createSuggestClass($type)
    {

        if (!array_key_exists($type, self::$suggestClasses)) {
            /*$className = self::DEFAULT_SUGGEST_CLASS_NAME;
            return new $className;*/
            return false;
        }

        return new self::$suggestClasses[$type];
    }

}