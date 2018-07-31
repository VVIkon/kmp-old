<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/23/16
 * Time: 11:46 AM
 */
class OrderModelRepository
{
    /**
     * Получение заявки с услугами
     * @param $orderId
     * @return OrderModel|null
     */
    public static function getWithServices($orderId)
    {
        return $OrderModel = OrderModel::model()->with(array(
            'OrdersServices'
        ))->findByPk($orderId);
    }

    /**
     * Получение заявки по ID вместе с ее услугами
     * @param $orderId
     * @return OrderModel
     */
    public static function getByOrderWithServices($orderId, $serviceIds)
    {
        $servicesids_string = implode(',', $serviceIds);

        return $OrderModel = OrderModel::model()->with(array(
            'OrdersServices.RefService' => array(
                'condition' => "OrdersServices.ServiceID IN ($servicesids_string)",
            )
        ))->findByAttributes(array('OrderID' => $orderId));
    }

    /**
     * Получение заявки по ID
     * @param int $orderId
     * @return OrderModel|null
     */
    public static function getByOrderId($orderId)
    {
        return OrderModel::model()->findByPk((int)$orderId);
    }

    /**
     * Поулчение заявки по ID УТК
     * @param string $utkOrderId ID заявки в УТК
     * @return OrderModel|null
     */
    public static function getByUTKOrderId($utkOrderId)
    {
        return OrderModel::model()->findByAttributes(['OrderID_UTK' => (string)$utkOrderId]);
    }

    /**
     * Поулчение заявки по ID GPTS
     * @param int $gptsOrderId ID заявки в GPTS
     * @return OrderModel|null
     */
    public static function getByGPTSOrderId($gptsOrderId)
    {
        return OrderModel::model()->findByAttributes(['OrderID_GP' => $gptsOrderId]);
    }

    /**
     * Пытаемся найти заявку по ID
     * если не нашлась, то создаем новую
     * @param $orderId
     * @return OrderModel или код ошибки
     */
    public static function getFromIdOrCreateNew($orderId)
    {
        $OrderModel = OrderModel::model()->findByPk($orderId);

        // если запись не нашлась, то нужно создать новую
        if (!$OrderModel) {
            $OrderModel = new OrderModel();
        }

        return $OrderModel;
    }

    /**
     *
     * @param $dateFrom
     * @param $dateTo
     * @param $companyId
     * @return OrderModel[]
     */
    public static function getBtwDatesForCompanyId($dateFrom, $dateTo, $companyId)
    {
        $criteria = new CDbCriteria();
        if ($companyId) {
            $criteria->addCondition("AgentID = $companyId");
        }
        $criteria->addBetweenCondition('OrderDate', $dateFrom, $dateTo);

        return OrderModel::model()->with('OrdersServices')->findAll($criteria);
    }

    public static function getByCriteria(CDbCriteria $criteria)
    {
        return OrderModel::model()->findAll($criteria);
    }
}