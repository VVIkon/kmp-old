<?php

/**
 * Модель доп услуги оффера
 *
 * @property $id
 *
 * @property HotelAddOfferPrice[] $addOfferPrices
 * @property RefSubServices $refSubService
 */
class HotelAddOffer extends AbstractHotelAddOffer
{
    public function tableName()
    {
        return 'kt_service_ho_addOffers';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'hotelOffer' => array(self::BELONGS_TO, 'HotelOfferResponse', 'offerId'),
            'addOfferPrices' => array(self::HAS_MANY, 'HotelAddOfferPrice', 'addOfferId'),
            'refSubService' => array(self::BELONGS_TO, 'RefSubServices', 'subServiceId'),
        );
    }

    public function fromArray($data)
    {
        $this->setSubServiceId(isset($data['serviceSubType']) ? $data['serviceSubType'] : null);
        $this->setName(isset($data['name']) ? $data['name'] : '');
        $this->setEngineData(isset($data['engineData']) ? $data['engineData'] : '');
        $this->setSpecParamAddService(isset($data['specParamAddService']) ? $data['specParamAddService'] : '');
    }

    /**
     *
     * @param HotelOffer $offer
     */
    public function bindHotelOffer(HotelOffer $offer)
    {
        $this->offerId = $offer->getOfferId();
    }

    /**
     * @return mixed
     */
    public function getOfferId()
    {
        return $this->offerId;
    }

    /**
     * @return RefSubServices
     */
    public function getSubService()
    {
        return $this->refSubService;
    }

    /**
     * @return HotelAddOfferPrice[]
     */
    public function getAddOfferPrices()
    {
        return $this->addOfferPrices;
    }
}