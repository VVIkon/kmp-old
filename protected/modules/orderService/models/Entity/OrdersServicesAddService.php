<?php

/**
 * Модель доп услуги в заявке
 *
 * @property $idAddService    bigint(20) Auto Increment    ID дополнительного сервиса
 * @property $serviceId    bigint(20)    ID основного сервиса
 * @property $subServiceId    bigint(20)    ID в справочнике дополнительных услуг
 * @property $addServiceOfferId    bigint(20) NULL    оффер доп. услуги в стурктуре офера предложения, по которому была добавлена доп. услуга в основной услуге
 * @property $status    tinyint(4) NULL    Статус доп.услуги
 * @property $specParamAddService    text NULL    массив дополнительных параметров для доп.услуги.
 * @property $name    varchar(100) NULL    Название услуги.
 * @property $engineData    text NULL    Данные шлюза для брони структура ss_еngineData
 *
 * @property RefSubServices $refSubService
 * @property OrdersServicesAddServicePrice[] $addOfferPrices
 * @property OrdersServices $service
 */
class OrdersServicesAddService extends AbstractHotelAddOffer
{
    // статусы
    const STATUS_NEW = 0;
    const STATUS_W_BOOKED = 1;
    const STATUS_BOOKED = 2;
//    const STATUS_W_PAID = 3;
//    const STATUS_P_PAID = 4;
//    const STATUS_PAID = 5;
    const STATUS_CANCELLED = 6;
//    const STATUS_VOIDED = 7;
//    const STATUS_DONE = 8;
    const STATUS_MANUAL = 9;

    public function tableName()
    {
        return 'kt_orders_services_addServices';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'addOfferPrices' => array(self::HAS_MANY, 'OrdersServicesAddServicePrice', 'idAddService'),
            'refSubService' => array(self::BELONGS_TO, 'RefSubServices', 'subServiceId'),
            'service' => array(self::BELONGS_TO, 'OrdersServices', 'serviceId')
        );
    }

    public function getId()
    {
        return $this->idAddService;
    }

    /**
     * @return mixed
     */
    public function getOfferId()
    {
        return $this->addServiceOfferId;
    }

    /**
     * @return RefSubServices
     */
    public function getSubService()
    {
        return $this->refSubService;
    }

    /**
     * @return AbstractPriceOffer[]
     */
    public function getAddOfferPrices()
    {
        return $this->addOfferPrices;
    }

    public function bindService(OrdersServices $service)
    {
        $this->serviceId = $service->getServiceID();
    }

    /**
     *
     * @param AbstractHotelAddOffer $addOffer
     */
    public function createFromAddOffer(AbstractHotelAddOffer $addOffer)
    {
        $this->addServiceOfferId = $addOffer->getId();
        $this->subServiceId = $addOffer->getSubService()->getId();
        $this->status = self::STATUS_NEW;
        $this->setEngineData($addOffer->getEngineData());
        $this->setSpecParamAddService($addOffer->getSpecParamAddService());
        $this->setName($addOffer->getName());
    }

    /**
     * @return OrdersServices
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     *
     * @param $required
     */
    public function setRequired($required)
    {
        $addParams = $this->getSpecParamAddService();
        $addParams['required'] = $required;
        $this->setSpecParamAddService($addParams);
    }

    public function makeNew()
    {
        $this->status = self::STATUS_NEW;
    }

    public function makeBooked()
    {
        $this->status = self::STATUS_BOOKED;
    }

    /**
     *
     */
    public function makeWBooked()
    {
        $this->status = self::STATUS_W_BOOKED;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function cancel()
    {
        $this->status = self::STATUS_CANCELLED;
    }


    public function makeManual()
    {
        $this->status = self::STATUS_MANUAL;
    }

    /**
     * @return bool
     */
    public function canBeRemoved()
    {
        return $this->status == self::STATUS_NEW;
    }

    /**
     * Структура SOAddService
     * @return array
     */
    public function toSOAddService()
    {
        $prices = $this->getAddOfferPrices();

        $salesTerms = new SalesTermsInfo();
        $salesTerms->addCurrency('local', CurrencyStorage::findByString(643));
        foreach ($prices as $price) {
            if ($this->viewCurrency) {
                $salesTerms->addCurrency('view', $this->viewCurrency);
            }
            $salesTerms->addCurrency($price->getType(), $price->getCurrency());
            $salesTerms->addPrice($price);
        }
        $salesTermsArr = $salesTerms->getArray();

        // узнаем поставщика услуги, чтобы найти
        $subServicesSupplier = RefSubServicesSupplierRepository::getBySupplierAndSubService($this->getService()->getSupplier(), $this->getSubService());

        return [
            'idAddService' => $this->idAddService,      // id доп.услуги
            'serviceId' => $this->serviceId,            // id основной услуги (доп.услуга не может быть оформлена самостоятельно)
            'serviceSubType' => $this->subServiceId,    // номер доп.услуги в справочнике доп.услуг.
            'status' => $this->status,                  // Статус доп.услуги
            'salesTermsInfo' => $salesTermsArr,         // Ценовые компоненты услуги, структура ss_salesTermsInfo
            'tourists' => [],                           // массив с данными туриста. структура ss_tourist. Опционально, т.к. турист может заказать или не заказать эту доп.услугу
            'specParamAddService' => $this->getSpecParamAddService(),  //массив дополнительных параметров для доп.услуги. Опционально.
            'bookedWithService' => $subServicesSupplier->canBeBookedWithService(),              //данный экземпляр доп.услуги бронируется ТОЛЬКО вместе с основной=> true/false. Если true – то 'status' в доп.услуге ставится 2, если false – то 'status' в доп.услуге принимается любой.
            'bookingSomeServices' => $subServicesSupplier->canBeBookedRepeatedly(),             //возможность оформления нескольких экземпляров типа услуги
            'name' => $this->getName(),
            'typeName' => $this->getSubService()->getName()
        ];
    }

    /**
     * Структура SOAddService
     * @return array
     */
    public function toSOUTKAddService()
    {
        $prices = $this->getAddOfferPrices();

        $salesTerms = [];
        foreach ($prices as $price) {
            $salesTerms[$price->getType()] = $price->toArray();
        }

        return [
            'idAddService' => $this->idAddService,      // id доп.услуги
            'serviceId' => $this->serviceId,            // id основной услуги (доп.услуга не может быть оформлена самостоятельно)
            'serviceSubType' => $this->subServiceId,    // номер доп.услуги в справочнике доп.услуг.
            'status' => $this->status,                  // Статус доп.услуги
            'salesTerms' => $salesTerms,         // Ценовые компоненты услуги, структура ss_salesTermsInfo
            'tourists' => [],                           // массив с данными туриста. структура ss_tourist. Опционально, т.к. турист может заказать или не заказать эту доп.услугу
            'specParamAddService' => $this->getSpecParamAddService(),  //массив дополнительных параметров для доп.услуги. Опционально.
            'name' => $this->getName(),
            'typeName' => $this->getSubService()->getName(),
            'manualAddService' => null
        ];
    }
}