<?php

/**
 * Доп поля в заявке
 *
 * @property $id    int(11)    id записи
 * @property $touristId    bigint(20)    ID туриста, может быть null если поле уровня заявки
 * @property $orderId    bigint(20) NULL    ID заявки
 * @property $serviceId
 * @property $fieldTypeId    int(11) NULL    Тип доп. поля
 * @property $value    varchar(100) NULL    Значение
 *
 * @property AdditionalFieldType $AdditionalFieldType
 */
class OrderAdditionalField extends AbstractAdditionalField
{
    const FIELD_CATEGORY_TOURIST = 1;
    const FIELD_CATEGORY_ORDER = 3;

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'AdditionalFieldType' => array(self::BELONGS_TO, 'AdditionalFieldType', 'fieldTypeId')
        );
    }

    public function tableName()
    {
        return 'kt_orderAdditionalData';
    }

    public function getTouristId()
    {
        return $this->touristId;
    }

    public function getServiceId()
    {
        return $this->serviceId;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Сущность дополнительных данных заявки и услуги в УТК
     * @return array
     */
    public function getSOUTKOrderAdditionalData()
    {
        return [
            'fieldTypeName' => $this->AdditionalFieldType->getName(),
            'fieldTypeID' => $this->AdditionalFieldType->getId(),             // Тип доп. поля (db_add_field_types)
            'fieldCategory' => $this->AdditionalFieldType->getFieldCategory(),              // Категория 1-поле пользователя, 2-поле услуги, 3-заявки
            'reasonFailTP' => $this->AdditionalFieldType->isReasonFailTP(),           // Поле - причина нарушения TP
            'Value' => $this->getValue(),                                                     //Значение
            'typeTemplate' => $this->AdditionalFieldType->getTypeTemplate()
        ];
    }

    public function toArray()
    {
        $base = parent::toArray();
        $base['orderId'] = $this->orderId;
        $base['serviceId'] = $this->serviceId;
        $base['touristId'] = $this->touristId;
        return $base;
    }

    /**
     * Присоединение заявки к доп поплю
     * @param OrderModel $order
     */
    public function bindOrder(OrderModel $order)
    {
        $this->orderId = $order->getOrderId();
    }

    /**
     * Присоединение заявки к доп поплю
     * @param OrdersServices $service
     */
    public function bindService(OrdersServices $service)
    {
        $this->serviceId = $service->getServiceID();
    }

    /**
     * Примоединение туриста к доп полю
     * @param OrderTourist $orderTourist
     */
    public function bindTourist(OrderTourist $orderTourist)
    {
        $this->touristId = $orderTourist->getTouristID();
    }
    /**
     * Примоединение туриста к доп полю по его Id
     * @param OrderTourist $orderTourist
     */
    public function bindTouristById($touristId)
    {
        $this->touristId = $touristId;
    }

}