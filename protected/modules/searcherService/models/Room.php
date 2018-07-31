<?php

/**
 * Class Room
 * Реализует функциональность для работы с данными номеров проживания
 */
class Room extends KFormModel
{
    private $adults;
    private $children;
    private $infants;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($adults, $children, $infants)
    {
        $this->adults = $adults;
        $this->children = $children;
        $this->infants = $infants;
    }

    /**
     * Вывести массив возрастов детей
     * @return array
     */
    public function childrenToArray()
    {
        $children = !empty($this->children) ? array_fill(0,$this->children, 8) : [];
        $infants = !empty($this->infants) ?  array_fill($this->children, $this->infants, 1) : [] ;
        return array_merge($children, $infants);
    }


    /**
     *
     * Вывод параметров объекта в массив
     * @return array
     */
    public function toArray()
    {
        return ['adults' => $this->adults, 'children' => $this->childrenToArray()];
    }

    /**
     * Получить количество детей старше 2 лет
     * @param $childrenAges
     * @return int
     */
    public static function getChildrenCount($childrenAges)
    {
        return self::getPersonsByAge($childrenAges, 3, 16);
    }

    /**
     *
     */
    public static function getInfantsCount($childrenAges)
    {
        return self::getPersonsByAge($childrenAges, 0, 2);
    }

    /**
     * Получить количество человек из масива возрастов в указанных границах
     * @param $ages
     * @param int $minimumAge минимальная граница возраста (включительно)
     * @param int $maximumAge максимальная граница возраста (включительно)
     * @return int
     */
    public static function getPersonsByAge($ages, $minimumAge = 0, $maximumAge = 100)
    {

        $personsCount = 0;
        if (empty($ages) || !is_array($ages)) {
            return 0;
        }

        foreach ($ages as $age) {

            if ($age >= $minimumAge && $age <= $maximumAge) {
                $personsCount++;
            }
        }

        return $personsCount;
    }

    /**
     * Получение свойств объекта
     * @param $name
     * @return string
     */
    public function __get($name)
    {
        switch ($name) {
            case 'adults' :
                return $this->adults;
                break;
            case 'children' :
                return $this->children;
                break;
            case 'infants' :
                return $this->infants;
                break;
        }
    }
}

