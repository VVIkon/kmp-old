<?php

/**
 * Class ServicesFactory
 * Реализация фабрики классов услуг заявки
 */
class ServicesFactory
{
    const DEFAULT_SERVICE_CLASS_NAME = 'Service';

    const ACCMD_SERVICE_TYPE = 1;
    const FLIGHT_SERVICE_TYPE = 2;
    const TRANSFER_SERVICE_TYPE = 3;
    const VISA_SERVICE_TYPE = 4;
    const CAR_RENT_SERVICE_TYPE = 5;
    const TOUR_SERVICE_TYPE = 6;
    const RAILWAY_SERVICE_TYPE = 7;
    const PACKET_SERVICE_TYPE = 8;
    const INSURANCE_SERVICE_TYPE = 9;
    const EXCURSION_SERVICE_TYPE = 10;
    const MEAL_SERVICE_TYPE = 11;
    const GUIDE_SERVICE_TYPE = 12;
    const EXTRA_SERVICE_TYPE = 13;
    const UTK_SERVICE_TYPE = 101;


    private static $_servicesClasses = [
                        self::TOUR_SERVICE_TYPE     => 'TourService',
                        self::FLIGHT_SERVICE_TYPE   => 'FlightService',
                        self::UTK_SERVICE_TYPE      => 'UtkService',
                        self::RAILWAY_SERVICE_TYPE  => 'RailwayService',
                        self::ACCMD_SERVICE_TYPE    => 'AccommodationService',
                        self::TRANSFER_SERVICE_TYPE => 'TransferService',
                        self::VISA_SERVICE_TYPE     => 'VisaService',
                        self::PACKET_SERVICE_TYPE   => 'PacketService',
                        self::CAR_RENT_SERVICE_TYPE => 'CarRentService',
                        self::INSURANCE_SERVICE_TYPE =>'InsuranceService',
                        self::EXCURSION_SERVICE_TYPE => 'ExcursionService',
                        self::MEAL_SERVICE_TYPE      => 'MealService',
                        self::GUIDE_SERVICE_TYPE     => 'GuideService',
                        self::EXTRA_SERVICE_TYPE     => 'ExtraService',

    ];

    /**
     * Создание объекта услуги в зависимости от указанного типа услуги
     * @param $type int тип услуги
     * @return Service
     */
    public static function createService($type) {

        if (!array_key_exists($type, self::$_servicesClasses)) {
            /*$className = self::DEFAULT_SERVICE_CLASS_NAME;
            return new $className;*/
            return false;
        }

        return new self::$_servicesClasses[$type];
    }

    /**
     * Проверка на существования связи типа услуги
     * и соответствующего этому типу класса
     * @param $serviceType int тип услуги
     * @return bool
     */
    public static function isServiceTypeExist($serviceType) {

        if (!is_numeric($serviceType)) {
            return false;
        }

        if (!array_key_exists(intval($serviceType),self::$_servicesClasses)) {
            return false;
        }

        return true;
    }
}