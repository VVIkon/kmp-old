<?php

/** Базовый класс менеджеров услуг */
abstract class ServiceManager
{
    /** @var KModule ссылка на модуль */
    protected $module;
    /** @var GPTSApiClient ссылка на клиент запросов к GPTS API */
    protected $apiClient;
    /** @var mixed[] текущая конфигурация модуля */
    protected $config;

    /** @var ServiceValidator валидатор класса */
    protected $validator;

    public function __construct(&$module, &$apiClient)
    {
        $this->module = $module;
        $this->apiClient = $apiClient;

        if (EnvironmentHelper::getEnvironmentType() == EnvironmentHelper::PRODUCTION) {
            $this->config = $module->getConfig()['prod_api'];
        } else {
            $this->config = $module->getConfig()['test_api'];
        }
    }

    /**
     * Метод получения оффера
     * @param mixed[] $params параметры команды
     */
    abstract public function getOffer($params);

    /**
     * Метод запуска бронирования оффера
     * @param mixed[] $params параметры команды
     */
    abstract public function serviceBooking($params);

    /**
     * Получение билетов
     * @param mixed[] $params параметры команды
     */
    abstract public function getEtickets($params);

    /**
     * Метод запуска бронирования оффера
     * @param mixed[] $params параметры команды
     */
    abstract public function serviceModify($params);
}