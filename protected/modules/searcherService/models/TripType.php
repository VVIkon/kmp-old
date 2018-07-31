<?php

/**
 * Class TripType
 * Реализует функциональность для работы с данными типа поездки
 */
class TripType extends KFormModel
{
    const ROUND_TRIP = 1;
    const ONE_WAY = 2;
    const MULTI_CITY = 3;

    /**
     * Проверка наличия указанного типа поездки
     * @param $typeCode int
     * @return bool
     */
    public static function checkTypeExists($typeCode)
    {

        $class = new ReflectionClass(__CLASS__);

        foreach ($class->getConstants() as $constant) {

            if ($typeCode == $constant) {
                return true;
            }
        }

        return false;
    }
}

