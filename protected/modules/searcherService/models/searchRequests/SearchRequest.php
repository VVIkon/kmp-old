<?php

/**
 * Class SearchRequest
 * Базовый класс для рботы с запросом поиска предложения
 */
class SearchRequest extends KFormModel
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
     * Используется для хранения провайдеров запроса
     * @var
     */
    protected $providers;

    /**
     * Используется для
     * @var
     */
    protected $suppliers;

    /**
     * Тип поискового запроса
     * @var
     */
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

    /**
     * Задать провайдеров для обработки поискового запроса
     * @param $providers array
     */
    public function setProviders($providers)
    {
        if (!empty($providers) && is_array($providers)) {
            $this->providers = $providers;
        }
    }

    /**
     * Задать параметры поставщиков услуг для обработки поискового запроса
     * @param $suppliers array
     */
    public function setSuppliers($suppliers)
    {
        if (!empty($suppliers) && is_array($suppliers)) {
            $this->suppliers = $suppliers;
        }
    }

    public function __get($name)
    {
        switch ($name) {
            case 'providers' :
                return $this->providers;
                break;
            case 'requestType' :
                return $this->requestType;
                break;
        }
    }

    /**
     * Добавим в условие поиска время жизни токена
     * @param CDbCommand $command
     * @return CDbCommand
     */
    protected function addTokenLifetimeCondition(CDbCommand $command)
    {
        $module = YII::app()->getModule('searcherService');
        $time_diff = $module->getConfig('cacheClear');

        if (empty($time_diff)) {
            return $command;
        }

        $TokenLifeLimit = new DateTime();
        $TokenLifeLimit->sub(new DateInterval($time_diff));

        return $command->join('token_cache tk', "UNIX_TIMESTAMP(tk.StartDateTime) > '{$TokenLifeLimit->getTimestamp()}' AND tk.token = sr.token");
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }
}