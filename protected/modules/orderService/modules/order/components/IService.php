<?php

/**
 * Interface IService
 * Интерфейс для доступа к методам услуги
 */
interface IService
{
    /**
     * Получить атрибуты, специфичные для типа услуги
     * @return mixed
     */
    public function getExAttributes();

    /**
     * Установить атрибуты, специфичные для типа услуги
     * @return mixed
     */
    public function setExAttributes($attrs);

    /**
     * Получить набор атрибутов для нескольких услуг одного типа
     * @param $services
     * @return mixed
     */
    public function getServicesGroupExAttributes($services);

    /**
     * Установка ид предложения услуги
     * @return mixed
     */
    public function setOfferId($offerId);
}