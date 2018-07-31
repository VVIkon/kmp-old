<?php

/**
 * Class GptsResponse
 * Базовый класс для работы с данными полученного предложения от GPTS
 */
class GptsResponse extends KFormModel
{

    const GPTS_ENGINE = 5;
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
     * Тип предложения
     * @var
     */
    protected $responseType;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module, $type)
    {
        parent::__construct();
        $this->module = $module;
        $this->responseType = $type;
        $this->namespace = $this->module->getConfig('log_namespace');

    }

    public function initParams($params)
    {
        throw new KmpLogicException('method not implemented',SearcherErrors::SERVICE_TYPE_INCORRECT);
        return false;
    }

    public function __get($name)
    {
        switch($name) {
            case 'responseType' :
                return $this->responseType;
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