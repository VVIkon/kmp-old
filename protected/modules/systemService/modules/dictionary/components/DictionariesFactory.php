<?php

/**
 * Class DictionariesFactory
 * Реализация фабрики классов обработчиков запросов справочных данных
 */
class DictionariesFactory
{
    const COMPANIES_DICTIONARY = 1;
    const CONTRACTS_DICTIONARY = 2;
    const SUPPLIERS_DICTIONARY = 3;
    const HOTEL_CHAINS_DICTIONARY = 4;
    const COUNTRIES_DICTIONARY = 6;
    const AIRLINES_LOYALITY_PROGRAMS = 21;
    const USER_ROLES = 22;

    private static $dictionaries = [
        self::COMPANIES_DICTIONARY => 'CompaniesDictionaryHandler',
        self::CONTRACTS_DICTIONARY => 'ContractsDictionaryHandler',
        self::SUPPLIERS_DICTIONARY => 'SuppliersDictionaryHandler',
        self::COUNTRIES_DICTIONARY => 'CountriesDictionaryHandler',
        self::HOTEL_CHAINS_DICTIONARY => 'HotelChainsDictionaryHandler',
        self::AIRLINES_LOYALITY_PROGRAMS => 'AirlinesLoyalityProgramsDictionaryHandler',
        self::USER_ROLES => 'UserRolesDictionaryHandler'
    ];

    /**
     * Создание обработчика запроса справочных данных
     * @param $handlerType int тип услуги
     * @param $module int
     * @return AbstractDictionaryHandler|bool
     */
    public static function createDictionaryHandler($handlerType, $module)
    {
        if (!array_key_exists($handlerType, self::$dictionaries)) {
            return false;
        }

        return new self::$dictionaries[$handlerType]($module);
    }

    /**
     * Проверить наличие указанного обработчика запроса справочных данных
     * @param $providerId
     * @return bool
     */
    public static function isDictionaryHandlerExists($handlerType)
    {
        if (empty($handlerType)) {
            return false;
        }

        return array_key_exists($handlerType, self::$dictionaries);
    }
}
