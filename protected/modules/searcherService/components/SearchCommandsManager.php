<?php

/**
 * Class SearchCommandsManager
 * Реализация фабрики поисковых команд
 */
class SearchCommandsManager
{
    const DEFAULT_OFFER_CLASS_NAME = 'RunOfferSearch';

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

    private static $searchCommands = [self::TOUR_OFFER_TYPE    => '',
                                        self::FLIGHT_OFFER_TYPE        => 'RunAviaOfferSearch',
                                        self::CAR_RENT_OFFER_TYPE      => '',
                                        self::RAILWAY_OFFER_TYPE       => '',
                                        self::PACKET_OFFER_TYPE        => '',
                                        self::ACCOMMODATION_OFFER_TYPE => 'RunAccommodationOfferSearch',
                                        self::TRANSFER_OFFER_TYPE      => '',
                                        self::VISA_OFFER_TYPE          => '',
                                        self::INSURANCE_OFFER_TYPE     => '',
                                        self::EXCURSION_OFFER_TYPE     => '',
                                        self::MEAL_OFFER_TYPE          => '',
                                        self::GUIDE_OFFER_TYPE         => '',
                                        self::EXTRA_OFFER_TYPE         => ''
    ];

    /**
     * Получение названия команды запуска процесса поиска авиапредложений
     * @param $type string тип услуги
     * @return mixed объект предложения
     */
    public static function getSearchCommand($type)
    {
        if (!array_key_exists($type, self::$searchCommands)) {
            return false;
        }

        return self::$searchCommands[$type];
    }

}