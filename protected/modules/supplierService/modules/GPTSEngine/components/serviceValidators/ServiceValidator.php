<?php

/** Базовый класс валидаторов услуг */
class ServiceValidator extends Validator
{
    /**
     * переопределен конструктор, т.к. в базовом классе (Validator) передается ссылка на домуль, которая не нужна
     * @todo убрать переопределение ,если будет убран параметр модуля в базовом классе
     */
    public function __construct()
    {
    }

    /**
     * Проверка параметров структуры supplierOfferData команды GetOffer
     * @param mixed[] $params параметры команды
     */
    public function checkGetOffer($params)
    {
        return true;
    }

    /**
     * Проверка параметров структуры supplierOfferData команды ServiceBooking
     * @param mixed[] $params параметры команды
     */
    public function checkServiceBooking($params)
    {
        return true;
    }

}

?>
