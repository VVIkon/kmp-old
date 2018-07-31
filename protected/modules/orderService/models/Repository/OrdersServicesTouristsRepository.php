<?php

/**
 * репозиторий привязок туристов к услугам заявки
 */
class OrdersServicesTouristsRepository
{
    /**
     * @param int $serviceId
     * @param int $orderTouristId
     * @return OrdersServicesTourists|null
     */
    public static function findByServiceAndOrderTouristIds($serviceId, $orderTouristId)
    {
        return OrdersServicesTourists::model()->findByAttributes(['ServiceID' => $serviceId, 'TouristID' => $orderTouristId]);
    }
}