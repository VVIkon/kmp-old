<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 28.03.17
 * Time: 18:51
 */
class OrderAdditionalFieldRepository
{
    /**
     *
     * @param OrderTourist $orderTourist
     * @param $fieldTypeId
     * @return OrderAdditionalField|OrderAdditionalField[]
     */
    public static function getTouristFieldWithId(OrderTourist $orderTourist, $fieldTypeId = null)
    {
        if(is_null($fieldTypeId)){
            return OrderAdditionalField::model()->findAllByAttributes(
                [
                    'touristId' => $orderTourist->getTouristID(),
                ]
            );
        }
        return OrderAdditionalField::model()->findByAttributes(
            [
                'touristId' => $orderTourist->getTouristID(),
                'fieldTypeId' => $fieldTypeId
            ]
        );
    }
    /**
     *
     * @param $touristId
     * @param $fieldTypeId
     * @return OrderAdditionalField|OrderAdditionalField[]
     */
    public static function getTouristFieldById($touristId, $fieldTypeId = null)
    {
        if(is_null($fieldTypeId)){
            return OrderAdditionalField::model()->findAllByAttributes(
                [
                    'touristId' => $touristId,
                ]
            );
        }
        return OrderAdditionalField::model()->findByAttributes(
            [
                'touristId' => $touristId,
                'fieldTypeId' => $fieldTypeId
            ]
        );
    }

    /**
     *
     * @param OrderModel $order
     * @param $fieldTypeId
     * @return OrderAdditionalField|OrderAdditionalField[]
     */
    public static function getOrderFieldWithId(OrderModel $order, $fieldTypeId = null)
    {
        if(is_null($fieldTypeId)){
            return OrderAdditionalField::model()->findAllByAttributes(
                [
                    'orderId' => $order->getOrderId(),
                ]
            );
        }
        return OrderAdditionalField::model()->findByAttributes(
            [
                'orderId' => $order->getOrderId(),
                'fieldTypeId' => $fieldTypeId
            ]
        );
    }

    /**
     *
     * @param OrdersServices $service
     * @param $fieldTypeId
     * @return OrderAdditionalField []|OrderAdditionalField
     */
    public static function getServiceFieldWithId(OrdersServices $service, $fieldTypeId = null)
    {
        if (is_null($fieldTypeId)) {
            return OrderAdditionalField::model()->findAllByAttributes(
                [
                    'serviceId' => $service->getServiceID(),
                ]
            );
        }

        return OrderAdditionalField::model()->findByAttributes(
            [
                'serviceId' => $service->getServiceID(),
                'fieldTypeId' => $fieldTypeId
            ]
        );
    }

    /**
     * @param OrdersServices $service
     * @return OrderAdditionalField|null
     */
    public static function getServiceMinimalPriceField(OrdersServices $service)
    {
        return OrderAdditionalField::model()->with(array('AdditionalFieldType' => array('condition' => 'typeTemplate = 5')))->findByAttributes(
            [
                'serviceId' => $service->getServiceID(),
            ]
        );
    }
}