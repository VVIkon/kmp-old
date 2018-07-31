<?php

/**
 * Оффер результат поиска по отелям
 *
 * @property $token
 *
 * @property $id
 * @property $offerKey
 * @property HotelResponseRoomService[] $roomServices
 * @property HotelResponsePriceOffer[] $priceOffers
 * @property HotelResponseTaxOffer[] $taxOffers
 * @property HotelResponsePriceOfferByDay[] $priceOfferByDays
 * @property $hotelID
 *
 *
 * @property HotelInfo $HotelInfo
 * @property TokenCache $tokenCache
 * @property HotelOfferResponseTravelPolicyValue $offerValue
 * @property HotelAddOfferResponse[] $addOffers
 */
class HotelOfferResponse extends AbstractHotelOffer implements ResponseOfferInterface
{
    public function tableName()
    {
        return 'ho_offers';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'roomServices' => array(self::HAS_MANY, 'HotelResponseRoomService', 'offerKey'),
            'priceOfferByDays' => array(self::HAS_MANY, 'HotelResponsePriceOfferByDay', 'offerKey'),
            'priceOffers' => array(self::HAS_MANY, 'HotelResponsePriceOffer', 'offerKey'),
            'taxOffers' => array(self::HAS_MANY, 'HotelResponseTaxOffer', 'offerKey'),
            'HotelInfo' => array(self::BELONGS_TO, 'HotelInfo', 'hotelID'),
            'TokenCache' => array(self::BELONGS_TO, 'TokenCache', 'token'),
            'offerValue' => array(self::HAS_ONE, 'HotelOfferResponseTravelPolicyValue', 'ho_offers_id'),
            'tokenCache' => array(self::BELONGS_TO, 'TokenCache', 'token'),
            'addOffers' => array(self::HAS_MANY, 'HotelAddOfferResponse', 'offerId'),
        );
    }

    /**
     * @return TokenCache
     */
    public function getTokenCache()
    {
        return $this->tokenCache;
    }

    public function init()
    {

    }

    public function getRoomServices()
    {
        return $this->roomServices;
    }

    public function getPriceOffers()
    {
        return $this->priceOffers;
    }

    public function getPriceOfferByDays()
    {
        return $this->priceOfferByDays;
    }

    public function getHotelId()
    {
        return $this->hotelID;
    }

    public function getId()
    {
        return $this->id;
    }


    public function getEngineData()
    {
        // TODO: Implement getEngineData() method.
    }

    /**
     * Получение отельных данных
     * @return HotelInfo
     */
    public function getHotelInfo()
    {
        return $this->HotelInfo;
    }

    /**
     * @return mixed
     */
    public function createTpValueClass()
    {
        return new HotelOfferResponseTravelPolicyValue();
    }

    private function calculateTimelimitBookDate(){
        $tokenCache = $this->getTokenCache();
        $expareDate = new DateTime($tokenCache['StartDateTime']);
        $cacheClear = $this->getConfig();
        $interval = new DateInterval($cacheClear['cacheClear']);   //PT2H
        return $expareDate->add($interval)->format('Y-m-d H:i:s');
    }

    public function toArray()
    {
        // добавим номер оффера в таблице
        $offerArr = parent::toArray();
        $offerArr['offerId'] = $this->id;
        if(empty($offerArr['additionalServices'])) {
            $offerArr['additionalServices'] = null;
        }
        $offerArr['timeLimitBookingDate'] = $this->calculateTimelimitBookDate();
        return $offerArr;
    }

    /**
     * @return mixed
     */
    public function getDescriptionAsMinimalPrice()
    {
        $clientPrice = $this->getClientPrice();

        return [
            'serviceName' => $this->generateServiceName(),
            'price' => $clientPrice->sum,
            'currency' => $clientPrice->currency->getCode(),
            'hotelName' => $this->getHotelInfo()->getHotelName(),
            'category' => $this->getHotelInfo()->getCategory(),
            'roomType' => $this->getRoomType(),
            'mealType' => $this->getMealType()
        ];
    }

    /**
     *
     * @return HotelAddOfferResponse[]
     */
    public function getAddOffers()
    {
        return $this->addOffers;
    }

    /**
     * Проверка наличия доп услуг в оффере
     * @return bool
     */
    public function hasAddOffers()
    {
        return count($this->addOffers) > 0;
    }
}