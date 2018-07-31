<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 22.09.17
 * Time: 11:47
 */
class ScheduleRequestValidator
{
    const ERROR_NONE = 300;
    const ERROR_NO_TASK_NAME = 301;
    const ERROR_NO_PERIOD = 302;
    const ERROR_NO_PERIOD_DETAIL = 303;
    const ERROR_NO_TASK_OPERATION = 304;
    const ERROR_NO_TASK_SERVICE = 305;
    const ERROR_NO_TASK_PARAMS = 306;
    const ERROR_NO_COMPANY_ID = 307;
    const ERROR_NO_TASK_ID = 308;


    /**
     * Код ошибки
     * @var int
     */
    private $_errorCode;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module) {
        $this->_module = $module;
    }


    /**
     * Проверка входных параметров
     * для записи расписания
     * @param $params
     * @return bool
     */
    public function checkSetScheduleParams($params)
    {
        if (empty($params['taskName'])) {
            $this->_errorCode = self::ERROR_NO_TASK_NAME;
            return false;
        }

        if (empty($params['period'])) {
            $this->_errorCode = self::ERROR_NO_PERIOD;
            return false;
        }
//        if (empty($params['periodDetail'])) {
//            $this->_errorCode = self::ERROR_NO_PERIOD_DETAIL;
//            return false;
//        }
        if (empty($params['taskOperation'])) {
            $this->_errorCode = self::ERROR_NO_TASK_OPERATION;
            return false;
        }
        if (empty($params['taskService'])) {
            $this->_errorCode = self::ERROR_NO_TASK_SERVICE;
            return false;
        }
        if (empty($params['taskParams'])) {
            $this->_errorCode = self::ERROR_NO_TASK_PARAMS;
            return false;
        }
        return true;
    }

    /**
     * Проверка входных параметров
     * для получения расписания
     * @param $params
     * @return bool
     */
    public function checkGetScheduleParams($params)
    {
        if ( is_string($params['companyId'])) {
            $this->_errorCode = self::ERROR_NO_COMPANY_ID;
            return false;
        }
        return true;
    }

    /**
     * Проверка входных параметров
     * для удаления расписания
     * @param $params
     * @return bool
     */
    public function checkDeleteSchedule($params)
    {
        if (empty($params['DeleteTaskId'])) {
            $this->_errorCode = self::ERROR_NO_TASK_ID;
            return false;
        }
        return true;
    }
    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError() {
        return $this->_errorCode;
    }
}