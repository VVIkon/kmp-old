<?php

/**
 * Class OwmActionsValidator
 * Класс для проверки корректности значений при выполнении действий OrderWorkflowManager
 */
class OwmActionsValidator extends Validator
{
    /**
     * Код ошибки
     * @var int
     */
    private $_errorCode;

    private $namespace;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        parent::__construct($module);
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Проверка параметров запроса на получение найденных предложений
     * @param $params
     */
    public function checkRunActionParams($params)
    {
        $this->validateComplex($params, [
            /** @todo orderId необязателен для команды addService */
            /*  ['orderId', 'included','message' => OrdersErrors::ORDER_ID_NOT_SET],
              ['orderId', 'checkOrderExists',
                  'message' => OrdersErrors::ORDER_NOT_FOUND,
              ],*/
            ['action', 'required', 'message' => OrdersErrors::ACTION_TYPE_NOT_SET],
            ['action', 'checkActionType', 'message' => OrdersErrors::UNKNOWN_ACTION_TYPE],
            ['actionParams', 'required', 'message' => OrdersErrors::ACTION_DETAILS_NOT_SET],
        ]);
    }

    /**
     * Проверка существования заявки
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkOrderExists($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpInvalidSettingsException(
                get_class($this), __FUNCTION__,
                SearcherErrors::INCORRECT_VALIDATION_RULES,
                []
            );
        }

        if (empty($values[$attribute])) {
            return true;
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($values[$attribute]);

        throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);

        return true;
    }

    /**
     * Проверка существования типа запрашиваемого действия
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkActionType($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute]) || !OrderWorkflowManager::isActionExists($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Валидация команды Import
     */
    public function checkImport($params)
    {
        $this->validateComplex($params, [
            ['order', 'required', 'message' => OrdersErrors::INCORRECT_INPUT_PARAM],
            ['services', 'required', 'message' => OrdersErrors::NO_SERVICES_IN_ORDER],
            ['services', 'isArray', 'message' => OrdersErrors::NO_SERVICES_IN_ORDER],
            ['tourists', 'required', 'message' => OrdersErrors::NO_TOURISTS_IN_ORDER],
            ['tourists', 'isArray', 'message' => OrdersErrors::NO_TOURISTS_IN_ORDER]
        ]);

        if (count($params['services']) == 0) {
            throw new KmpInvalidSettingsException(
                get_class($this), __FUNCTION__,
                OrdersErrors::NO_SERVICES_IN_ORDER,
                ['params' => $params]
            );
        }

        if (count($params['tourists']) == 0) {
            throw new KmpInvalidSettingsException(
                get_class($this), __FUNCTION__,
                OrdersErrors::NO_TOURISTS_IN_ORDER,
                ['params' => $params]
            );
        }

        $this->validateComplex($params['order'], [
            ['orderId_UTK', 'required', 'message' => OrdersErrors::UTK_ORDER_ID_NOT_SET],
            ['userID', 'required', 'message' => OrdersErrors::AGENCY_USER_ID_NOT_SET],
            ['contractId', 'required', 'message' => OrdersErrors::AGENCY_CONTRACT_NOT_SET]
        ]);

        $tourleaderpresent = false;
        foreach ($params['tourists'] as $tourist) {
            $this->validateComplex($tourist, [
                ['firstName', 'required', 'message' => OrdersErrors::TOURIST_HAS_EMPTY_REQUIRED_FIELDS],
                ['lastName', 'required', 'message' => OrdersErrors::TOURIST_HAS_EMPTY_REQUIRED_FIELDS],
                ['dateOfBirth', 'required', 'message' => OrdersErrors::TOURIST_HAS_EMPTY_REQUIRED_FIELDS]
            ]);

            if ((bool)$tourist['tourLeader']) {
                $tourleaderpresent = true;
            }
        }

        if (!$tourleaderpresent) {
            throw new KmpInvalidSettingsException(
                get_class($this), __FUNCTION__,
                OrdersErrors::TOURLEADER_NOT_FOUND,
                ['params' => $params]
            );
        }
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
