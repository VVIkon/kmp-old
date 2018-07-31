<?php

/**
 * Class DictionaryHandler
 * Базовый класс для обработки запросов к справочникам
 */
abstract class AbstractDictionaryHandler
{
    /**
     * Пространство имён для логирования
     * @var string
     */
    protected $namespace;

    /**
     * Объект модуля
     * @var object
     */
    protected $module;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        $this->module = $module;
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Получение справочных данных
     */
    abstract public function getDictionaryData($params);
}