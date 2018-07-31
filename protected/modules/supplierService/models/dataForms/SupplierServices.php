<?php

/**
 * Class SupplierServices
 * Используется для работы с типами услуг сервиса поставщиков
 */
class SupplierServices
{
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


    private static $supplierServices = [
        self::TOUR_SERVICE_TYPE,
        self::FLIGHT_SERVICE_TYPE,
        self::UTK_SERVICE_TYPE,
        self::RAILWAY_SERVICE_TYPE,
        self::ACCMD_SERVICE_TYPE,
        self::TRANSFER_SERVICE_TYPE,
        self::VISA_SERVICE_TYPE,
        self::PACKET_SERVICE_TYPE,
        self::CAR_RENT_SERVICE_TYPE,
        self::INSURANCE_SERVICE_TYPE,
        self::EXCURSION_SERVICE_TYPE,
        self::MEAL_SERVICE_TYPE,
        self::GUIDE_SERVICE_TYPE,
        self::EXTRA_SERVICE_TYPE,

    ];

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

        if (!array_key_exists(intval($serviceType),self::$supplierServices)) {
            return false;
        }

        return true;
    }
}