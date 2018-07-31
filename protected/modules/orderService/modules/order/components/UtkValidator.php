<?php

/**
 * Class UtkValidator
 * Класс для проверки корректности значений поступивших от УТК
 */
class UtkValidator
{
    /**
     * Код ошибки
     * @var int
     */
    private $_errorCode;

    /**
     * namespace для записи логов
     * @var
     */
    private $_namespace;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        $this->_module = $module;
        $this->_namespace = "system.orderservice";
    }

    /**
     * Проверка параметров от УТК для создания/обновления заявки
     * @param $params array входные параметры
     * @return bool|null
     */
    public function checkUTKOrderParams($params)
    {

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;

        if (empty($params['orderId']) && empty($params['orderIdUTK'])) {
            $this->_errorCode = OrdersErrors::ORDER_ID_NOT_SET;
        }

        if (empty($params['orderIdUTK'])) {
            $this->_errorCode = OrdersErrors::UTK_ORDER_ID_NOT_SET;
        }

        if (empty($params['Services']) || count($params['Services']) == 0) {
            $this->_errorCode = OrdersErrors::NO_SERVICES_IN_ORDER;
        }

        if (empty($params['status'])) {
            $this->_errorCode = OrdersErrors::ORDER_STATUS_NOT_SET;
        }

        if (empty($params['clientIdUTK'])) {
            $this->_errorCode = OrdersErrors::AGENT_ID_NOT_SET;
        }

        if (empty($params['agentIdUTK'])) {
            $this->_errorCode = OrdersErrors::AGENCY_USER_ID_NOT_SET;
        }

        if (empty($params['Tourists']) || !$this->checkTourleaderExists($params['Tourists'])) {
            $this->_errorCode = OrdersErrors::TOURLEADER_NOT_FOUND;
        }

        if (empty($params['Services'])) {
            $this->_errorCode = OrdersErrors::NO_SERVICES_IN_ORDER;
        }

        /*if (empty($params['online'])) {
            $this->_errorCode = OrdersErrors::ORDER_ONLINE_TYPE_PARAM_NOT_SET;
        }*/

        if (!isset($params['GPTSorderId']) || empty($params['GPTSorderId']) && $params['GPTSorderId'] != 0) {
            $this->_errorCode = OrdersErrors::ORDER_GPTS_ID_NOT_SET;
        }

        if (empty($params['contractId'])) {
            $this->_errorCode = OrdersErrors::ORDER_AGENCY_UTK_CONTRACT_ID_NOT_SET;
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Проверка входных параметров от УТК при изменении заявки
     * @param $params array входные параметры
     * @return bool|null
     */
    public function checkUTKOrderUpdateParams($params)
    {

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;

        $orderForm = new OrderSearchForm($params);

        try {
            if (!empty($params['orderId'])) {
                $order = $orderForm->getOrderById();
            }

            if (empty($order)) {
                $order = $orderForm->getOrderByUTKId($params['orderIdUTK']);
            }
        } catch (Exception $e) {
            $this->_errorCode = OrdersErrors::DB_ERROR;

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError(OrdersErrors::CANNOT_FIND_ORDER), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        if (empty($order)) {
            $this->_errorCode = OrdersErrors::ORDER_ID_INCORRECT;
        }

        if (!empty($order['OrderID_UTK']) && $order['OrderID_UTK'] !== $params['orderIdUTK']) {
            $this->_errorCode = OrdersErrors::UTK_ORDER_ID_INCORRECT;
        }

        try {
            $services = $orderForm->getOrdersServices($order['OrderID']);
        } catch (Exception $e) {
            $this->_errorCode = OrdersErrors::DB_ERROR;

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError(OrdersErrors::CANNOT_GET_ORDER_SERVICES), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        if (count($services) > 0 && isset($params['Services']) && count($params['Services']) < count($services)) {
            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                OrdersErrors::SERVICES_NOT_MATCH, $params, LogHelper::MESSAGE_TYPE_WARNING,
                $this->_namespace . '.errors');
        }

        return true;
    }

    /**
     * Проверка входных параметров от УТК при создании заявки
     * @param $params array входные параметры
     * @return bool|null
     */
    public function checkUTKOrderCreateParams($params)
    {

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;
        $maxYearsDifference = 30;

        $orderForm = new OrderSearchForm($params);

        try {
            $order = $orderForm->getOrderByUTKId($params['orderIdUTK']);
        } catch (Exception $e) {
            $this->_errorCode = OrdersErrors::DB_ERROR;

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError(OrdersErrors::CANNOT_FIND_ORDER), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        if (!empty($order)) {
            $this->_errorCode = OrdersErrors::ORDER_ALREADY_EXISTS;
        }

        if (empty($params['orderDate'])) {
            $this->_errorCode = OrdersErrors::ORDER_DATE_NOT_SET;
        }

        $orderDate = DateTime::createFromFormat('Y-m-d\TH:i:s', $params['orderDate']);

        if (!$orderDate || $orderDate > new DateTime()
            || $orderDate->diff(new DateTime())->y > $maxYearsDifference
        ) {
            $this->_errorCode = OrdersErrors::ORDER_DATE_INCORRECT;
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;

    }

    /**
     * Проверка на наличие валидных услуг в заявке
     * @param $services array
     * @param $tourists array
     * @return bool
     */
    public function checkValidUtkServicesExists($services, $tourists)
    {


        if (empty($services) || count($services) == 0) {
            $this->_errorCode = OrdersErrors::NO_SERVICES_IN_ORDER;
            return false;
        }

        foreach ($services as $serviceInfo) {
            $this->resetLastError();
            if ($this->checkValidService($serviceInfo, $tourists)) {
                return true;
            }
        }

        $this->_errorCode = OrdersErrors::NO_CORRECT_SERVICES_IN_ORDER;
        return false;
    }

    /**
     * Проверка услуги на валидность
     * @param $serviceInfo array
     * @param $tourists array
     * @return bool
     */
    public function checkValidService($serviceInfo, $tourists)
    {

        if (!$this->checkUtkServiceParams($serviceInfo)) {
            return false;
        }

        if (!$this->serviceHasLinkedTourists($serviceInfo, $tourists)) {
            return false;
        }

        if (!$this->checkServiceHasHandler($serviceInfo)) {
            return false;
        }

        return true;
    }

    /**
     * Валидация параметров услуги
     * @param $serviceInfo
     * @return bool
     */
    public function checkUtkServiceParams($serviceInfo)
    {
        $CurrencyRates = CurrencyRates::getInstance();

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;
        $yearLimit = 2000;

        if (!$CurrencyRates->getIdByCode($serviceInfo['NetCurrency']) ||
            !$CurrencyRates->getIdByCode($serviceInfo['SaleCurrency'])
        ) {
            $this->_errorCode = OrdersErrors::CURRENCY_INCORRECT;
            return false;
        }

        if (empty($serviceInfo['serviceType'])) {
            $this->_errorCode = OrdersErrors::SERVICE_TYPE_NOT_SET;
            return false;
        }

        $offer = OffersFactory::createOffer($serviceInfo['serviceType']);

        if (!$offer) {
            $this->_errorCode = OrdersErrors::UNKNOWN_SERVICE_TYPE;
            return false;
        }

        if (empty($serviceInfo['ServiceDetails']) || count($serviceInfo['ServiceDetails']) == 0) {
            $this->_errorCode = OrdersErrors::SERVICE_DETAILS_NOT_SET;
            return false;
        }

        if (!$offer->checkOfferDetails($serviceInfo['ServiceDetails'])) {
            $this->_errorCode = OrdersErrors::SERVICE_DETAILS_INCORRECT;
            return false;
        }

        if (empty($serviceInfo['NetSum'])
            && $serviceInfo['serviceType'] != ServicesFactory::TOUR_SERVICE_TYPE
        ) {
            $this->_errorCode = OrdersErrors::SERVICE_PRICE_INCORRECT;
            return false;
        }

        if (empty($serviceInfo['SaleSum'])
            && $serviceInfo['serviceType'] != ServicesFactory::PACKET_SERVICE_TYPE
        ) {
            $this->_errorCode = OrdersErrors::SERVICE_PRICE_INCORRECT;
            return false;
        }

        if (empty($serviceInfo['startDateTime'])) {
            $this->_errorCode = OrdersErrors::DATE_START_NOT_SET;
            return false;
        }

        if (empty($serviceInfo['endDateTime'])) {
            $this->_errorCode = OrdersErrors::DATE_END_NOT_SET;
            return false;
        }

        $startDate = DateTime::createFromFormat('Y-m-d\TH:i:s', $serviceInfo['startDateTime']);
        if (!$startDate || $startDate->format('Y') < $yearLimit) {
            $this->_errorCode = OrdersErrors::DATE_START_INCORRECT;
            return false;
        }

        $endDate = DateTime::createFromFormat('Y-m-d\TH:i:s', $serviceInfo['endDateTime']);
        if (!$endDate || $endDate->format('Y') < $yearLimit) {
            $this->_errorCode = OrdersErrors::DATE_END_INCORRECT;
            return false;
        }

        if ($startDate > $endDate) {
            $this->_errorCode = OrdersErrors::DATE_START_INCORRECT;
            return false;
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $serviceInfo,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Валидация агентства заявки
     * @param $clientIdUtk идентифкатор агентства в УТК
     * @param $clientUserIdUtk идентифкатор пользователя агентства в УТК
     * @return bool
     */
    public function checkOrderAgency($clientIdUtk, $clientUserIdUtk, $activeDate = null)
    {

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;

        if (!$this->checkAgencyExists($clientIdUtk, $cxtName, $err)) {
            $this->_errorCode = OrdersErrors::AGENT_NOT_FOUND;
        }

        if (!$this->checkAgencyIsActive($clientIdUtk, $activeDate, $cxtName, $err)) {
            $this->_errorCode = OrdersErrors::AGENT_NOT_ACTIVE;
        }

        if (!$this->checkAgencyUserExists($clientUserIdUtk, $cxtName, $err)) {
            $this->_errorCode = OrdersErrors::AGENCY_USER_NOT_FOUND;
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $clientIdUtk,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Проверка существования заявки в БД
     * @param $params
     * @return bool
     */
    public function checkOrderExists($params)
    {

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;

        $orderForm = new OrderSearchForm($params);

        try {
            if (!empty($params['orderId'])) {
                $order = $orderForm->getOrderById();
            }

            if (empty($order)) {
                $order = $orderForm->getOrderByUTKId($params['orderIdUTK']);
            }
        } catch (Exception $e) {

            $this->_errorCode = OrdersErrors::DB_ERROR;

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError(OrdersErrors::CANNOT_FIND_ORDER), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return !empty($order);
    }

    /**
     * Проверка существования счёта в БД
     * @param $params
     * @return bool
     */
    public function checkInvoiceExists($params)
    {

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;

        try {
            if (!empty($params['invoiceId'])) {
                $invoice = InvoicesForm::getInvoiceById($params['invoiceId']);
            }

            if (empty($invoice)) {
                $invoice = InvoicesForm::getInvoiceByUtkId($params['invoiceIdUTK']);
            }

        } catch (Exception $e) {

            $this->_errorCode = OrdersErrors::DB_ERROR;

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError(OrdersErrors::CANNOT_FIND_INVOICE), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors'
            );

            return false;
        }

        return !empty($invoice);
    }

    /**
     * Проверка входных параметров
     * для создания или изменения счёта из УТК
     * @return bool
     */
    public function checkUTKInvoiceParams($params)
    {

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;

        if (empty($params['orderIdUTK'])) {
            $this->_errorCode = OrdersErrors::UTK_ORDER_ID_NOT_SET;
        }

        if (empty($params['serviceTableShort']) || count($params['serviceTableShort']) == 0) {
            $this->_errorCode = OrdersErrors::NO_LINKED_SERVICES_TO_INVOICE;
        } else {
            $CurrencyRates = CurrencyRates::getInstance();

            foreach ($params['serviceTableShort'] as $service) {
                if (empty($service['currency'])) {
                    $this->_errorCode = OrdersErrors::CURRENCY_NOT_SET;
                    break;
                }

                if (!$CurrencyRates->getIdByCode($service['currency'])) {
                    $this->_errorCode = OrdersErrors::CURRENCY_INCORRECT;
                    break;
                }

                $savedService = null;
                if (!empty($service['serviceId'])) {
                    $savedService = ServicesForm::getServiceById($service['serviceId']);
                }
                if (empty($savedService)) {
                    $savedService = ServicesForm::getServiceByUtkId($service['serviceIdUTK']);
                }

                if (!$savedService) {
                    $this->_errorCode = OrdersErrors::SERVICE_NOT_FOUND;
                    break;
                }

                if (trim($service['serviceIdUTK']) != trim($savedService['ServiceID_UTK'])) {
                    $this->_errorCode = OrdersErrors::SERVICE_UTK_ID_INCORRECT;
                    break;
                }
            }
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Проверка входных параметров
     * для обновления счёта из УТК
     * @param $params
     */
    public function checkUTKInvoiceUpdateParams($params)
    {

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;

        try {
            if (!empty($params['invoiceId'])) {
                $invoice = InvoicesForm::getInvoiceById($params['invoiceId']);
            }

            if (empty($invoice)) {
                $invoice = InvoicesForm::getInvoiceByUtkId($params['invoiceIdUTK']);
            }

        } catch (Exception $e) {

            $this->_errorCode = OrdersErrors::DB_ERROR;

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError(OrdersErrors::CANNOT_FIND_INVOICE), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors'
            );

            return false;
        }

        if (!$invoice) {
            $this->_errorCode = OrdersErrors::INVOICE_NOT_FOUND;
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Проверка входных параметров
     * для создания счёта из УТК
     * @param $params
     */
    public function checkUTKInvoiceCreateParams($params)
    {

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;

        if (InvoicesForm::getInvoiceByUtkId($params['invoiceIdUTK'])) {
            $this->_errorCode = OrdersErrors::INVOICE_ALREADY_EXISTS;
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Проверка входных параметров
     * для создания или изменения оплаты из УТК
     * @param $params array
     */
    public function checkUTKPaymentParams($params)
    {

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;

        if (empty($params['invoiceId']) && empty($params['invoiceIdUTK'])) {
            $this->_errorCode = OrdersErrors::INVOICE_ID_NOT_SET;
            return false;
        }

        if (empty($params['paymentIdUTK'])) {
            $this->_errorCode = OrdersErrors::UTK_PAYMENT_ID_NOT_SET;
            return false;
        }

        if (empty($params['price'])) {
            $this->_errorCode = OrdersErrors::PAYMENT_SUM_NOT_SET;
            return false;
        }

        if (empty($params['currency'])) {
            $this->_errorCode = OrdersErrors::CURRENCY_NOT_SET;
            return false;
        }

        $CurrencyRates = CurrencyRates::getInstance();

        if (!$CurrencyRates->getIdByCode($params['currency'])) {
            $this->_errorCode = OrdersErrors::CURRENCY_INCORRECT;
            return false;
        }

        if (empty($params['invoiceId'])) {
            $invoice = InvoicesForm::getInvoiceByUtkId($params['invoiceIdUTK']);
        } else {
            $invoice = InvoicesForm::getInvoiceById($params['invoiceId']);
        }

        if (empty($invoice)) {
            $this->_errorCode = OrdersErrors::INVOICE_NOT_FOUND;
            return false;
        }

        if (empty($params['paymentType']) ||
            !TypesMapperHelper::getKtByUTKType(
                $params['paymentType'],
                TypesMapperHelper::TYPE_PAYMENT,
                $this->_namespace
            )
        ) {
            $this->_errorCode = OrdersErrors::INCORRECT_PAYMENT_TYPE;
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        /*if ($invoice['InvoiceAmount'] != $params['price']) {
            $this->_errorCode = OrdersErrors::PAYMENT_SUM_NOT_MATCH_INVOICE_SUM;
            return false;
        }*/

        /*if ($invoice['CurrencyID'] != CurrencyForm
                ::getCurrencyIdByCode($params['currency'])) {
            $this->_errorCode = OrdersErrors::PAYMENT_CURRENCY_NOT_MATCH_INVOICE_CURRENCY;
            return false;
        }*/

        $paymentForm = new PaymentsForm();

        if (!empty($params['paymentId'])) {
            $payment = $paymentForm->getPaymentById($params['paymentId']);
        }

        if (empty($payment)) {
            $payment = $paymentForm->getPaymentByUtkId($params['paymentIdUTK']);
        }

        if (!empty($payment['PaymentID'])) {
            $params['paymentId'] = $payment['PaymentID'];
            return $this->checkUTKPaymentUpdateParams($params);
        } else {
            return $this->checkUTKPaymentCreateParams($params);
        }

        return true;
    }

    /**
     * Проверка входных параметров для создания оплаты
     * @param $params
     * @return bool
     */
    public function checkUTKPaymentCreateParams($params)
    {

        if (PaymentsForm::getPaymentByUtkId($params['paymentIdUTK'])) {
            $this->_errorCode = OrdersErrors::PAYMENT_ALREADY_EXISTS;
            return false;
        }

        if (PaymentsForm::getPaymentByInvoiceId($params['invoiceId'])) {
            $this->_errorCode = OrdersErrors::INVOICE_PAYMENT_ALREADY_EXISTS;
            return false;
        }

        return true;
    }

    /**
     * Проверка входных параметров для обновления данных оплаты
     * @param $params
     * @return bool
     */
    public function checkUTKPaymentUpdateParams($params)
    {

        if (!PaymentsForm::getPaymentById($params['paymentId'])) {
            $this->_errorCode = OrdersErrors::PAYMENT_NOT_FOUND;
            return false;
        }

        return true;
    }

    /**
     * Проверка параметров для создания счёта
     * @param $params
     * @return bool
     */
    public function checkSetInvoiceParams($params)
    {

        if (empty($params['orderId'])) {
            $this->_errorCode = OrdersErrors::ORDER_ID_NOT_SET;
            return false;
        }

        if (empty($params['currency'])) {
            $this->_errorCode = OrdersErrors::CURRENCY_INCORRECT;
            return false;
        }

        if (empty($params['Services'])) {
            $this->_errorCode = OrdersErrors::NO_SERVICES_IN_INVOICE;
            return false;
        }

        foreach ($params['Services'] as $service) {

            if (empty($service['serviceId'])) {
                $this->_errorCode = OrdersErrors::SERVICE_ID_NOT_SET;
                return false;
            }

            if (empty($service['invoicePrice'])) {
                $this->_errorCode = OrdersErrors::SERVICE_PRICE_NOT_SET;
                return false;
            }

            if (!is_numeric($service['invoicePrice']) || floatval($service['invoicePrice']) < 0) {
                $this->_errorCode = OrdersErrors::SERVICE_PRICE_INCORRECT;
                return false;
            }
        }

        return true;

    }

    /**
     * Проверка предусловий для создания счёта
     * @param $orderId ид счёта
     */
    public function checkSetInvoiceConditions($orderId)
    {

        $orderSearchForm = new OrderSearchForm(['orderId' => $orderId]);

        $services = $orderSearchForm->getOrdersServices($orderSearchForm->orderId);
        $tourists = [];

        $confirmedExist = false;
        foreach ($services as $service) {

            if (
                $service['status'] == ServicesForm::SERVICE_STATUS_BOOKED
                || $service['status'] == ServicesForm::SERVICE_STATUS_W_PAID
                || $service['status'] == ServicesForm::SERVICE_STATUS_P_PAID
                //  || $service['status'] == ServicesForm::SERVICE_STATUS_P_PAID
            ) {
                $confirmedExist = true;
            }

            $tourists = CMap::mergeArray($tourists, TouristForm::getServiceTourists($service['serviceID']));
        }

        if (!$confirmedExist) {
            $this->_errorCode = OrdersErrors::NO_CONFIRMED_SERVICES_IN_ORDER;
            return false;
        }

        if (empty($tourists) || count($tourists) == 0) {
            $this->_errorCode = OrdersErrors::NO_TOURISTS_IN_ORDER;
            return false;
        }

        foreach ($tourists as $tourist) {

            $touristBase = new TouristBaseForm();

            $touristBase->setParamsMapping([
                'TouristIDbase' => 'touristBaseId',
                'TouristID_UTK' => 'touristUtkId',
                'Name' => 'name',
                'MiddleName' => 'middleName',
                'Surname' => 'surname',
                'Citizenship' => 'citizenship',
                'MaleFemale' => 'sex',
                'Birthdate' => 'birthDate',
                'Email' => 'email',
                'Phone' => 'phone',
            ]);

            $touristBase->setAttributes($tourist);

            if (!$touristBase->validate()) {

                $this->_errorCode = OrdersErrors::TOURIST_HAS_EMPTY_REQUIRED_FIELDS;
                return false;
            }
//todo реализовать проверку необходимых полей документа туриста когда эти данные будут приходить
            //Проверка на наличие данных о документе документа
            if (empty($tourist['TouristIDdoc'])) {
                $this->_errorCode = OrdersErrors::TOURIST_HAS_EMPTY_DOCUMENT_REQUIRED_FIELDS;
                return false;
            }
            return true;
        }

    }

    /**
     * Проверка наличия агентства в БД КТ
     * @param $agencyIdUtk ид агентства в УТК
     * @param $cxtName наименование контекста
     * @param $err объект менеджер ошибок
     * @return bool
     */
    public function checkAgencyExists($agencyIdUtk, $cxtName, $err)
    {

        $agentForm = new AgentForm($this->_namespace);

        try {
            $agent = $agentForm->getAgencyByUtkID($agencyIdUtk);
        } catch (Exception $e) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError(OrdersErrors::CANNOT_GET_AGENCY), $agencyIdUtk,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        if (empty($agent)) {
            return false;
        }

        return true;
    }

    /**
     * Проверка наличия менеджера агентства
     * @param $agencyIdUtk ид агентства в УТК
     * @param $cxtName наименование контекста
     * @param $err объект менеджер ошибок
     * @return bool
     */
    public function checkAgencyUserExists($agencyUserIdUtk, $cxtName, $err)
    {

        $agentForm = new AgentForm($this->_namespace);

        try {
            $agencyUser = $agentForm->getAgentUserIdByUtkId($agencyUserIdUtk);
        } catch (Exception $e) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError(OrdersErrors::CANNOT_GET_AGENCY_MANAGER), $agencyUserIdUtk,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        if (empty($agencyUser)) {
            return false;
        }

        return true;
    }

    /**
     * Проверка существования действующего агентского договора с агентством
     * @param $agencyIdUtk ид агентства в УТК
     * @param $cxtName наименование контекста
     * @param $err объект менеджер ошибок
     * @return bool
     */
    public function checkAgencyIsActive($agencyIdUtk, $activeDate, $cxtName, $err)
    {

        $agentForm = new AgentForm($this->_namespace);

        if ($activeDate == null) {
            $activeDate = time();
        }

        try {
            $agent = $agentForm->getAgencyByUtkID($agencyIdUtk);
        } catch (Exception $e) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError(OrdersErrors::CANNOT_GET_AGENCY) . PHP_EOL . $e->getMessage(),
                $agencyIdUtk, LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        if (!$agentForm->isActiveAgent($agent, $activeDate)) {
            return false;
        }

        return true;
    }

    /**
     * Проверка на наличие туриста с признаком турлидера в массиве
     * @param $tourists array
     * @return bool
     */
    public function checkTourleaderExists($tourists)
    {

        $isTourleader = 1;

        if (empty($tourists) || count($tourists) == 0) {
            return false;
        }

        foreach ($tourists as $tourist) {

            if (intval($tourist['isTourLead']) == $isTourleader) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверка каждого туриста на наличие связи с услугой в заявке
     * @param $tourists array
     * @param $services array
     * @return bool
     */
    public function checkTouristsLinkedToServices($tourists, $services)
    {

        $isLinked = false;
        if (empty($tourists) || count($tourists) == 0 || empty($services) || count($services) == 0) {
            return false;
        }

        foreach ($tourists as $tourist) {
            $isLinked = false;

            foreach ($services as $service) {
                if ($tourist['isTourLead'] || $this->touristIsLinkedToService($service, $tourist)) {
                    $isLinked = true;
                    break;
                }
            }

            if (!$isLinked) {
                return false;
            }
        }

        return true;
    }

    /**
     * Проверка существования туристов привязанных к услуге
     * @param $service array
     * @param $tourists array
     * @return bool
     */
    public function serviceHasLinkedTourists($service, $tourists)
    {

        $cxtName = $this->_module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->_module;

        if (empty($tourists) || count($tourists) == 0 || empty($service)) {
            $this->_errorCode = OrdersErrors::NO_LINKED_TOURISTS_TO_SERVICES;
            return false;
        }

        foreach ($tourists as $tourist) {
            if ($this->touristIsLinkedToService($service, $tourist)) {
                return true;
            }
        }

        $this->_errorCode = OrdersErrors::NO_LINKED_TOURISTS_TO_SERVICES;

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $service,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }
    }

    /**
     * Проверка связи одного туриста и одной услуги
     * @param $service
     * @param $tourist
     * @return bool
     */
    public function touristIsLinkedToService($service, $tourist)
    {

        if (empty($service['serviceIdUTK']) || empty($tourist['serviceIdUTK'])) {
            return false;
        }

        return $tourist['serviceIdUTK'] == $service['serviceIdUTK'];
    }

    /**
     * Проверка на наличие обработчика(класса) для типа услуги
     * @param $services array данные услуги
     * @return bool
     */
    public function checkServiceHasHandler($service)
    {

        if (!ServicesFactory::isServiceTypeExist($service['serviceType'])) {
            $this->_errorCode = OrdersErrors::UNKNOWN_SERVICE_TYPE;
            return false;
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

    /**
     * Сброс кода последней ошибки
     */
    public function resetLastError()
    {
        $this->_errorCode = 0;
    }

}
