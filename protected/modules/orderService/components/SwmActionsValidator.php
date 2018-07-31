<?php

/**
 * Class SwmActionsValidator
 * Класс для проверки корректности значений при выполнении действий ServiceWorkflowManager
 */
class SwmActionsValidator extends Validator
{
    /**
     * Код ошибки
     * @var int
     */
    private $errorCode;

    private $namespace;
    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module) {

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
            //['orderId', 'included','message' => OrdersErrors::ORDER_ID_NOT_SET],
            /*['orderId', 'checkOrderExists',
                'message' => OrdersErrors::ORDER_NOT_FOUND,
            ],*/
            // serviceId не обязательный параметр для действий SWM
            //['serviceId', 'included','message' => OrdersErrors::SERVICE_ID_NOT_SET],
            ['action', 'required','message' => OrdersErrors::ACTION_TYPE_NOT_SET],
            ['action', 'checkActionType','message' => OrdersErrors::UNKNOWN_ACTION_TYPE],
            //['actionParams', 'required','message' => OrdersErrors::ACTION_DETAILS_NOT_SET],
        ]);
    }

    /**
     * Проверка существования заявки
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkOrderExists($values, $attribute, $params) {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpInvalidSettingsException(
                get_class($this),
                __FUNCTION__SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute])) {
            return true;
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($values[$attribute]);

        if (empty($orderInfo)) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка существования типа запрашиваемого действия
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkActionType($values, $attribute, $params) {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute]) || !ServiceWorkflowManager::isActionExists($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this),__FUNCTION__,$params['message'],$values);
        }

        return true;
    }

    /**
    * Валидация команды Import
    */
    public function checkImport($params) {
      $this->validateComplex($params, [
          ['serviceId_Utk', 'required','message' => OrdersErrors::SERVICE_ID_NOT_SET],
          ['serviceType', 'checkServiceType','message' => OrdersErrors::SERVICE_TYPE_NOT_SET],
          ['dateStart', 'checkDate','message' => OrdersErrors::SERVICE_DATE_START_INCORRECT],
          ['dateFinish', 'checkDate','message' => OrdersErrors::SERVICE_DATE_END_INCORRECT],
          ['salesTerms', 'isArray','message' => OrdersErrors::SERVICE_PRICE_NOT_SET]
      ]);

      $this->validateComplex($params['salesTerms'], [
          ['supplierCurrency', 'isArray','message' => OrdersErrors::SERVICE_PRICE_NOT_SET],
          ['saleCurrency', 'isArray','message' => OrdersErrors::SERVICE_PRICE_NOT_SET]
      ]);

      $this->validateComplex($params['salesTerms']['supplierCurrency'], [
          ['supplier', 'isArray','message' => OrdersErrors::SERVICE_PRICE_NOT_SET]
      ]);

      $this->validateComplex($params['salesTerms']['supplierCurrency']['supplier'], [
          ['currency', 'checkCurrency','message' => OrdersErrors::SUPPLIER_CURRENCY_INCORRECT],
          ['amountBrutto', 'required','message' => OrdersErrors::SERVICE_PRICE_NOT_SET]
      ]);

      $this->validateComplex($params['salesTerms']['saleCurrency'], [
          ['client', 'isArray','message' => OrdersErrors::SERVICE_PRICE_NOT_SET]
      ]);

      $this->validateComplex($params['salesTerms']['saleCurrency']['client'], [
          ['currency', 'checkCurrency','message' => OrdersErrors::SUPPLIER_CURRENCY_INCORRECT],
          ['amountBrutto', 'required','message' => OrdersErrors::CURRENCY_NOT_SET]
      ]);

      return true;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError() {
        return $this->errorCode;
    }


    /* Валидации взяты из ServicesValidator, надо бы им всем в одном месте жить */

    /**
     * Проверка типа услуги
     */
    public function checkServiceType($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this),__FUNCTION__,$params['message'],$values);
        }

        if (!ServicesFactory::isServiceTypeExist($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this),__FUNCTION__,$params['message'],$values);
        }

        return true;
    }

    /**
     * Проверка значения параметра валюты
     */
    public function checkCurrency($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this),__FUNCTION__,$params['message'],$values);
        }

        $CurrencyRates = CurrencyRates::getInstance();

        if (!$CurrencyRates->getIdByCode($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this),__FUNCTION__,$params['message'],$values);
        }

        return true;
    }

    /**
    * Проверка даты услуги: после 01.01.2000
    */
    public function checkDate($values, $attribute, $params) {
      if (empty($attribute) || empty($values) || empty($params)) {
          throw new KmpException('',OrdersErrors::INCORRECT_VALIDATION_RULES);
      }

      if (empty($values[$attribute])) {
          throw new KmpInvalidArgumentException(get_class($this),__FUNCTION__,$params['message'],$values);
      }

      $datemark=new DateTime('2000-01-01');
      $chdate=new DateTime($values[$attribute]);

      if (empty($chdate) || $chdate<$datemark) {
          throw new KmpInvalidArgumentException(get_class($this),__FUNCTION__,$params['message'],$values);
      }

      return true;
    }

}
