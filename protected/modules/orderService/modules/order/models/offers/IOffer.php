<?php

/**
 * Interface IOffer
 * Интерфейс для доступа к методам предложения услуги
 */
interface IOffer
{
    /**
     * Получить идентифкатор предложения
     * @return mixed
     */
    public function getOfferId();

    /**
     * Получить туристов предложения
     * @return mixed
     */
    public function getOfferTouristsInfo();
}