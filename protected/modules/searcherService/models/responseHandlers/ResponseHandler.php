<?php

/**
 * Class ResponseHandler
 * Базовый класс формирования возвращаемой информации по предложениям
 */
class ResponseHandler extends KFormModel
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
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        $this->module = $module;
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Получить информацию по предложениям по токену
     */
    public function getOffersInfoByToken($token, $lang, $currency)
    {
        throw new KmpException(get_class($this), __FUNCTION__, SearcherErrors::METHOD_NOT_IMPLEMENTED, '');
        return false;
    }

}