<?php

/**
 * Трип авиаперелета
 *
 * @property AviaResponseSegmentService [] $segments
 * @property $TripID
 * @property $offerkey
 * @property $duration
 */
class AviaResponseTripService extends AbstractTripOffer
{

    public function tableName()
    {
        return 'fl_trip';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'segments' => array(self::HAS_MANY, 'AviaResponseSegmentService', 'TripID', 'order' => 'segmentNum ASC', 'with' => array('arrivalAirport', 'departureAirport')),
        );
    }

    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * @param AbstractAviaOffer $aviaOffer
     * @return mixed
     */
    public function bindOffer(AbstractAviaOffer $aviaOffer)
    {
        // TODO: Implement bindOffer() method.
    }


    public function toArray($routeName)
    {
        // Trip part's offer
        $tripArr = parent::toArray();
        $tripArr["routeName"] = $routeName;   // Название трипа = коды аэропортов начальной и конечной точки трипа. Вычисляемый атрибут.

        return $tripArr;
    }

}