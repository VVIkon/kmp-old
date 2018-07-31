<?php

/**
 * репозиторий услуг заявки
 */
class OrdersServicesRepository
{
    /**
     * Возвращает коллекцию услуг заявки со справочником офферов (kt_ref_services)
     * @param array $ids
     * @return OrdersServices [] массив услуг заявки
     */
    public static function findAllByIdsWithRef(array $ids)
    {
        $ids_string = explode(',', $ids);
        return OrdersServices::model()->with('RefService')->findAll("ServiceID IN ($ids_string)");
    }

    /**
     * Поиск по ID
     * @param $serviceId
     * @return OrdersServices|null
     */
    public static function findById($serviceId)
    {
        return OrdersServices::model()->findByPk($serviceId);
    }

    /**
     * @param int $gptsServiceId
     * @return OrdersServices|null
     */
    public static function findByGPTSId($gptsServiceId)
    {
        return OrdersServices::model()->findByAttributes(['ServiceID_GP' => $gptsServiceId]);
    }

    /**
     * @param string $utkServiceId
     * @return OrdersServices|null
     */
    public static function findByUTKId($utkServiceId)
    {
        return OrdersServices::model()->findByAttributes(['ServiceID_UTK' => $utkServiceId]);
    }

    /**
     * Поиск по ID
     * @param $serviceId
     * @param $orderId
     * @return OrdersServices|null
     */
    public static function findByIdAndOrderId($orderId, $serviceId)
    {
        return OrdersServices::model()->findByAttributes(['OrderID' => $orderId, 'ServiceID' => $serviceId]);
    }

    /**
     * Возвращает коллекцию услуг для проверки статуса в GPTS
     * @param $gateId - провайдер
     * @return OrdersServices [] массив услуг заявки
     *
     * SELECT x.* FROM testkmptravel.kt_orders_services x
     *  WHERE Status = 1
     *  AND Offline = 0
     *  OR ( Status = 8
     *      AND ServiceID IN (SELECT DISTINCT ticket.serviceid from kt_service_fl_ticket ticket where ticket.Status = 1)
     *    )
     *  AND DateFinish > now()
     *
     */
    public static function findOrderServiceForCheckStatus($gateId = 5)
    {
        return OrdersServices::model()
            ->findAll("Status = 1 and Offline = 0 OR (Status = 8 and ServiceID in (SELECT DISTINCT ticket.serviceid from kt_service_fl_ticket ticket where ticket.Status = 1)) and DateFinish > now()");
    }

    /**
     * @param CDbCriteria $criteria
     * @return OrdersServices []
     */
    public static function getByCriteria(CDbCriteria $criteria)
    {
        return OrdersServices::model()->findAll($criteria);
    }
}