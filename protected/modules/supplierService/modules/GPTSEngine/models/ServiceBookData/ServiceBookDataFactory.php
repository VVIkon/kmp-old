<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/4/16
 * Time: 3:28 PM
 */
class ServiceBookDataFactory
{
    /**
     * @param $serviceName
     * @throws Exception
     * @return AbstractServiceBookData
     */
    public static function getServiceBookDataByServiceName($serviceName)
    {
        $className = $serviceName . 'BookData';

        if (class_exists($className)) {
            $ServiceBookDataClass = new $className();
            return $ServiceBookDataClass;
        } else {
            throw new Exception("Класс $className не существует");
        }
    }
}