<?php

/**
 * Модель сегмента авиа перелета
 * @property $offerID
 * @property $departureDate
 *
 * @property Airport $arrivalAirport
 * @property Airport $departureAirport
 * @property $validatingAviaCompany AviaCompany
 */
class AviaOfferSegment extends AbstractSegmentOffer
{
    public function tableName()
    {
        return 'kt_service_fl_Segments';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'arrivalAirport' => array(self::BELONGS_TO, 'Airport', array('arrivalAirportCode' => 'IATA')),
            'departureAirport' => array(self::BELONGS_TO, 'Airport', array('departureAirportCode' => 'IATA')),
            'validatingAviaCompany' => array(self::BELONGS_TO, 'AviaCompany', array('validatingAirline' => 'carrierIATA'))
        );
    }

    public function fromArray(array $segment)
    {
        $this->flightSegmentName = $segment['flightSegmentName'];
        $this->segmentNum = $segment['segmentNum'];
        $this->validatingAirline = $segment['validatingAirline'];
        $this->marketingAirline = $segment['marketingAirline'];
        $this->operatingAirline = $segment['operatingAirline'];
        $this->flightNumber = $segment['flightNumber'];
        $this->aircraftName = $segment['aircraftName'];
        $this->aircraftCode = $segment['aircraftCode'];
        $this->duration = $segment['duration'];
        $this->departureAirportCode = $segment['departureAirportCode'];
        $this->classType = $segment['categoryClass']['classType'];
        $this->code = $segment['categoryClass']['code'];
//        $this->baggageMeasureCode = $segment['baggageMeasureCode'];
//        $this->baggageMeasureQuantity = $segment['baggageMeasureQuantity'];
        $this->baggageData = isset($segment['baggage']) ? json_encode($segment['baggage']) : null;
        $this->mealCode = $segment['mealCode'];
        $this->stopQuantity = $segment['stopQuantity'];
        $this->arrivalTerminal = $segment['arrivalTerminal'];
        $this->arrivalDate = $segment['arrivalDate'];
        $this->arrivalAirportCode = $segment['arrivalAirportCode'];
        $this->departureDate = $segment['departureDate'];
        $this->departureTerminal = $segment['departureTerminal'];
        $this->stopLocations = $this->setStopLocations($segment['stops']);
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

    public function bindOffer(AviaOffer $aviaOffer)
    {
        $this->offerID = $aviaOffer->getOfferId();
    }

    /**
     * @return mixed
     */
    public function getValidatingAviaCompany()
    {
        return $this->validatingAviaCompany;
    }
}