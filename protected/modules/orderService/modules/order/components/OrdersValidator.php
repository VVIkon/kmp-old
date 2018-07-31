<?php

/**
 * Class OrdersValidator
 * Класс для проверки корректности значений при работе с данными заявки
 */
class OrdersValidator extends Validator
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
     * Проверка параметров от УТК
     * @param $params array входные параметры
     * @return bool|null
     */
    public function checkSetDiscountParams($params)
    {

        if (empty($params['orderId'])) {
            $this->_errorCode = OrdersErrors::ORDER_ID_NOT_SET;
            return false;
        }

        if (empty($params['agentOrderDiscount'])) {
            $this->_errorCode = OrdersErrors::ORDER_AGENCY_DISCOUNT_NOT_SET;
            return false;
        }
        return true;
    }

    /**
     * Проверка параметров получения туристов заявки
     * @param $params array
     * @return bool
     */
    public function checkGetOrderTourists($params)
    {

        $cxtName = $this->module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->module;

        if (empty($params['orderId'])) {
            $this->_errorCode = OrdersErrors::ORDER_ID_NOT_SET;
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Проверка параметров запроса на получение найденных предложений
     * @param $params
     */
    public function checkAddServiceCommonParams($params)
    {
        $this->validateComplex($params, [
            ['serviceType', 'required', 'message' => OrdersErrors::SERVICE_TYPE_NOT_SET],
            ['serviceType', 'checkServiceType', 'message' => OrdersErrors::UNKNOWN_SERVICE_TYPE],
        ]);

        return true;
    }

    /**
     * Проверка параметров для запуска процесса бронирования предложения
     * @param $params
     */
    public function checkBookStartCommonParams($params)
    {
        $this->validateComplex($params, [
            ['agreementSet', 'required', 'message' => OrdersErrors::OFFERS_AND_RATES_AGREEMENT_NOT_CONFIRMED],
            ['agreementSet', 'booleantrue', 'message' => OrdersErrors::OFFERS_AND_RATES_AGREEMENT_NOT_CONFIRMED],
            ['orderId', 'required', 'message' => OrdersErrors::ORDER_ID_NOT_SET],
            ['orderId', 'checkOrderStatusToStartBooking', 'message' => OrdersErrors::OFFERS_AND_RATES_AGREEMENT_NOT_CONFIRMED]
        ]);

        return true;
    }

    public function checkBookCompleteParams($params)
    {
        $this->validateComplex($params, [
            ['orderId', 'required', 'message' => OrdersErrors::ORDER_ID_NOT_SET],
            ['bookResult', 'required', 'message' => OrdersErrors::BOOK_RESULT_NOT_SET],
            ['bookResult', 'checkBookResultValid', 'message' => OrdersErrors::INCORRECT_BOOK_RESULT],
            ['bookData', 'included', 'message' => OrdersErrors::BOOK_DATA_NOT_SET],
            ['bookData', 'checkBookDataParams', 'message' => OrdersErrors::INCORRECT_BOOK_DATA_STRUCTURE],
            ['serviceId', 'required', 'message' => OrdersErrors::SERVICE_ID_NOT_SET],
            ['serviceId', 'checkServiceExists', 'message' => OrdersErrors::SERVICE_NOT_FOUND]
        ]);

        return true;
    }

    /**
     * Проверка общих параметров запроса на создание пустой заявки
     * @param $params
     * @return bool
     */
    public function checkNewOrderCommonParams($params)
    {
        $this->validateComplex($params, [
            ['agentId', 'required', 'message' => OrdersErrors::AGENT_ID_NOT_SET],
            ['agentId', 'checkAgencyExists', 'message' => OrdersErrors::AGENT_NOT_FOUND],
            ['contractId', 'required', 'message' => OrdersErrors::AGENCY_CONTRACT_NOT_SET],
            ['contractId', 'checkAgencyContractExist', 'message' => OrdersErrors::AGENCY_CONTRACT_NOT_FOUND],
            ['companyManagerId', 'required', 'message' => OrdersErrors::AGENCY_USER_ID_NOT_SET],
            ['companyManagerId', 'checkAgencyUserExist', 'message' => OrdersErrors::AGENCY_USER_NOT_FOUND],
            ['kmpManagerId', 'required', 'message' => OrdersErrors::KMP_MANAGER_NOT_SET],
            ['kmpManagerId', 'checkKmpManagerExist', 'message' => OrdersErrors::KMP_MANAGER_NOT_FOUND]
        ]);

        return true;
    }

    /**
     * Проверка общих параметров запроса на получение услуги и предложений заявки
     * @param $params
     * @return bool
     */
    public function checkGetOrderOffersParams($params)
    {
        $this->validateComplex($params, [
            ['servicesIds', 'required', 'message' => OrdersErrors::SERVICE_ID_NOT_SET],
            ['servicesIds', 'checkServicesExists', 'message' => OrdersErrors::SERVICE_NOT_FOUND],
            ['servicesIds', 'checkServicesInOrder', 'message' => OrdersErrors::SERVICES_IDS_FROM_DIFFERENT_ORDERS],
            ['lang', 'required', 'message' => OrdersErrors::LANGUAGE_NOT_SET],
            ['lang', 'checkLang', 'message' => OrdersErrors::INCORRECT_LANGUAGE],
            ['getInCurrency', 'required', 'message' => OrdersErrors::CURRENCY_NOT_SET],
            ['getInCurrency', 'checkCurrency', 'message' => OrdersErrors::CURRENCY_INCORRECT],
        ]);
    }

    /**
     * Проверка параметров команды для выставления счёта
     * @param $params array
     */
    public function checkPayStartParams($params)
    {
        $this->validateComplex($params, [
            ['orderId', 'required', 'message' => OrdersErrors::ORDER_ID_NOT_SET],
            [
                'orderId', 'checkOrderStatusToSetInvoice',
                'message' => OrdersErrors::ORDER_STATUS_IS_BLOCKING_SET_INVOICE
            ],
            ['invoiceId', 'required', 'message' => OrdersErrors::INVOICE_ID_NOT_SET]
        ]);
    }

    /**
     * Проверка параметров команды оплаты счёта
     * @param $params array
     */
    public function checkPayFinishParams($params)
    {
        $this->validateComplex($params, [
            ['orderId', 'required', 'message' => OrdersErrors::ORDER_ID_NOT_SET],
            [
                'orderId', 'checkOrderStatusToSetPayment',
                'message' => OrdersErrors::ORDER_STATUS_IS_BLOCKING_SET_PAYMENT
            ],
            ['services', 'required', 'message' => OrdersErrors::SERVICE_ID_NOT_SET],
            ['services', 'checkInvoiceServicesStruct', 'message' => OrdersErrors::SERVICE_DETAILS_INCORRECT]
        ]);
    }

    /**
     * Проверка параметров команды инициирования выписки билетов
     * @param $params
     */
    public function checkIssueTicketsParams($params)
    {
        $this->validateComplex($params, [
            ['orderId', 'required', 'message' => OrdersErrors::ORDER_ID_NOT_SET],
            [
                'orderId', 'checkOrderStatusToIssueTickets',
                'message' => OrdersErrors::ORDER_STATUS_IS_BLOCKING_ISSUE_TICKETS
            ],
            ['serviceId', 'required', 'message' => OrdersErrors::SERVICE_ID_NOT_SET],
        ]);
        return true;
    }

    /**
     * Проверка параметров команды завершения обработки заявки
     * @param $params
     */
    public function checkDoneParams($params)
    {
        $this->validateComplex($params, [
            ['orderId', 'required', 'message' => OrdersErrors::ORDER_ID_NOT_SET],
            [
                'orderId', 'checkOrderStatusToDone',
                'message' => OrdersErrors::ORDER_STATUS_IS_BLOCKING_ISSUE_TICKETS
            ],
        ]);

        return true;
    }

    /**
     * Проверка существования услуг с указаннымы идентификаторами
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkServicesExists($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (is_array($values) && count($values)) {
            foreach ($values[$attribute] as $serviceId) {
                $service = ServicesForm::getServiceById($serviceId);

                if (empty($service)) {
                    throw new KmpInvalidArgumentException(
                        get_class($this),
                        __FUNCTION__,
                        $params['message'],
                        ['serviceId' => $serviceId]
                    );
                }
            }
            return true;
        } else {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }
    }

    /**
     * Проверка корректности значения результата бронирования
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkBookResultValid($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (!BookType::checkTypeExists($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка параметров бронирования
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkBookDataParams($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }


        if ($values['bookResult'] == BookType::BOOK_RESULT_NOT_BOOKED) {
            return true;
        }

        $this->validateComplex($values[$attribute], [
            ['segments', 'included', 'message' => OrdersErrors::SEGMENTS_NOT_SET],
            ['pnrData', 'included', 'message' => OrdersErrors::PNR_DATA_NOT_SET]
        ]);

        if (!empty($values[$attribute])) {
            $this->validateComplex($values[$attribute], [
                ['pnrData', 'checkPnrStructure', 'message' => OrdersErrors::INCORRECT_PNR_STRUCTURE],
            ]);
        }

        return true;
    }

    /**
     * Проверка структуры Passenger Name Record(PNR)
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkPnrStructure($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        $this->validateComplex($values[$attribute], [
            ['engine', 'checkEngineStructure', 'message' => OrdersErrors::SUPPLIER_ENGINE_NOT_SET],
            ['supplierCode', 'required', 'message' => OrdersErrors::SUPPLIER_CODE_NOT_SET],
            ['PNR', 'required', 'message' => OrdersErrors::PNR_NOT_SET],
        ]);

        return true;
    }

    /**
     * Проверка параметров шлюза поставщика
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkEngineStructure($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        $this->validateComplex($values[$attribute], [
            ['type', 'required', 'message' => OrdersErrors::INCORRECT_ENGINE_STRUCTURE],
            ['GPTS_service_ref', 'required', 'message' => OrdersErrors::INCORRECT_ENGINE_STRUCTURE],
            ['GPTS_order_ref', 'required', 'message' => OrdersErrors::INCORRECT_ENGINE_STRUCTURE],
        ]);

        return true;
    }

    /**
     * Проверка существования указанной услуги
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkServiceExists($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        $service = ServicesForm::getServiceById($values[$attribute]);

        if (empty($service)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                $params['message'],
                ['serviceId' => $values[$attribute]]
            );
        }

        return true;
    }

    /**
     * Проверка корректности кода языка
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkLang($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (!LangForm::GetLanguageCodeByName($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка корректности кода валюты
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkCurrency($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        $CurrencyRates = CurrencyRates::getInstance();

        if (!$CurrencyRates->getIdByCode($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка существования указанных услуг в заявке
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkServicesInOrder($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        $serviceId = $values[$attribute][0];

        $orderInfo = OrderForm::getOrderByServiceId($serviceId);

        if (empty($orderInfo)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                $params['message'],
                ['serviceId' => $serviceId]
            );
        }

        $orderSearchForm = OrderSearchForm::createInstance();
        $orderServicesInfo = $orderSearchForm->getOrdersServices($orderInfo['OrderID']);

        foreach ($values[$attribute] as $serviceId) {

            $serviceInOrder = false;
            foreach ($orderServicesInfo as $serviceInfo) {
                if ($serviceInfo['serviceID'] == $serviceId) {
                    $serviceInOrder = true;
                }
            }

            if (!$serviceInOrder) {
                throw new KmpInvalidArgumentException(
                    get_class($this),
                    __FUNCTION__,
                    $params['message'],
                    ['serviceId' => $serviceId]
                );
            }

        }
        return true;
    }

    /**
     * Проверка статуса заявки для выполнения операции бронирования
     * @param $orderId
     * @return bool
     */
    public function checkOrderStatusToStartBooking($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($values[$attribute]);

        if (empty($orderInfo)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_INCORRECT,
                ['orderId' => $values[$attribute]]
            );
        }

        if (
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_NEW &&
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_W_PAID &&
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_MANUAL
        ) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_STATUS_IS_BLOCKING_BOOKING,
                [
                    'orderId' => $orderInfo['OrderID'],
                    'orderStatus' => $orderInfo['Status']
                ]
            );
        }

        return true;
    }


    /**
     * Проверка статуса заявки для добавления услуги
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkOrderStatusToAddService($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (empty($values[$attribute])) {
            return true;
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($values[$attribute]);

        if (empty($orderInfo)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_INCORRECT,
                $values
            );
        }

        if (
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_NEW &&
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_BOOKED &&
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_W_PAID &&
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_PAID &&
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_ANNULED
        ) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка статуса заявки для выставления счёта
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkOrderStatusToSetInvoice($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_NOT_SET,
                $values);
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($values[$attribute]);

        if (empty($orderInfo)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_INCORRECT,
                $values
            );
        }

        $orderStatusesToSetInvoice = [
            OrderForm::ORDER_STATUS_NEW,
            OrderForm::ORDER_STATUS_BOOKED,
            OrderForm::ORDER_STATUS_W_PAID,
            OrderForm::ORDER_STATUS_DONE,
            OrderForm::ORDER_STATUS_MANUAL
        ];

        $orderStatusesToSetInvoiceKMPUser = [
            OrderForm::ORDER_STATUS_DONE,
            OrderForm::ORDER_STATUS_MANUAL
        ];

        // просто статусы
        if (in_array($orderInfo['Status'], $orderStatusesToSetInvoice)) {
            if (in_array($orderInfo['Status'], $orderStatusesToSetInvoiceKMPUser)) {
                $userProfile = Yii::app()->user->getState('userProfile');

                if ($userProfile['userType'] != 1) {
                    throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
                }
            }
        } else {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка статуса заявки для установки оплаты
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkOrderStatusToSetPayment($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_NOT_SET,
                $values);
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($values[$attribute]);

        if (empty($orderInfo)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_INCORRECT,
                $values
            );
        }

        if (
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_W_PAID
        ) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка статуса заявки для инициирования операции выписки билета
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkOrderStatusToIssueTickets($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_NOT_SET,
                $values);
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($values[$attribute]);

        if (empty($orderInfo)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_INCORRECT,
                $values
            );
        }

        if (
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_W_PAID &&
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_PAID &&
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_MANUAL &&
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_BOOKED &&
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_NEW
        ) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка статуса заявки для завершения обработки заявки
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkOrderStatusToDone($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_NOT_SET,
                $values);
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($values[$attribute]);

        if (empty($orderInfo)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_INCORRECT,
                $values
            );
        }

        if (
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_W_PAID &&
            $orderInfo['Status'] != OrderForm::ORDER_STATUS_PAID
        ) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка наличия турлидера в заявке
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkOrderHasTourLeader($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_NOT_SET,
                $values);
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($values[$attribute]);

        if (empty($orderInfo)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_ID_INCORRECT,
                $values
            );
        }

        $tourists = TouristForm::getTouristsByOrderId($values[$attribute]);

        foreach ($tourists as $tourist) {
            if ($tourist['TourLeader'] == 1) {
                return true;
            }
        }

        throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
    }

    /**
     * Проверка наличия неоформленных услуг (виза, страховка и т.д) у туристов в заявке
     * @param $orderId int идентификатор заявки
     * @return bool
     */
    public function checkNotIssuedAdditionalServices($orderId)
    {
        $touristsInfo = TouristForm::getTouristsByOrderId($orderId);

        foreach ($touristsInfo as $touristInfo) {
            $tourist = new TouristForm($this->namespace);
            $tourist->loadTouristByID($touristInfo['TouristID']);
            if ($tourist->touristDocMapper->needToIssueService()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверка типа услуги
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkServiceType($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute])) {
            return false;
        }

        if (!ServicesFactory::isServiceTypeExist($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка сущестования агентства
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkAgencyExists($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        $agencyForm = new AgentForm($this->namespace);
        if (empty($values[$attribute]) || !$agencyForm->getAgencyByID($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;

    }

    /**
     * Проверка существования договора агентства
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkAgencyContractExist($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        $agencyContractForm = new AgencyContractForm($this->namespace);
        if (empty($values[$attribute]) || !$agencyContractForm->getContractById($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }


    /**
     * Проверка сущестования менеджера агентства
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkAgencyUserExist($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        $agencyUserForm = new AgencyUserForm($this->namespace);

        if (empty($values[$attribute]) || !$agencyUserForm->getUserById($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка структуры массива данных об услуге в счёте
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkInvoiceServicesStruct($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        foreach ($values[$attribute] as $serviceInfo) {

            if (!isset($serviceInfo['serviceId']) || !isset($serviceInfo['servicePaid'])) {
                throw new KmpInvalidArgumentException(
                    get_class($this),
                    __FUNCTION__,
                    $params['message'],
                    $values);
            }

            if ($this->checkServiceExists(
                $serviceInfo,
                'serviceId',
                ['message' => OrdersErrors::SERVICE_NOT_FOUND])
            ) {

            }
        }

        return true;
    }

    /**
     * Проверка существования договора агентства
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkKmpManagerExist($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        $responsibleMgrForm = new ResponsibleManager();
        if (empty($values[$attribute]) || !$responsibleMgrForm->loadFromDb($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
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
