<?php

/**
 * Class FlightGetOfferRequest
 * Базовый класс для рботы с запросом получения предложения авиаперелёта
 */
class FlightGetOfferRequest extends SearchRequest
{
    /**
     * Идентификатор предложения
     * @var string
     */
    private $offerKey;

    /**
     * Конструктор класса
     * @param $module object
     * @param $type int тип поискового запроса
     */
    public function __construct($module)
    {
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Инициализация параметров запроса предложения
     * @param $params
     */
    public function initParams($params)
    {
      $this->offerKey = $params['offerKey'];
    }

    /**
     * Установка свойств класса
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'offerKey':
                $this->offerKey = $value;
                break;
        }
    }

    /**
     * Проверка значений в свойствах класса
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->$name);
    }

    public function toArray()
    {
        return $props['offerKey'] = $this->offerKey;
    }
}