<?php

/**
 * Модель для ценовых предложений услуги
 * @property $serviceId
 * @property OrderServicePriceTax[] $taxes
 */
class OrderServicePrice extends AbstractPriceOffer
{
    public function tableName()
    {
        return 'kt_order_service_prices';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'taxes' => array(self::HAS_MANY, 'OrderServicePriceTax', 'orderServiceId'),
        );
    }

    /**
     * @return mixed
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param mixed $serviceId
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;
    }

    /**
     * @return OrderServicePriceTax[]
     */
    public function getTaxes()
    {
        return $this->taxes;
    }
}