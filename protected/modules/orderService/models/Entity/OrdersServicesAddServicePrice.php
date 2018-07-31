<?php

/**
 * @property $idAddService
 */
class OrdersServicesAddServicePrice extends AbstractPriceOffer
{
    public function tableName()
    {
        return 'kt_orders_services_addServices_price';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function bindAddService(OrdersServicesAddService $addService)
    {
        $this->idAddService = $addService->getId();
    }

    /**
     * @return mixed
     */
    public function getTaxes()
    {
        return [];
    }
}