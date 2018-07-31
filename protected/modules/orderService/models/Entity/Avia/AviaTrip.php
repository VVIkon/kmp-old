<?php

/**
 * Трип авиа оффера
 *
 * @property $duration
 * @property $offerID
 * @property $TripId
 * @property $segments AviaOfferSegment[]
 */
class AviaTrip extends AbstractTripOffer
{
    public function tableName()
    {
        return 'kt_service_fl_trip';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'segments' => array(self::HAS_MANY, 'AviaOfferSegment', 'TripID', 'order' => 'segmentNum ASC'),
        );
    }

    /**
     * @param AviaOffer $aviaOffer
     */
    public function bindOffer(AviaOffer $aviaOffer)
    {
        $this->offerID = $aviaOffer->getOfferId();
    }


    public function getSegments()
    {
        return $this->segments;
    }

    public function fromArray(array $tripArr)
    {
        $this->duration = $tripArr['duration'];
    }

    /**
     * @return mixed
     */
    public function getTripID()
    {
        return $this->TripId;
    }
}