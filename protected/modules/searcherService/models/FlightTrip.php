<?php

/**
 * Class FlightTrip
 * Реализует функциональность для работы с данными
 * одной поездки из маршрута авиаперелёта
 */
class FlightTrip extends KFormModel
{
    /**
     * Идентифкатор предложения
     * @var string
     */
    private $offerKey;

    /**
     * Идентифкатор поездки из маршрута
     * @var
     */
    private $tripId;

    /**
     * Длительность поездки
     * @var
     */
    private $duration;

    /**
     * Сегменты маршрута
     * @var objects array
     */
    private $segments;

    /**
     * Конструктор класса
     * @param $params
     */
    public function __construct($params)
    {
        $this->offerKey = $params['offerKey'];
        $this->tripId = isset($params['tripId']) ? $params['tripId'] : '';
        $this->duration = $params['duration'];

        foreach ($params['segments'] as $segment) {
            $segment['offerKey'] = $params['offerKey'];
            $this->segments[] = new FlightSegment($segment);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'tripId' :
                $this->tripId = $value;
                $this->setSegmentsTripId($this->tripId);
                break;
        }
    }

    public function __get($name)
    {
        switch ($name) {
            case 'offerKey' :
                return $this->offerKey;
                break;
            case 'tripId' :
                return $this->tripId;
                break;
            case 'duration' :
                return $this->duration;
                break;
            case 'segments' :
                return $this->segments;
                break;
        }
    }

    /**
     * Вывод параметров объекта в массив
     * @return array
     */
    public function toArray($lang, $currency)
    {
        $props = [
            /*            'offerKey' => $this->offerKey,
                        'tripId' => $this->tripId,*/
            'duration' => $this->duration,
        ];

        foreach ($this->segments as $segment) {
            $props['segments'][] = $segment->toArray($lang, $currency);
        }

        return $props;
    }

    /**
     * Сравнение поездки маршрута с указанной поездкой
     * @param $trip
     * @return bool
     */
    public function isEqual($trip)
    {
        if (empty($trip)) {
            return false;
        }

        if ($this->duration != $trip->duration) {
            return false;
        }

        if (count($this->segments) != count($trip->segments)) {
            return false;
        }

        foreach ($this->segments as $key => $segment) {
            if (!$this->segments[$key]->isEqual($trip->segments[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Сохранение поездки в кэше маршрутов предложений
     */
    public function toCache($id)
    {
        $command = Yii::app()->db->createCommand();

        try {
            $res = $command->insert('fl_trip', [
                'duration' => $this->duration,
                'offerkey' => $this->offerKey,
                'id' => $id
            ]);

        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_CREATE_ROUTE_TRIP,
                $command->getText(),
                $e
            );
        }

        $this->__set('tripId', Yii::app()->db->lastInsertID);

        $this->segmentsToCache($this->tripId);
    }

    /**
     * Получение данных по поездкам авиаперелёта из кэша
     * @param $offerKeys
     * @return array|CDbDataReader
     */
    public static function fromCache($offerKeys)
    {
        $command = Yii::app()->db->createCommand();

        $command->select('offerKey, TripID tripId, duration');
        $command->from('fl_trip');
        $command->where(['in', 'offerkey', $offerKeys]);

        try {
            $tripsInfo = $command->queryAll();
        } catch (Exception $e) {

            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_TRIP,
                $command->getText(),
                $e
            );
        }
        return $tripsInfo;
    }

    /**
     * Получить коды аэропортов поездки
     * @return array
     */
    public function getTripAirportsIataCodes()
    {
        $iataCodes = [];
        foreach ($this->segments as $segment) {

            if ($segment->departureAirportCode != '') {
                $iataCodes[] = $segment->departureAirportCode;
            }

            if ($segment->arrivalAirportCode != '') {
                $iataCodes[] = $segment->arrivalAirportCode;
            }
        }

        return $iataCodes;
    }

    private function setSegmentsTripId($tripId)
    {
        foreach ($this->segments as $segment) {
            $segment->tripId = $tripId;
        }
    }

    protected function segmentsToCache()
    {
        foreach ($this->segments as $segmentNum => $segment) {
            $segment->toCache($segmentNum);
        }
    }
}

