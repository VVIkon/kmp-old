<?php

/**
 * Class Offer
 * Базовый класс для работы с полученными от провайдера предложениями
 */
class ProviderOffer extends KFormModel
{
    /**
     * namespace для записи логов
     * @var string
     */
    protected $namespace;

    /**
     * Используется для хранения ссылки на текущий модуль
     * @var object
     */
    protected $module;

    /**
     * Тип предложения в ответе от провайдера
     * @var
     */
    protected $offerType;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        parent::__construct();
        $this->module = $module;
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    public function __get($name)
    {
        switch($name) {
            case 'offerType' :
                return $this->offerType;
                break;
        }
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }
}