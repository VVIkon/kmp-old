<?php

/**
 * Class GptsRequest
 * Базовый класс для работы с запросом поиска предложения в GPTS
 */
class GptsRequest extends KFormModel
{
    /** @var string namespace для записи логов */
    protected $namespace;
    /** @var object Используется для хранения ссылки на текущий модуль */
    protected $module;
    /** @var int Тип поискового запроса */
    protected $requestType;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module, $type)
    {
        parent::__construct();
        $this->module = $module;
        $this->requestType = $type;
        $this->namespace = $this->module->getConfig('log_namespace');

    }

    public function __get($name)
    {
        switch($name) {
            case 'requestType':
                return $this->requestType;
                break;
            default:
                return undefined;
                break;
        }
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }

    public function toArray()
    {
        return [];
    }

}
