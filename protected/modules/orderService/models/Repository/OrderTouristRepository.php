<?php

/**
 * Репозиторий туристов в заявке
 */
class OrderTouristRepository
{
    /**
     * @param $orderId
     * @return null|OrderTourist
     */
    public static function getTourleaderByOrderId($orderId)
    {
        return OrderTourist::model()->findByAttributes(['OrderID' => $orderId, 'TourLeader' => 1]);
    }

    /**
     * @param int $orderId
     * @param int $touristIdBase
     * @return OrderTourist
     */
    public static function getByOrderAndTourist($orderId, $touristIdBase)
    {
        return OrderTourist::model()->findByAttributes(['OrderID' => $orderId, 'TouristIDbase' => $touristIdBase]);
    }

    /**
     * @param int $TouristID
     * @return OrderTourist|null
     */
    public static function getByOrderTouristId($TouristID)
    {
        return OrderTourist::model()->findByPk($TouristID);
    }

    /**
     * @param $orderId
     * @param $touristId
     * @return OrderTourist|null
     */
    public static function getByOrderIdAndTouristId($orderId, $touristId)
    {
        return OrderTourist::model()->findByAttributes(['TouristID' => $touristId, 'OrderID' => $orderId]);
    }
}