<?php

/**
 * Class GptsFlightTrip
 * Реализует функциональность для работы с данными авиаперелёта в GPTS
 */
class GptsFlightTrip extends KFormModel
{

    private $duration;
    private $segments;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($duration, $segments)
    {
        $this->duration = $duration;

        foreach ($segments as $segment) {
            $segment = new GptsFlightSegment($segment);
            $this->segments[] = $segment;
        }
    }

    public function __get($name)
    {
        switch ($name) {
            case 'duration' :
                return $this->from;
                break;
        }

    }

    /**
     * Вывод параметров объекта в массив
     * @return array
     */
    public function toArray()
    {
        $trip = [];
        $trip['duration'] = $this->duration;

        foreach ($this->segments as $segment) {
            $trip['segments'][] = $segment->toArray();
        }

        return $trip;
    }

}

