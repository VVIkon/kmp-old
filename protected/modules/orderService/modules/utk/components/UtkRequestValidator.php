<?php

/**
 * Class UtkRequestValidator
 * Класс для проверки корректности значений поступивших от УТК
 */
class UtkRequestValidator extends Validator
{
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
        $this->module = $module;
    }

    /**
     * Проверка параметров получения информации по клиентам
     * @param $params
     */
    public function checkGetClientsListParams($params) {

        if (empty($params['clientId']) && empty($params['clientIdUtk']) && empty($params['pass'])) {
            $this->_errorCode = UtkErrors::INCORRECT_INPUT_PARAM;
            return false;
        }

        $operationTypes = ['one','all','changed','messageReceived'];

        if (empty($params['operation']) || !in_array($params['operation'], $operationTypes)) {
            $this->_errorCode = UtkErrors::INCORRECT_INPUT_PARAM;
        }

        if ($params['operation'] == 'one' && empty($params['clientIdUtk'])) {
                $this->_errorCode = UtkErrors::INCORRECT_INPUT_PARAM;
                return false;
        }

        if ($params['operation'] == 'messageReceived' && empty($params['numberMessage']) ) {
                $this->_errorCode = UtkErrors::INCORRECT_INPUT_PARAM;
                return false;
        }

        return true;
    }

    /**
     * Проверка параметров запроса к УТК на
     * получение списка заявок
     * @param $params array входные параметры
     * @return bool
     */
    public function checkUTKOrderListParams($params) {

        if (empty($params['dateStart'])) {
            $this->_errorCode = UtkErrors::DATE_START_NOT_SET;
            return false;
        }

        if (empty($params['dateEnd'])) {
            $this->_errorCode = UtkErrors::DATE_END_NOT_SET;
            return false;
        }

        if (!$this->validateDate(($params['dateStart']))) {
            $this->_errorCode = UtkErrors::DATE_START_INCORRECT;
            return false;
        }

        if (!$this->validateDate(($params['dateEnd']))) {
            $this->_errorCode = UtkErrors::DATE_END_INCORRECT;
            return false;
        }

        return true;
    }

    /**
     * Проверка параметров запроса к УТК на
     * получение полной информации о заявке
     * @param $params array входные параметры
     * @return bool|null
     */
    public function checkUTKOrderViewParams($params) {

        if (empty($params['orderId']) || !is_numeric($params['orderId'])) {
            $this->_errorCode = UtkErrors::ORDER_ID_INCORRECT;
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