<?php

/**
 * Class WorkflowDelegate
 * Базовый класс для обработчиков действий для worflow заявки
 */
abstract class WorkflowDelegate
{
    /**
     * Код ошибки
     * @var int
     */
    private $errorCode;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct() {
    }

    /**
     * Запуск метода выполнения действия делегата
     * @param $params
     * @return mixed
     */
    abstract public function run($params, $module) ;

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError() {
        return $this->errorCode;
    }

}

