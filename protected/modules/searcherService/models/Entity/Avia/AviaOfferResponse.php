<?php

/**
 * Оффер результат поиска по авиабилетам
 *
 * @property $token
 *
 * @property TokenCache $tokenCache
 * @property AviaSearchRequest $AviaSearchRequest
 * @property AviaOfferResponseTravelPolicyValue $offerValue
 */
class AviaOfferResponse extends AbstractAviaOffer implements ResponseOfferInterface
{

    public function tableName()
    {
        return 'fl_Offer';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'trips' => array(self::HAS_MANY, 'AviaResponseTripService', 'id', 'order' => 'trips.TripID ASC'),
            'AviaSearchRequest' => array(self::BELONGS_TO, 'AviaSearchRequest', 'token'),
            'offerValue' => array(self::HAS_ONE, 'AviaOfferResponseTravelPolicyValue', 'fl_Offer_id'),
            'tokenCache' => array(self::BELONGS_TO, 'TokenCache', 'token'),
        );
    }

    /**
     * @return TokenCache
     */
    public function getTokenCache()
    {
        return $this->tokenCache;
    }

    /**
     * @return mixed|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AviaResponseTripService []
     */
    public function getTrips()
    {
        return $this->trips;
    }

    private function calculateTimelimitBookDate()
    {
        $tokenCache = $this->getTokenCache();
        $expareDate = new DateTime($tokenCache['StartDateTime']);
        $cacheClear = $this->getConfig();
        $interval = new DateInterval($cacheClear['cacheClear']);   //PT2H
        return $expareDate->add($interval)->format('Y-m-d H:i:s');
    }

    /**
     * @return mixed
     */
    public function toArray()
    {
        // добавим номер оффера в таблице
        $offerArr = parent::toArray();
        $offerArr['offerId'] = $this->getId();
        $offerArr['timeLimitBookingDate'] = $this->calculateTimelimitBookDate();

        return $offerArr;
    }

    /**
     * @return integer
     */
    public function getAdult()
    {
        return $this->AviaSearchRequest->getAdult();
    }

    /**
     * @return integer
     */
    public function getChild()
    {
        return $this->AviaSearchRequest->getChild();
    }

    /**
     * Младенцы
     * @param
     * @return integer
     */
    public function getInfant()
    {
        return $this->AviaSearchRequest->getInfant();
    }

    /**
     * @return mixed
     */
    public function createTpValueClass()
    {
        return new AviaOfferResponseTravelPolicyValue();
    }

    /**
     * @return array
     */
    public function getDescriptionAsMinimalPrice()
    {
        return [
            'serviceName' => $this->generateServiceName(),
            'price' => $this->getAmountBrutto(),
            'currency' => $this->getCurrencyBrutto(),
            'duration' => $this->getTotalDuration(),
            'from' => $this->getFirstTrip()->getFirstSegment()->getDepartureAirport()->getCity()->getName() . ' ' . "({$this->getFirstTrip()->getFirstSegment()->getDepartureAirport()->getIATACode()})",
            'to' => $this->getLastTrip()->getLastSegment()->getArrivalAirport()->getCity()->getName() . ' ' . "({$this->getLastTrip()->getLastSegment()->getArrivalAirport()->getIATACode()})",
            'changes' => $this->countTransfers(),
            'class' => $this->getFirstTrip()->getFirstSegment()->getClassType(),
            'dateStart' => $this->getFirstTrip()->getFirstSegment()->getDepartureDate(),
            'dateFinish' => $this->getLastTrip()->getLastSegment()->getArrivalDate()
        ];
    }
}