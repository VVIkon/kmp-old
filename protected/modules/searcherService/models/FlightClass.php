<?php

/**
 * Class FlightClass
 * Реализует функциональность для работы с данными класса полёта
 */
class FlightClass extends KFormModel
{
    const ANY = 0;
    const ECONOMY = 1;
    const FIRST = 2;
    const BUSINESS = 3;

    /**
     * Массив для связи текстовых названий с числдовыми константами
     * @var array
     */
    private static $flightClasses = [
        'ANY' => self::ANY,
        'ECONOMY' => self::ECONOMY,
        'FIRST' => self::FIRST,
        'BUSINESS' => self::BUSINESS
    ];

    /**
     * Проверка наличия указанного класса полёта
     * @param $class
     * @return bool
     */
    public static function checkClassExists($class)
    {
        return array_key_exists($class, self::$flightClasses);
    }

    public static function checkIdExists($id)
    {
        return in_array($id, self::$flightClasses);
    }
    /**
     * Получение идентифкатора класса полёта по его наименованию
     * @param $name
     * @return bool
     */
    public static function getIdByName($name)
    {

        if (!self::checkClassExists($name)) {
            return false;
        }

        return self::$flightClasses[$name];
    }

    /**
     * Получение наименования класса полёта по его идентифкатору
     * @param $id int
     * @return string
     */
    public static function getNameById($id)
    {

        if (!self::checkIdExists($id)) {
            return false;
        }

        return array_search($id,self::$flightClasses);
    }
}

