<?php

/**
 * Class FlightService
 * Реализует функциональность работы с атрибутами услуги типа FlightService
 */
class FlightService extends Service implements IService
{
    /**
     * Получить атрибуты, специфичные для услуги
     */
    public function getExAttributes() {}

    /**
     * Получить атрибуты, специфичные для группы услуг
     * @param $services array услуги
     * @return array атрибуты
     */
    public function getServicesGroupExAttributes($services)
    {
        /*$command = Yii::app()->db->createCommand()
            ->select('ordersvc.ServiceID serviceID, toursMatch.SupplierID supplierId,
                        tours.Name serviceName, tours.Brief serviceDescription')
            ->from('kt_orders_services ordersvc')

            ->leftJoin('kt_tours_match toursMatch', 'ordersvc.TourID = toursMatch.TourID')
            ->leftJoin('kt_tours tours', 'ordersvc.TourID = tours.TourID')

            ->where(array('in', 'ServiceID', $services));

        return $command->queryAll();*/
        return [];
    }
}
