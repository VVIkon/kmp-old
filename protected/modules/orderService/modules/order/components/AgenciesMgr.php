<?php

/**
 * класс для работы с данными агентств и их сотрудников
 * Class AgenciesMgr
 */
class AgenciesMgr
{
    /**
     * Ссылка на объект модуля
     * @var object
     */
    private $_module;

    /**
     * Код ошибки
     * @var int
     */
    private $_errorCode;
    /**
     * namespace для записи логов
     * @var string
     */
    private $_namespace;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        $this->_namespace = "system.orderservice";
        $this->_module = $module;
    }

    /**
     * Обновление информации по агентствам
     * @param array $params
     * @return array результат обработки списка агентств
     */
    public function updateAgenciesInfo($params)
    {
        $updateInfo = [];

        if (empty($params) || !isset($params['clients'])) {
            $this->_errorCode = OrdersErrors::INCORRECT_INPUT_PARAM;
            return false;
        }

        $result = [];
        $result['saveResults'] = [];

        foreach ($params['clients'] as $agencyInfo) {
            $result['saveResults'][] = $this->updateAgencyInfo($agencyInfo);
        }

        return $result;
    }

    /**
     * Обновление информации по агентствам
     * @param array $agencyInfo объект данных агентства
     * @return array результат обработки агентства
     */
    public function updateAgencyInfo($agencyInfo)
    {
        $agency = new AgentForm($this->_namespace);
        if (!$agency->setAttributesFromUtk($agencyInfo)) {
            $this->_errorCode = OrdersErrors::INCORRECT_INPUT_PARAM;
            return false;
        }

        $saveResult = $agency->save();

        if (!$saveResult) {
            $this->_errorCode = OrdersErrors::CANNOT_CREATE_AGENCY;

            LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                'Невозможно записать данные агентства для ' . print_r($agencyInfo, 1), 'trace',
                $this->_namespace . '.errors');

            return false;
        }

        return $saveResult;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->_errorCode;
    }

}
