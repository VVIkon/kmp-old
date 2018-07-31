<?php

/**
 * Class Trip
 * Реализует функциональность для работы с данными поездки
 */
class Trip extends KFormModel
{

    private $from;
    private $to;
    private $date;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($from, $to, $date)
    {
        $this->from = $from;
        $this->to = $to;
        $this->date = DateTime::createFromFormat('Y-m-d',$date);
    }

    public function __get($name)
    {

        switch ($name) {
            case 'from' :
                return $this->from;
                break;
            case 'to' :
                return $this->to;
                break;
            case 'date' :
                return $this->date;
                break;
            case 'datestr' :
                return $this->date->format('Y-m-d');
                break;
        }

    }

    /**
     * Вывод параметров объекта в массив
     * @return array
     */
    public function toArray()
    {
        return ['from' => $this->from, 'to' => $this->to , 'date' => $this->datestr];
    }
}

