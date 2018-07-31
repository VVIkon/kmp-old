<?php

/**
 * @inheritdoc
 *
 * @property Airport $arrivalAirport
 * @property Airport $departureAirport
 */
class AviaResponseSegmentService extends AbstractSegmentOffer
{

    public function tableName()
    {
        return 'fl_Segments';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'trips' => array(self::HAS_MANY, 'AviaResponseTripService', 'id'),
            'AviaSearchRequest' => array(self::BELONGS_TO, 'AviaSearchRequest', 'token'),
            'arrivalAirport' => array(self::BELONGS_TO, 'Airport', array('arrivalAirportCode' => 'IATA'), 'with' => array('city' => array('alias' => 'arrivalAirportCity'))),
            'departureAirport' => array(self::BELONGS_TO, 'Airport', array('departureAirportCode' => 'IATA'), 'with' => array('city' => array('alias' => 'departureAirportCity'))),
        );
    }

    /**
     * @return Airport
     */
    public function getArrivalAirport()
    {
        return $this->arrivalAirport;
    }

    /**
     * @return Airport
     */
    public function getDepartureAirport()
    {
        return $this->departureAirport;
    }

    public function toArray()
    {
        // Segment part's offer
        $segmentArr = parent::toArray();
        return $segmentArr;
    }

}