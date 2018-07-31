<?php

/**
 * @property $orderServiceId
 */
class OrderServicePriceTax extends AbstractTaxOffer
{
    public function tableName()
    {
        return 'kt_order_service_tax';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param $priceId
     * @return mixed
     */
    protected function setPriceId($priceId)
    {
        $this->orderServiceId = $priceId;
    }
}