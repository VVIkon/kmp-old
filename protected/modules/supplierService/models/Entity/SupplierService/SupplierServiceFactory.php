<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/3/16
 * Time: 3:32 PM
 */
class SupplierServiceFactory
{
    protected static $services = [
        1 => 'Accomodation',
        2 => 'Avia'
    ];

    /**
     * @param $serviceType
     * @return SupplierServiceInterface
     * @throws Exception
     */
    public static function getSupplierServiceByType($serviceType)
    {
        $serviceClassName = self::$services[$serviceType] . 'SupplierService';

        if (class_exists($serviceClassName)) {
            return new $serviceClassName();
        } else {
            throw new Exception("Неизвестный тип сервиса № $serviceType");
        }
    }
}