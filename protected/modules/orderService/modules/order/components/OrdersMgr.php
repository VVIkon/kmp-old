<?php

class OrdersMgr
{
    /**
     * Ссылка на объект модуля
     * @var object
     */
    private $module;

    /**
     * Код ошибки
     * @var int
     */
    private $errorCode;
    /**
     * namespace для записи логов
     * @var string
     */
    private $namespace;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        $this->namespace = "system.orderservice";
        $this->module = $module;
    }

    /**
     * Обёртка для получения списка заявок в развёрнутом виде
     * @param $params array фильтры
     * @return array заявки
     */
    public function getOrdersLong($params)
    {
        return $this->getOrders($params);
    }

    /**
     * Обёртка для получения списка заявок в сокращённом виде
     * @param $params array фильтры
     * @return array заявки
     */
    public function getOrdersShort($params)
    {

        $result = $this->getOrders($params);

        $shortFormOrdersInfo = [];

        foreach ($result['orders'] as $key => $order) {

            $servicesTypes = [];

            foreach ($order['services'] as $service) {
                $servicesTypes[] = $service['serviceType'];
            }

            $shortFormOrdersInfo['o'][] = array(
                'orderNumber' => $order['orderNumber'],
                'st' => $order['status'],
                'dts' => $order['startdate'],
                'dte' => $order['enddate'],
                'cnt' => $order['country'],
                'cty' => $order['city'],
                'srst' => $servicesTypes,
                'mngName' => $order['mgrLastName'],
                'arh' => $order['archive'],
            );
        }

        return array('nums' => $result['nums'], 'o' => $shortFormOrdersInfo['o']);

    }

    /**
     * v.ikon
     * // Проверка рав ( PERMISSION_40 OR PERMISSION_41 OR PERMISSION_42 )
     * @param
     * @returm
     * KT-2550
     */
    private function checkPermitions($order)
    {

        $userProfile = Yii::app()->user->getState('userProfile');
        if ($userProfile['userId'] == $order['userId']) { // Если создатель заявки
            return true;
        } elseif ($userProfile['companyID'] == $order['agentId']) { // Если заявка создана в компании пользователя
            $permissionsToCheck[] = 41;
        } elseif ($userProfile['userType'] == 3) { // Если заявка холдинга
            $permissionsToCheck[] = 42;
        } else { // Если заявка чужая, то лучше проверить 40 бит (со слов Андрея): нет смысла проверять 42 !?!
            $permissionsToCheck[] = 40;
        }
        if (!UserAccess::hasPermissions($permissionsToCheck)) {
            LogHelper::logExt(get_class($this), __METHOD__,
                'Проверка прав пользователя по заявке',
                OrdersErrors::NOT_ENOUGH_USER_RIGHTS,
                ['userProfile' => $userProfile, 'permissionsToCheck' => $permissionsToCheck],
                'error',
                'system.orderservice.error');
            return false;
        }
        return true;
    }

    /**
     * Получение списка заявок
     * @param $params array фильтры
     * @return array заявки
     */
    private function getOrders($params)
    {
        $orderForm = new OrderSearchForm($params);

        if (count($orderForm->errors) > 0) {
            if (count($orderForm->errors) > 1) {
                $this->errorCode = OrdersErrors::INCORRECT_INPUT_PARAM;
            }

            if (array_key_exists('sortFields', $orderForm->errors)) {
                $this->errorCode = OrdersErrors::SORT_ORDER_PARAM_INVALID;
            } else {
                $this->errorCode = OrdersErrors::INCORRECT_INPUT_PARAM;
            }

            return null;
        }

        try {
            $orders = $orderForm->getOrders();
        } catch (Exception $e) {
            $this->errorCode = OrdersErrors::DB_ERROR;
            return null;
        }

        if (count($orders) == 0) {
            return (array('nums' => 0, 'orders' => array()));
        }

        $orderCount = $orders[0]['ordersCount'];

        $ordersIDs = [];
        foreach ($orders as $key => $order) {
            $ordersIDs[] = $order['orderId'];
            $orders[$key]['archive'] = ($orders[$key]['archive'] != 1) ? false : true;
            $orders[$key]['vip'] = ($orders[$key]['vip'] != 1) ? false : true;
            unset($orders[$key]['ordersCount']);
        }

        try {
            $servicesInfo = $orderForm->getOrdersServices($ordersIDs);
        } catch (Exception $e) {
            $this->errorCode = OrdersErrors::DB_ERROR;
            return null;
        }

        $services = [];
        foreach ($servicesInfo as $serviceInfo) {

//            if ($serviceInfo['serviceType'] == ServicesFactory::PACKET_SERVICE_TYPE) {
//                continue;
//            }

            $service = ServicesFactory::createService($serviceInfo['serviceType']);

            if (!$service) {

                LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                    'Неизвестный serviceType в ' . print_r($serviceInfo, 1), 'trace',
                    $this->namespace . '.errors');

                continue;
            }

            $serviceInfo['offline'] = (empty($serviceInfo['offline'])) ? false : true;

            $service->setParamsMapping([
                'kmpPrice' => 'saleSum',
                'supplierPrice' => 'supplierNetPrice',
                'agencyProfit' => 'commission'
            ]);

            $service->setAttributes($serviceInfo);

            $services[$service->serviceType][] = $service;
        }

        $this->_setServicesExAttributes($services);

        $inCurrency = isset($params['getInCurrency']) ? $params['getInCurrency'] : null;

        $this->_setServicesCosts($services, $inCurrency);

        foreach ($orders as $key => $order) {

            $orders[$key]['services'] = [];
            foreach ($services as $servicesGroup) {

                foreach ($servicesGroup as $service) {

                    if ($order['orderId'] == $service->orderId) {
                        $orders[$key]['services'][] = $service->toArrayLongInfo();
                    }
                }
            }

        }

        return (array('nums' => $orderCount, 'orders' => $orders));
    }

    /**
     * Получить информацию о заявке
     * @param $params array фильтры
     * @return array заявка
     */
    public function getOrder($params)
    {
        if (empty($params['orderId'])) {
            $this->errorCode = OrdersErrors::ORDER_ID_NOT_SET;
            return null;
        }

        $orderForm = new OrderSearchForm($params);

        try {
            $order = $orderForm->getOrder();
        } catch (Exception $e) {
            $this->errorCode = OrdersErrors::DB_ERROR;
            return null;
        }

        if (empty($order) || empty($order['orderId'])) {
            return [];
        }
        // Проверка прав
        if (!$this->checkPermitions($order)) {
            $this->errorCode = OrdersErrors::NOT_ENOUGH_USER_RIGHTS;
            return null;
        }

        $order['clientManager'] = [
            'id' => $order['agencyManagerID'],
            'firstName' => $order['agencyManagerName'],
            'lastName' => $order['agencyManagerSurname'],
            'middleName' => $order['agencyManagerMiddleName'],
        ];

        $order['managerKMP'] = [
            'id' => $order['agencyResponsibleManagerID'],
            'firstName' => $order['agencyResponsibleManagerName'],
            'lastName' => $order['agencyResponsibleManagerSurname'],
            'middleName' => $order['agencyResponsibleManagerMiddleName'],
        ];

        $order['creator'] = [
            'id' => $order['KMPManagerID'],
            'firstName' => $order['KMPManagerName'],
            'lastName' => $order['KMPManagerSurname'],
            'middleName' => $order['KMPManagerMiddleName'],
        ];

        $OrderTourleader = OrderTouristRepository::getTourleaderByOrderId($order['orderId']);

        if (!is_null($OrderTourleader)) {
            $Tourist = $OrderTourleader->getTourist();

            $order['touristFirstName'] = $Tourist->getName();
            $order['touristLastName'] = $Tourist->getSurname();
            $order['liderEmail'] = $Tourist->getEmail();
            $order['liderPhone'] = $Tourist->getPhone();
        }

        unset($order['KMPManagerID']);
        unset($order['KMPManagerSurname']);
        unset($order['KMPManagerName']);
        unset($order['KMPManagerMiddleName']);

        unset($order['agencyManagerID']);
        unset($order['agencyManagerSurname']);
        unset($order['agencyManagerName']);
        unset($order['agencyManagerMiddleName']);

        unset($order['agencyResponsibleManagerID']);
        unset($order['agencyResponsibleManagerName']);
        unset($order['agencyResponsibleManagerSurname']);
        unset($order['agencyResponsibleManagerMiddleName']);

        $order['archive'] = (empty($order['archive'])) ? false : (bool)$order['archive'];
        $order['VIP'] = (empty($order['VIP'])) ? false : true;

        // если пользователь не сотрудник КМП,
        // то уберем с глаз некоторые лишние данные
        $userProfile = Yii::app()->user->getState('userProfile');
        if (isset($userProfile['userType']) && $userProfile['userType'] != 1) {
            unset($order['orderIdGp']);
            unset($order['orderIdUtk']);
        }

        try {
            $servicesInfo = $orderForm->getOrdersServices(array($order['orderId']));
        } catch (Exception $e) {
            $this->errorCode = OrdersErrors::DB_ERROR;
            return null;
        }

        $services = [];

        foreach ($servicesInfo as $serviceInfo) {
//            if ($serviceInfo['serviceType'] == ServicesFactory::PACKET_SERVICE_TYPE) {
//                continue;
//            }
            $service = ServicesFactory::createService($serviceInfo['serviceType']);

            $serviceInfo['offline'] = (empty($serviceInfo['offline'])) ? false : true;

            $service->setParamsMapping([
                'kmpPrice' => 'saleSum',
                'supplierPrice' => 'supplierNetPrice',
                'agencyProfit' => 'commission',
                'restPaymentAmount' => 'RestPaymentAmount',
                'Extra' => 'comment',
            ]);

            $service->setAttributes($serviceInfo);

            $services[$service->serviceType][] = $service;
        }

        $this->_setServicesExAttributes($services);

        if (!isset($params['getInCurrency'])) {
            $this->errorCode = OrdersErrors::INPUT_PARAMS_ERROR;
            return null;
        }

        $Currency = CurrencyStorage::findByString($params['getInCurrency']);

        if (is_null($Currency)) {
            $this->errorCode = OrdersErrors::CURRENCY_INCORRECT;
            return null;
        }

        $this->_setServicesCosts($services, $Currency->getId());

        foreach ($services as $servicesGroup) {
            foreach ($servicesGroup as $service) {
                $serviceDetailInfo = $service->toArrayDetailInfo();

                if (isset($userProfile['userType']) && $userProfile['userType'] != 1) {
                    unset($serviceDetailInfo['requestedNetSum']);
                    unset($serviceDetailInfo['supplierNetPrice']);
                    unset($serviceDetailInfo['localNetSum']);
                }

                // найдем доп поля сервиса
                $orderAdditionalFields = OrderAdditionalFieldRepository::getServiceFieldWithId(OrdersServicesRepository::findById($serviceDetailInfo['serviceID']));

                foreach ($orderAdditionalFields as $orderAdditionalField) {
                    $serviceDetailInfo['additionalData'][] = $orderAdditionalField->toArray();
                }

                $order['services'][] = $serviceDetailInfo;
            }
        }

        return $order;
    }

    /**
     * Получить информацию по услугам и соответствующим предложениям в заявке
     * @param $params array
     * @return array|null
     */
    public function getOrderOffers($params)
    {
        $repsonse = [];

        $orderInfo = OrderForm::getOrderByServiceId($params['servicesIds'][0]);

        $orderSearchForm = OrderSearchForm::createInstance();
        $orderServicesInfo = $orderSearchForm->getOrdersServices($orderInfo['OrderID']);

        foreach ($orderServicesInfo as $orderServiceInfo) {

            if (!in_array($orderServiceInfo['serviceID'], $params['servicesIds'])) {
                continue;
            }

            $serviceInfo = [
                'serviceId' => $orderServiceInfo['serviceID'],
                'serviceType' => $orderServiceInfo['serviceType'],
                'serviceStatus' => $orderServiceInfo['status']
            ];

            $touristsInfo = [];

            $serviceTouristsInfo = TouristForm::getServiceTourists($orderServiceInfo['serviceID']);

            foreach ($serviceTouristsInfo as $serviceTouristInfo) {

                $touristsInfo[] = [
                    'touristId' => $serviceTouristInfo['TouristID'],
                    'attached' => true,
                    'firstName' => $serviceTouristInfo['Name'],
                    'middleName' => $serviceTouristInfo['MiddleName'],
                    'surName' => $serviceTouristInfo['Surname']
                ];
            }

            $serviceInfo['serviceTourists'] = $touristsInfo;

            $offer = OffersFactory::createOffer($serviceInfo['serviceType']);

            $CurrencyRates = CurrencyRates::getInstance();

            if ($serviceInfo['serviceType'] == OffersFactory::FLIGHT_OFFER_TYPE) {
                $offer->load($orderServiceInfo['offerId']);

                $offerLangId = LangForm::GetLanguageCodeByName($params['lang']);
                $offer->setSegmentsMealName($offerLangId);
                $offer->setSegmentsAirportCityName($offerLangId);
                $offer->setSegmentsAirportName($offerLangId);
                $offer->setPriceInCurrency($CurrencyRates->getIdByCode($params['getInCurrency']));

                $serviceInfo['offerInfo'] = $offer->offerData;

                try {
                    $pnr = new ServiceFlPnr();
                    $pnr->loadByOfferId($offer->offerId);
                } catch (KmpDbException $kde) {
                    LogExceptionsHelper::logExceptionEr($kde, $this->module, $this->namespace . '.errors');
                }

                $serviceInfo['offerInfo']['pnr'] = !empty($pnr->pnr) ? ['pnrNumber' => $pnr->pnr] : [];

                $serviceTickets = [];
                $serviceReceipts = [];

                $ticketForm = TicketsFactory::createTicket($serviceInfo['serviceType']);
                try {
                    $tickets = $ticketForm->getTicketsByServiceId($serviceInfo['serviceId']);
                } catch (KmpDbException $kde) {
                    LogExceptionsHelper::logExceptionEr($kde, $this->module, $this->namespace . '.errors');
                    $this->errorCode = $kde->getCode();
                    return false;
                }
                $docMgr = new DocumentsMgr($this->module);

                if (!empty($tickets)) {
                    foreach ($tickets as $ticket) {
                        $attachedDoc = new AttachedDocument();
                        try {
                            $attachedDoc->load($ticket->attachedFormId);
                        } catch (KmpDbException $kde) {
                            LogExceptionsHelper::logExceptionEr($kde, $this->module, $this->namespace . '.errors');
                            $this->errorCode = $kde->getCode();
                            return false;
                        }

                        $ssAviaTicket = new SSAviaTicket($this->module);
                        $ssAviaTicket->init($ticket->getData());
                        $serviceTickets[] = $ssAviaTicket->getView();

                        $ssAviaTicketReceipt = new SSAviaTicketReceipt($this->module);
                        $ssAviaTicketReceipt->init([
                            'ticketNumbers' => $ticket->getReceiptTicketNumbers($ticket->attachedFormId),
                            'serviceId' => $ticket->serviceId,
                            'documentId' => $ticket->attachedFormId,
                            'receiptUrl' => $docMgr->getDocumentUrl($attachedDoc->fileURL)
                        ]);
                        $serviceReceipts[] = $ssAviaTicketReceipt->getView();
                    }
                }
                $serviceInfo['offerInfo']['pnr']['tickets'] = $serviceTickets;
                $serviceInfo['offerInfo']['pnr']['receipts'] = $serviceReceipts;
            } else {
                $serviceInfo['offerInfo'] = ['offerId' => $orderServiceInfo['offerId']];
            }
            //
            $repsonse[] = $serviceInfo;
        }

        return $repsonse;
    }

    /**
     * Проверка прав пользователя на выполнения операций
     * @param $rightsToCheck проверяемые права
     */
    private function checkRights($rightsToCheck, $userToken)
    {
        $apiClient = new ApiClient($this->module);
        $profile = $apiClient->getUserProfileByToken($userToken);

        $rightsValidator = new UserRightsValidator($this->module, $profile);
        $rightsValidator->setCurrentUserProfile($profile);

        foreach ($rightsToCheck as $rightGroup) {
            $groupsRight = false;

            foreach ($rightGroup as $right) {

                switch ($right) {
                    case RightsRegister::RIGHT_ALL_ORDERS_ACCESS :
                        $checkResult = $rightsValidator->checkRight($right, []);
                        break;
                    case RightsRegister::RIGHT_OWN_COMPANY_ORDERS_ACCESS :
                        $checkResult = $rightsValidator->checkRight($right, []);
                        break;
                    case RightsRegister::RIGHT_OTHER_COMPANIES_ORDERS_ACCESS :
                        $checkResult = $rightsValidator->checkRight($right, []);
                        break;
                }

                if ($checkResult == true) {
                    $groupsRight = true;
                    break;
                }
            }

            if ($groupsRight == false) {
                throw new KmpInvalidUserRightsException(
                    get_class($this),
                    __FUNCTION__,
                    OrdersErrors::NOT_ENOUGH_USER_RIGHTS,
                    [
                        'rightId' => print_r($rightGroup, 1),
                        'userId' => !empty(Yii::app()->user->getState('userProfile')['userId'])
                            ? Yii::app()->user->getState('userProfile')['userId']
                            : '',
                        'isGuest' => empty($profile) == 1 ? 'true' : 'false'
                    ]
                );
            }
        }

        return true;
    }

    /**
     * Установка специфических атрибутов для каждого типа услуги
     * @param $services array услуги
     */
    private function _setServicesExAttributes($services)
    {

        foreach ($services as $key => $servicesGroup) {

            foreach ($servicesGroup as $service) {
                $ids[] = $service->serviceID;
            }

            $servicesAttrs = $servicesGroup[0]->getServicesGroupExAttributes($ids);

            foreach ($servicesGroup as $service) {

                foreach ($servicesAttrs as $serviceAttr) {

                    if ($service->serviceID == $serviceAttr['serviceID']) {

                        $service->setExAttributes($serviceAttr);
                    }
                }

            }
        }

    }

    /**
     * Установка стоимости услуг в различных валютах
     * @param $services array услуги
     * @param $requestedCurrencyId int код валюты
     * для вычисления стоимости услуги в запрошенной валюте
     */
    private function _setServicesCosts($services, $requestedCurrencyId)
    {
        $currency = CurrencyStorage::findByString($requestedCurrencyId);

        foreach ($services as $servicesGroup) {
            foreach ($servicesGroup as $service) {
                $service->setServicePrice();
                $service->setServiceLocalSum();
                $service->setServiceDiscount();
                $service->setServiceLocalNetSum();
                $service->setServiceRequestedSum($currency);
                $service->setServiceRequestedNetSum($currency);
//                $service->setServiceSumByContractCurrency(CurrencyStorage::findByString($service->paymentCurrencyCode));
            }
        }
    }

    /**
     * Получить комиссию агентства по заявке
     * @param $orderId
     * @return bool|float|int
     */
    public function getOrderContractCommission($orderId)
    {

        if (empty($orderId)) {
            $this->errorCode = OrdersErrors::ORDER_ID_NOT_SET;
            return false;
        }

        $orderSearchForm = OrderSearchForm::createInstance();
        $orderSearchForm->orderId = $orderId;
        $orderServices = $orderSearchForm->getOrdersServices($orderId);

        $order = $orderSearchForm->getOrder();

        if (empty($orderServices) || count($orderServices) == 0) {
            $this->errorCode = OrdersErrors::NO_SERVICES_IN_ORDER;
            return false;
        }

        $agency = new AgentForm($this->namespace);

        $agencyContractCommission = $agency->getAgencyContractCommission($order['agentId']);
        if (!$agencyContractCommission || $agencyContractCommission == 0) {
            $this->errorCode = OrdersErrors::CANNOT_GET_AGENCY_COMMISSION;
            return false;
        }
        $orderCommissionSum = 0;

        foreach ($orderServices as $orderService) {
            $orderCommissionSum += floatval($orderService['supplierPrice']) / 100 * $agencyContractCommission;
        }

        return $orderCommissionSum;
    }

    /**
     * Установить сумму комиссии для каждой услуги агентства
     * @param $orderId
     * @param $agencyCommissionPercent
     * @return array|bool
     */
    public function setOrderServicesAgencyCommisssion($orderId, $agencyCommissionPercent)
    {

        $services = [];
        $orderSearchForm = OrderSearchForm::createInstance();
        $orderSearchForm->orderId = $orderId;
        $orderServices = $orderSearchForm->getOrdersServices($orderId);

        if (empty($orderServices) || count($orderServices) == 0) {
            $this->errorCode = OrdersErrors::NO_SERVICES_IN_ORDER;
            return false;
        }
        $services['extra'] = ['percent' => $agencyCommissionPercent];
        foreach ($orderServices as $orderService) {

            $commissionSum = round($orderService['supplierPrice'] / 100 * $agencyCommissionPercent, 2);

            $result = ServicesForm::setServiceCommission($commissionSum, $orderService['serviceID']);

            if (!$result) {
                $this->errorCode = OrdersErrors::CANNOT_UPDATE_SERVICE;
                return false;
            }

            $services[] = ['serviceId' => $orderService['serviceID']];
        }

        return $services;
    }

    /**
     * Создать заявку и её вложенные объекты
     * @param $params array параметры создания заявки
     * @return array|bool
     */
    public function processOrderInfo($params)
    {

        $result = [];

        $orderForm = new OrderSearchForm($params);

        if (!empty($params['orderId'])) {
            $existedOrder = $orderForm->getOrderById();
        }

        if (empty($existedOrder)) {
            $existedOrder = $orderForm->getOrderByUTKId($params['orderIdUTK']);
        }

        if (!empty($existedOrder)) {

            $order = $this->_updateOrder($existedOrder['OrderID'], $params);

            $result['orderId'] = $order->orderId;

            if (!empty($params['Services'])) {
                $result['services'] = $this->_processOrderServices($order->orderId, $params);
            } else {
                $result['services'] = [];
            }

            $result['tourists'] = $this->_processOrderTourists($order->orderId, $params);
            return $result;
        } else {
            $order = $this->_createOrder($params);

            $result['orderId'] = $order->orderId;

            if (count($params['Services']) > 0 && count($params['Tourists']) > 0) {

                $result['services'] = $this->_processOrderServices($order->orderId, $params);
                $result['tourists'] = $this->_processOrderTourists($order->orderId, $params);
            }
            return $result;

        }

    }

    /**
     * Обновление данных в существующей заявке
     * @param $params array параметры заявки
     */
    private function _updateOrder($orderId, $params)
    {

        $orderForm = OrderForm::createInstance($this->namespace);

        $orderForm->setParamsMapping([
            'orderIdUTK' => 'orderUtkId',
            'GPTSorderId' => 'orderIdGpts'
        ]);

        $orderForm->setAttributes($params);

        $agentForm = new AgentForm($this->namespace);

        $orderForm->orderId = $orderId;
        $orderForm->agencyId = $agentForm->getAgencyIdByUtkID($params['clientIdUTK']);
        $orderForm->agencyUserId = $agentForm->getAgentUserIdByUtkId($params['agentIdUTK']);
        $orderForm->contractId = $agentForm->getAgencyContractByUtkId($params['contractId']);

        $orderForm->status = StatusesMapperHelper::getKtByUTKStatus(
            $params['status'], StatusesMapperHelper::STATUS_TYPE_ORDER, $this->namespace
        );

        $orderForm->updateOrder();

        return $orderForm;
    }

    /**
     * Создание новой заявки
     * @param $params array параметры заявки
     * @return bool|OrderForm заявка
     */
    private function _createOrder($params)
    {

        $orderForm = OrderForm::createInstance($this->namespace);

        $agentForm = new AgentForm($this->namespace);

        $agencyId = $agentForm->getAgencyIdByUtkID($params['clientIdUTK']);
        $agencyUserId = $agentForm->getAgentUserIdByUtkId($params['agentIdUTK']);
        $agencyContracts = $agentForm->getAgencyContracts($agencyId);

        $orderForm->status = StatusesMapperHelper::getKtByUTKStatus(
            $params['status'], StatusesMapperHelper::STATUS_TYPE_ORDER, $this->namespace
        );

        if (empty($agencyContracts) || !is_array($agencyContracts)) {
            $this->errorCode = OrdersErrors::CANNOT_GET_AGENCY_CONTRACT;
            return false;
        }

        $agencyContractId = $agencyContracts[0]['ContractID'];

        if (empty($agencyContractId)) {
            $this->errorCode = OrdersErrors::CANNOT_GET_AGENCY_CONTRACT;
            return false;
        }

        $orderId = $orderForm
            ->createOrder($params['orderIdUTK'], $params['GPTSorderId'],
                $agencyId, $agencyUserId, $agencyContractId);

        if (empty($orderId)) {
            $this->errorCode = OrdersErrors::CANNOT_CREATE_ORDER;
            return false;
        }

        return $orderForm;
    }

    /**
     * Конвертация иерархического списка услуг где есть вложенные услуги
     * в плоский список с указанием родителя
     * @param $servicesInfo array
     * @return bool | array
     */
    private function _normalizeOrderServices($servicesInfo)
    {
        $addServices = [];
        $services = [];

        if (empty($servicesInfo) || count($servicesInfo) == 0) {
            return false;
        }

        foreach ($servicesInfo as $serviceInfo) {

            if (isset($serviceInfo['ServicesAdd']) && count($serviceInfo['ServicesAdd']) > 0) {

                foreach ($serviceInfo['ServicesAdd'] as $subService) {

                    //В случае если тип услуги пакет, её сохранение не требуется,
                    // но часть данных должна перейти в родительскую услугу
                    if ($subService['serviceType'] == ServicesFactory::PACKET_SERVICE_TYPE
                        && $subService['status'] != UtkService::STATUS_DELETED
                    ) {
                        $serviceInfo['NetPrice'] = $subService['NetPrice'];
                        $serviceInfo['NetSum'] = $subService['NetSum'];
                        continue;
                    }

                    $subService['online'] = $serviceInfo['online'];
                    $subService['countryId'] = $serviceInfo['countryId'];
                    $subService['cityId'] = $serviceInfo['cityId'];
                    $subService['parentServiceUtkId'] = $serviceInfo['serviceIdUTK'];
                    $addServices[] = $subService;
                }

            }

            $services[] = $serviceInfo;
        }

        //Дополнительные услуги должны быть в конце списка услуг,
        //чтобы иметь возможность привязаться к созданым родительским услугам
        $services = array_merge($services, $addServices);

        return $services;
    }

    /**
     * Операция по созданию данных счёта для отправки их в УТК
     * @param $params
     * @return bool
     */
    public function setInvoice($params)
    {
        $invoiceParams = $this->prepareInvoiceParams($params);

        try {
            $invoiceInfo = $this->processInvoiceInfo($invoiceParams);
        } catch (KmpException $ke) {

            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
        }

        if (!$invoiceInfo) {
            $this->errorCode = $this->getLastError();
            return false;
        }

        $invoiceParams['invoiceId'] = $invoiceInfo['invoiceId'];

        return $invoiceParams;
    }

    /**
     * Создание|обновление услуг завки
     * @param $orderId integer номер заявки
     * @param $params array параметры запроса
     * @return array|bool
     */
    protected function _processOrderServices($orderId, $params)
    {

        $result = [];

        $requestServices = $this->_normalizeOrderServices($params['Services']);

        $orderServices = null;

        $orderSearchForm = OrderSearchForm::createInstance();
        $orderServices = $orderSearchForm->getOrdersServices($orderId);

        foreach ($requestServices as $requestService) {

            $action = '';
            if (empty($orderServices) || count($orderServices) == 0) {
                $action = 'create';

            } else {

                foreach ($orderServices as $orderService) {
                    if (trim($orderService['serviceUtkId']) == trim($requestService['serviceIdUTK'])) {
                        $action = 'update';
                        break;
                    }
                }
                $action = empty($action) ? 'create' : $action;
            }

            $functionName = '_' . $action . 'Service';

            $serviceId = $this->$functionName($requestService, $orderId);

            if (!$serviceId) {

                if ($requestService['status'] != UtkService::STATUS_DELETED
                    || $this->errorCode == OrdersErrors::UNKNOWN_SERVICE_TYPE
                ) {
                    $serviceId = 'skipped';
                } else {
                    return false;
                }
            }

            $result[] = ['serviceId' => $serviceId, 'action' => $action];
        }
        return $result;
    }

    /**
     * Создание|изменение туристов в заявке
     * @param $order object OrderForm
     * @param $params
     * @return array|bool
     */
    protected function _processOrderTourists($orderId, $params)
    {

        if (empty($orderId)) {
            $this->errorCode = OrdersErrors::ORDER_ID_NOT_SET;
            return false;
        }

        $orderSearchForm = OrderSearchForm::createInstance();
        $orderServices = $orderSearchForm->getOrdersServices($orderId);

        $servicesIds = [];

        foreach ($orderServices as $orderService) {
            $servicesIds[] = $orderService['serviceID'];
        }

        $result = [];
        foreach ($params['Tourists'] as $touristInfo) {

// todo оптимизировать проверку на существующего туриста
            $orderTourists = TouristForm::getServicesTourists($servicesIds);
//
            if ($touristInfo['isTourLead'] && empty($touristInfo['serviceIdUTK'])) {
                $orderTourists[] = TouristForm::getTouristByUtkidBase($touristInfo['personIdUTK']);
            }

            $action = '';

            if (is_array($orderTourists)) {

                foreach ($orderTourists as $orderTourist) {

                    if ($touristInfo['personIdUTK'] == $orderTourist['TouristID_UTK']) {
                        $action = 'update';
                    }
                }
            }

            $action = empty($action) ? 'create' : $action;
            $functionName = '_' . $action . 'Tourist';

            $touristInfo['orderId'] = $orderId;
            $touristId = $this->$functionName($touristInfo, $orderTourists);

            if (!$touristId) {
                return false;
            }
            $result[] = ['touristid' => $touristId, 'action' => $action];
        }

        return $result;
    }

    /**
     * Создание услуги заявки
     * @param $serviceInfo array параметры услуги
     * @param $orderId int ид заявки
     * @return bool|int ид заявки
     */
    private function _createService($serviceInfo, $orderId)
    {
        $CurrencyRates = CurrencyRates::getInstance();

        $currencyCode = $CurrencyRates->getIdByCode($serviceInfo['NetCurrency']);
        if (empty($currencyCode)) {
            $this->errorCode = OrdersErrors::SUPPLIER_CURRENCY_INCORRECT;
            return false;
        }
        $serviceInfo['NetCurrency'] = $currencyCode;

        $currencyCode = $CurrencyRates->getIdByCode($serviceInfo['SaleCurrency']);
        if (empty($currencyCode)) {
            $this->errorCode = OrdersErrors::CURRENCY_INCORRECT;
            return false;
        }
        $serviceInfo['SaleCurrency'] = $currencyCode;

        $serviceInfo['orderId'] = $orderId;

        if (!empty($serviceInfo['cityId'])) {
            $serviceInfo['cityId'] = CitiesMapperHelper::getCityIdByUtkCityId($serviceInfo['cityId']);
        }

        if (!empty($serviceInfo['countryId'])) {
            $serviceInfo['countryId'] = CountryForm::getCountryIdByUtkCountryId($serviceInfo['countryId']);
        }

        $service = ServicesFactory::createService(
            ServicesFactory::UTK_SERVICE_TYPE
        );

        if (!$service) {

            $this->errorCode = OrdersErrors::CANNOT_CREATE_SERVICE;
            return false;
        }

        $service->setParamsMapping([
            'serviceId' => 'serviceId',
            'serviceIdUTK' => 'serviceIDUtk',
            'supplierIdUTK' => 'supplierIdUtk',
            'GPTSserviceID' => 'serviceGptsId',
            'SalePrice' => 'salePrice',
            'NetPrice' => 'supplierPrice',
            'SaleSum' => 'saleSum',
            'offline' => 'offline',
            'NetSum' => 'supplierPrice',
            'NetCurrency' => 'netCurrency',
            'SaleCurrency' => 'saleCurrency',
            'CommissionSum' => 'commission',
            'Description' => 'description',
            'parentServiceId' => 'parentServiceId',
            'ServiceDetails' => 'serviceDetails'
        ]);

        $serviceInfo['offline'] = ($serviceInfo['online'] == 0 ? 1 : 0);

        if (!empty($serviceInfo['parentServiceUtkId'])) {
            $parentService = ServicesForm::getServiceByUtkId($serviceInfo['parentServiceUtkId']);
            $serviceInfo['parentServiceId'] = (!empty($parentService))
                ? $parentService['ServiceID']
                : 0;
        }

        if (!$service->setAttributes($serviceInfo)) {
            $this->errorCode = OrdersErrors::CANNOT_CREATE_SERVICE;
            return false;
        }

        $service->status = StatusesMapperHelper::getKtByUTKStatus(
            $service->status, StatusesMapperHelper::STATUS_TYPE_SERVICE, $this->namespace
        );

        $offer = OffersFactory::createOffer($service->serviceType);

        if (!$offer) {

            LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                'Неизвестный тип услуги для ' . print_r($service, 1), 'trace',
                $this->namespace . '.errors');
            $this->errorCode = OrdersErrors::UNKNOWN_SERVICE_TYPE;
            return false;
        }

        $offer->setOfferDetails($service->serviceDetails);

        if (!$offer->saveOffer()) {
            $this->errorCode = OrdersErrors::CANNOT_CREATE_OFFER;
            return false;
        }

        $service->setOfferId($offer->getOfferId());

        $service->setServiceName($offer->getOfferName());

        $serviceId = $service->save();

        if (!$serviceId) {
            $this->errorCode = OrdersErrors::CANNOT_CREATE_SERVICE;
            return false;
        }

        return $serviceId;

    }

    /**
     * Создание нового туриста
     * @param $touristInfo array параметры туриста
     * @param $serviceId int ид услуги
     * @return bool|UtkTouristForm объект турист
     */
    private function _createTourist($touristInfo)
    {

        if ((empty($touristInfo) || empty($touristInfo['serviceIdUTK']))
            && !$touristInfo['isTourLead']
        ) {
            $this->errorCode = OrdersErrors::TOURIST_HAS_EMPTY_REQUIRED_FIELDS;
            return false;
        }

        $service = ServicesForm::getServiceByUtkId($touristInfo['serviceIdUTK']);

        if (empty($service)) {
            //Если это турлидер не привязанный к услуге установить нулевой ид услуги
            if (!$touristInfo['isTourLead']) {
                $this->errorCode = OrdersErrors::SERVICE_NOT_FOUND;
                return false;
            } else {
                $service['ServiceID'] = 0;
            }
        }

        $tourist = new TouristForm($this->namespace);
        $touristInfo['serviceId'] = $service['ServiceID'];
//todo передалеть после нормального получения документа туриста
        $touristInfo['document'] = ['docTypeId' => 1];
//
        $tourist->setParamsMapping([
            'serviceId' => 'serviceId',
            'orderId' => 'orderId',
            'personId' => 'touristId',
            'personIdUTK' => 'touristUtkId',
            'firstName' => 'name',
            'middleName' => 'middleName',
            'lastName' => 'surname',
            'sex' => 'sex',
            'dateOfBirth' => 'birthDate',
            'isTourLead' => 'tourLeader',
        ]);

        $tourist->setAttributes($touristInfo);
        $tourist->linkToService($touristInfo['serviceId']);

        if (!$tourist->save()) {
            $this->errorCode = OrdersErrors::CANNOT_CREATE_TOURIST;
            return false;
        }

        return $tourist->touristId;
    }

    /**
     * Обновление данных по туристу
     * @param $touristInfo
     * @param $serviceId
     * @return int
     */
    private function _updateTourist($touristInfo, $orderExistedTourists)
    {

        $cxtName = $this->module->getCxtName(get_class(), __FUNCTION__);
        $err = $this->module;

        if ((empty($touristInfo) || empty($touristInfo['personIdUTK'])
                || empty($touristInfo['serviceIdUTK'])) && !$touristInfo['isTourLead']
        ) {
            $this->errorCode = OrdersErrors::TOURIST_HAS_EMPTY_REQUIRED_FIELDS;
            return false;
        }

        $service = ServicesForm::getServiceByUtkId($touristInfo['serviceIdUTK']);

        $orderTourist = TouristForm::getTouristByUtkId($touristInfo['personIdUTK'], $service['ServiceID']);

        if (empty($orderTourist) && !empty($service)) {

            foreach ($orderExistedTourists as $existedTourist) {

                if ($touristInfo['personIdUTK'] == $existedTourist['TouristID_UTK']) {

                    $orderTourist = TouristForm
                        ::getTouristByUtkidBase($touristInfo['personIdUTK']);
                    break;
                }
            }
        }


        if (empty($orderTourist) && $touristInfo['isTourLead']) {
            $orderTourist = TouristForm::getTouristByUtkidBase($touristInfo['personIdUTK']);
        } else {

            $this->errorCode = OrdersErrors::INCORRECT_TOURIST_UTK_ID;
            LogHelper::logExt(get_class(), __FUNCTION__, $cxtName,
                $err->getError($this->errorCode), $touristInfo,
                LogHelper::MESSAGE_TYPE_ERROR, $this->namespace . '.errors');
        }

        $tourist = new TouristForm($this->namespace);

        $tourist->setParamsMapping([
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
            'ServiceID' => 'serviceId',
            'TouristID' => 'touristId',
            'TouristIDdoc' => 'touristDocId'
        ]);

        $tourist->setAttributes($orderTourist);

        foreach ($touristInfo as $key => $infoItem) {
            if (empty($infoItem)) {
                unset($touristInfo[$key]);
            }
        }

        $tourist->setParamsMapping([
            'serviceId' => 'serviceId',
            'personId' => 'touristId',
            'personIdUTK' => 'touristIdUtk',
            'firstName' => 'name',
            'middleName' => 'middleName',
            'lastName' => 'surname',
            'sex' => 'sex',
            'dateOfBirth' => 'birthDate',
            'isTourLead' => 'tourLeader',
        ]);

//todo переделать после нормального получения документа туриста
        $touristInfo['document'] = ['docTypeId' => 1];
//
        $tourist->setAttributes($touristInfo);
        $tourist->linkToService($service['ServiceID']);

        if (!$tourist->save()) {

            $this->errorCode = OrdersErrors::CANNOT_UPDATE_TOURIST;
            return false;
        }

        return $tourist->touristId;
    }

    private function _updateService($serviceData)
    {

        if (empty($serviceData)) {
            return false;
        }

        $serviceInfo = ServicesForm::getServiceByUtkId($serviceData['serviceIdUTK']);

        if (empty($serviceInfo)) {
            $this->errorCode = OrdersErrors::SERVICE_NOT_FOUND;
            return false;
        }

        $service = ServicesFactory::createService($serviceInfo['ServiceType']);

        $service->setParamsMapping([
            'ServiceID' => 'serviceID',
            'ServiceID_UTK' => 'serviceUtkId',
            'ServiceID_GP' => 'serviceGptsId',
            'Status' => 'status',
            'ServiceType' => 'serviceType',
            'OfferID' => 'offerId',
            'DateStart' => 'startDateTime',
            'DateFinish' => 'endDateTime',
            'AmendAllowed' => 'amendAllowed',
            'DateAmend' => 'dateAmend',
            'DateOrdered' => 'dateOrdered',
            'SupplierPrice' => 'supplierPrice',
            'KmpPrice' => 'saleSum',
            'AgencyProfit' => 'commission',
            'SupplierCurrency' => 'supplierCurrency',
            'SaleCurrency' => 'saleCurrency',
            'OrderID' => 'orderId',
            'Extra' => 'serviceDescription',
            'CityID' => 'cityId',
            'CountryID' => 'countryId',
            'SupplierID' => 'supplierId',
            'SupplierSvcID' => 'supplierServiceId',
            'ServiceName' => 'serviceName',
            'ServiceID_main' => 'parentServiceId',
        ]);

        $service->setAttributes($serviceInfo);

        $serviceData['countryId'] = CountryForm::getCountryIdByUtkCountryId($serviceData['countryId']);
        $serviceData['cityId'] = CitiesMapperHelper::getCityIdByUtkCityId($serviceData['cityId']);

//        todo реализовать сохранение идентификатора родительской услуги когда он будет приходить
        $service->setParamsMapping([
            'ServiceID' => 'serviceID',
            'ServiceID_UTK' => 'serviceUtkId',
            'GPTSserviceID' => 'serviceGptsId',
            'status' => 'status',
            'serviceType' => 'serviceType',
            'startDateTime' => 'startDateTime',
            'endDateTime' => 'endDateTime',
            'refNum' => 'supplierServiceId',
            'offline' => 'offline',
            'supplierId' => 'supplierId',
            'SalePrice' => 'supplierPrice',
            'NetPrice' => 'supplierNetPrice',
            'SaleSum' => 'saleSum',
            'NetSum' => 'supplierPrice',
            'NetCurrency' => 'supplierCurrency',
            'SaleCurrency' => 'saleCurrency',
            'CommissionSum' => 'commission',
            'cityId' => 'cityId',
            'countryId' => 'countryId',
            'serviceDescription' => 'serviceDescription',
            'parentServiceId' => 'parentServiceId',
            'ServiceDetails' => 'serviceDetails'
        ]);

        $CurrencyRates = CurrencyRates::getInstance();

        $currencyCode = $CurrencyRates->getIdByCode($serviceData['NetCurrency']);
        if (empty($currencyCode)) {
            $this->errorCode = OrdersErrors::SUPPLIER_CURRENCY_INCORRECT;
            return false;
        }
        $serviceData['NetCurrency'] = $currencyCode;

        $currencyCode = $CurrencyRates->getIdByCode($serviceData['SaleCurrency']);
        if (empty($currencyCode)) {
            $this->errorCode = OrdersErrors::SUPPLIER_CURRENCY_INCORRECT;
            return false;
        }
        $serviceData['SaleCurrency'] = $currencyCode;

        if (!empty($serviceData['parentServiceUtkId'])) {
            $parentService = ServicesForm::getServiceByUtkId($serviceData['parentServiceUtkId']);

            $serviceData['parentServiceId'] = (!empty($parentService))
                ? $parentService['ServiceID']
                : 0;
        }

        $serviceData['offline'] = ($serviceData['online'] == 0 ? 1 : 0);

        $service->setAttributes($serviceData);

        $service->status = StatusesMapperHelper::getKtByUTKStatus(
            $service->status, StatusesMapperHelper::STATUS_TYPE_SERVICE, $this->namespace
        );

        $offer = OffersFactory::createOffer($service->serviceType);

        if (!$offer) {

            LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                'Неизвестный тип услуги для ' . print_r($service, 1), 'trace',
                $this->namespace . '.errors');
            $this->errorCode = OrdersErrors::UNKNOWN_SERVICE_TYPE;
            return false;
        }

        $offer->setOfferDetails($service->serviceDetails);

        if (!$offer->saveOffer()) {
            $this->errorCode = OrdersErrors::CANNOT_CREATE_OFFER;
            return false;
        }

        $service->setOfferId($offer->getOfferId());

        $service->setServiceName($offer->getOfferName());

        return $service->save();
    }

    /**
     * Получить информацию по счетам
     * и оплатам указанной заявки
     * @param $params
     */
    public function getOrderInvoices($params)
    {

        if (empty($params['orderId'])) {
            $this->errorCode = OrdersErrors::ORDER_ID_NOT_SET;
            return null;
        }

        $orderForm = new OrderSearchForm($params);

        try {
            $invoices = $orderForm->getOrderInvoices($params['orderId']);
        } catch (Exception $e) {
            $this->errorCode = OrdersErrors::DB_ERROR;
            return null;
        }

        if (empty($invoices) || !count($invoices)) {
            return [];
        }

        $invoicesGroups = [];
        foreach ($invoices as $invoice) {
            $invoicesGroups[$invoice['invoiceId']]['Inv'] = array_slice($invoice, 0, 7);

            $invoicesGroups[$invoice['invoiceId']]
            ['Services'][$invoice['serviceId']] = array_slice($invoice, 7, 4);

            if (!empty($invoice['paymentSum']) && !empty($invoice['paymentCur']) &&
                !empty($invoice['paymentDate'])
            ) {
                $invoicesGroups[$invoice['invoiceId']]
                ['Payments'][] = array_slice($invoice, 11, 4);
            } else {
                $invoicesGroups[$invoice['invoiceId']]['Payments'] = [];
            }

        }

        $result = [];
        foreach ($invoicesGroups as $invoicesGroup) {

            $result['Invoices'][] = array_merge($invoicesGroup['Inv'], [
                'InvoiceServices' => array_values($invoicesGroup['Services']),
                'Payments' => $invoicesGroup['Payments']
            ]);

        }

        return $result;
    }

    /**
     * Создать счёт и его вложенные объекты
     * @param $params
     * @return array|bool
     */
    public function processInvoiceInfo($params)
    {
//        if (isset($params['invoiceId']) && isset($params['invoiceIdUTK'])) {
//            $Invoice = InvoiceRepository::getByIds($params['invoiceIdUTK'], $params['invoiceId']);
//        } else {
//            LogHelper::logExt(
//                __CLASS__, __METHOD__,
//                'Прием счета из УТК', 'Некорретные параметры счета',
//                $params,
//                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.error'
//            );
//            return false;
//        }
//
//        if (is_null($Invoice)) {
//            $Invoice = new Invoice();
//        }

        if (!empty($params['invoiceId'])) {
            $existedInvoice = InvoicesForm::getInvoiceById($params['invoiceId']);
        }

        if (empty($existedInvoice)) {
            $existedInvoice = InvoicesForm::getInvoiceByUtkId($params['invoiceIdUTK']);
        }

        // если документа на этот счет еще нет, то запишем
        $OrderDocument = OrderDocumentRepository::getOrderDocumentByObjectId($params['invoiceId']);

        // попробуем сохранить файл счета
        if (is_null($OrderDocument)) {
            $module = Yii::app()->getModule('orderService');
            $confWithBasePath = $module->getConfig();
            $confWithStoragePath = $this->module->getConfig();

            if (!array_key_exists('storagePath', $confWithStoragePath) || !array_key_exists('baseUrl', $confWithBasePath)) {
                $this->errorCode = OrdersErrors::CANNOT_GET_FILES_STORAGE_SETTINGS;
                return false;
            }

            $fileRealPath = $confWithBasePath['baseUrl'] . $confWithStoragePath['storagePath'] . str_replace(' ', '%20', $params['invoiceURL']);

            try {
                $OrderDocument = new OrderDocument();
                $OrderDocument->setFile("Счет №{$params['invoiceIdUTK']} по заявке {$params['orderId']}", $fileRealPath);
                $OrderDocument->setOrderId($params['orderId']);
                $OrderDocument->setObject($params['invoiceId'], OrderDocument::INVOICE_FILE);
                $OrderDocument->save(false);
            } catch (OrderDocumentException $e) {
                $this->errorCode = OrdersErrors::INVOICE_FILE_ERROR;

                LogHelper::logExt(
                    __CLASS__, __METHOD__,
                    'Создание файла счета', $e->getMessage(),
                    $fileRealPath,
                    LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.error'
                );

                return false;
            } catch (CDbException $e) {
                $this->errorCode = OrdersErrors::DB_ERROR;

                LogHelper::logExt(
                    __CLASS__, __METHOD__,
                    'Создание файла счета', 'Не удалось сделать запись в БД',
                    '',
                    LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.error'
                );

                return false;
            }

        }

//        $Invoice->fromUtkArray($params);

        if (!empty($existedInvoice)) {
            $invoiceId = $this->_updateInvoice($params);
            if (!$invoiceId) {
                $this->errorCode = OrdersErrors::CANNOT_UPDATE_INVOICE;
                return false;
            }
        } else {
            $invoiceId = $this->_createInvoice($params);

            if (!$invoiceId) {
                $this->errorCode = OrdersErrors::CANNOT_CREATE_INVOICE;
            }
        }

        return ['invoiceId' => $invoiceId];
    }

    /**
     * Обновить информацию в существующем счёте
     * @param $params
     */
    private function _updateInvoice($params)
    {
        $invoiceInfo = InvoicesForm::getInvoiceByUtkId($params['invoiceIdUTK']);
        if (empty($invoiceInfo)) {
            $invoiceInfo = InvoicesForm::getInvoiceById($params['invoiceId']);
        }

        if (empty($invoiceInfo)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::INVOICE_NOT_FOUND,
                ['params' => print_r($params, 1)]
            );
        }

        $invoiceForm = new InvoiceForm();

        $utkInvoice = new UtkInvoiceForm();

        $utkInvoice->setParamsMapping([
            'orderIdUTK' => 'orderUtkId',
            'invoiceIdUTK' => 'invoiceUtkId',
            'status' => 'invoiceStatus',
            'serviceTableShort' => 'invoiceServices'
        ]);

        $utkInvoice->setAttributes($params);

        $invoiceForm->setParamsMapping([
            'invoiceUtkId' => 'invoiceIdUtk',
            'invoiceDate' => 'invoiceDate',
            'invoiceStatus' => 'invoiceStatus',
            'invoiceServices' => 'invoiceServices'
        ]);

        $invoiceForm->setAttributes(
            $utkInvoice->getAttributes(null)
        );

        $invoiceForm->setParamsMapping([
            'InvoiceID' => 'invoiceId',
            'InvoiceID_UTK' => 'invoiceIdUTK',
        ]);

        $invoiceForm->setAttributes($invoiceInfo);

        foreach ($invoiceForm->invoiceServices as $key => $invoiceService) {

            if (empty($invoiceService['serviceId'])) {
                $savedService = ServicesForm::getServiceByUtkId($invoiceService['serviceUtkId']);
                $invoiceForm->invoiceServices[$key]['serviceId'] = $savedService['ServiceID'];
                $invoiceForm->invoiceServices[$key]['invoiceId'] = $invoiceForm->invoiceId;
            } else {
                $invoiceForm->invoiceServices[$key]['invoiceId'] = $invoiceForm->invoiceId;
            }

        }

        return $invoiceForm->update();
    }

    /**
     * Создать новый счёт
     * @param $params
     */
    private function _createInvoice($params)
    {
        $utkInvoice = new UtkInvoiceForm();

        $utkInvoice->setParamsMapping([
            'orderIdUTK' => 'orderUtkId',
            'invoiceIdUTK' => 'invoiceUtkId',
            'status' => 'invoiceStatus',
            'serviceTableShort' => 'invoiceServices'
        ]);

        $utkInvoice->setAttributes($params);

        $invoiceForm = new InvoiceForm();

        $invoiceForm->setParamsMapping([
            'invoiceId' => 'invoiceId',
            'invoiceUtkId' => 'invoiceIdUTK',
            'invoiceDate' => 'invoiceDate',
            'invoiceStatus' => 'invoiceStatus',
            'invoiceServices' => 'invoiceServices'
        ]);

        foreach ($utkInvoice->invoiceServices as $key => $invoiceService) {
            if (empty($invoiceService['serviceId'])) {
                $savedService = ServicesForm::getServiceByUtkId($invoiceService['serviceIdUTK']);
                $utkInvoice->invoiceServices[$key]['serviceId'] = $savedService['ServiceID'];
            }
        }

        $invoiceForm->setAttributes(
            $utkInvoice->toKtAttributes()
        );

        return $invoiceForm->create();
    }

    /**
     * Создать или обновить информацию по оплате
     * @param $params array данные оплаты
     * @return bool
     */
    public function processPaymentInfo($params)
    {
        $paymentForm = new PaymentsForm();

        if (!empty($params['paymentId'])) {
            $payment = $paymentForm->getPaymentById($params['paymentId']);
        } else {
            $payment = $paymentForm->getPaymentByUtkId($params['paymentIdUTK']);
        }

        $params['paymentStatus'] = StatusesMapperHelper::getKtByUTKStatus(
            $params['paymentStatus'], StatusesMapperHelper::STATUS_TYPE_PAYMENT, $this->namespace
        );

        if ($params['paymentStatus'] = "") {
            $this->errorCode = OrdersErrors::CANNOT_FIND_PAYMENT_STATUS;

            LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                'Невозможно получить статус оплаты ' . print_r($params, 1), 'trace',
                $this->namespace . '.errors');

            return false;
        }

        if (!empty($payment['PaymentID'])) {

            $params['paymentId'] = $payment['PaymentID'];

            if (!$this->_updatePayment($params)) {
                $this->errorCode = OrdersErrors::CANNOT_UPDATE_PAYMENT;
                return false;
            }

            return ['paymentId' => $params['paymentId']];

        } else {

            $paymentId = $this->_createPayment($params);

            if (!$paymentId) {
                $this->errorCode = OrdersErrors::CANNOT_CREATE_PAYMENT;
            }

            return ['paymentId' => $paymentId];
        }
    }

    /**
     * Создать объект оплаты
     * @param $params array данные оплаты
     * @return bool
     */
    private function _createPayment($params)
    {

        $utkPayment = new UtkPaymentForm();

        $params['paymentType'] = TypesMapperHelper::getKtByUTKType(
            $params['paymentType'],
            TypesMapperHelper::TYPE_PAYMENT,
            $this->namespace
        );

        $utkPayment->setParamsMapping([

            'orderIdUTK' => 'orderUtkId',
            'invoiceIdUTK' => 'invoiceUtkId',
            'paymentId' => 'paymentId',
            'paymentIdUTK' => 'paymentUtkId',
            'paymentDate' => 'paymentDate',
            'price' => 'paymentAmount',
            'currency' => 'currencyId',
            'paymentStatus' => 'paymentStatus',
            'paymentType' => 'paymentType',
        ]);

        if (empty($params['invoiceId'])) {
            $invoice = InvoicesForm::getInvoiceByUtkId($params['invoiceIdUTK']);
        } else {
            $invoice = InvoicesForm::getInvoiceById($params['invoiceId']);
        }

        $params['invoiceId'] = $invoice['InvoiceID'];

        $utkPayment->setAttributes($params);

        $payment = new PaymentForm();

        $payment->setAttributes(
            $utkPayment->getAttributes(null)
        );

        return $payment->create();
    }

    /**
     * Обновить данные оплаты
     * @param $params array данные оплаты
     * @return bool
     */
    private function _updatePayment($params)
    {

        $utkPayment = new UtkPaymentForm();

        $utkPayment->setParamsMapping([

            'orderIdUTK' => 'orderUtkId',
            'invoiceIdUTK' => 'invoiceUtkId',
            'paymentId' => 'paymentId',
            'paymentIdUTK' => 'paymentUtkId',
            'paymentDate' => 'paymentDate',
            'price' => 'paymentAmount',
            'currency' => 'currencyId',
            'paymentStatus' => 'paymentStatus',
            'paymentType' => 'paymentType',
        ]);

        if (empty($params['invoiceId'])) {
            $invoice = InvoicesForm::getInvoiceByUtkId($params['invoiceIdUTK']);
        } else {
            $invoice = InvoicesForm::getInvoiceById($params['invoiceId']);
        }

        $params['invoiceId'] = $invoice['InvoiceID'];

        $utkPayment->setAttributes($params);

        $payment = new PaymentForm();

        $payment->setAttributes(
            $utkPayment->getAttributes(null)
        );

        return $payment->update();
    }

    /**
     * Получить туристов по указанным услугам
     * @param $params
     * @return array|bool
     */
    public function getOrderTourists($params)
    {

        $cxtName = $this->module->getCxtName(get_class(), __FUNCTION__);
        $err = $this->module;

        $tourists = [];
        $orderForm = OrderForm::createInstance($this->namespace);
        $order = $orderForm->getOrderByIdObj($params['orderId']);

        if (!$order) {
            $this->errorCode = OrdersErrors::CANNOT_FIND_ORDER;

            LogHelper::logExt(get_class(), __FUNCTION__, $cxtName,
                $err->getError($this->errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->namespace . '.errors');

            return false;
        }

        $orderSearchForm = new OrderSearchForm($params);
        $orderServices = $orderSearchForm->getOrdersServices($order->orderId);

        $servicesIds = [];
        foreach ($orderServices as $orderService) {
            $servicesIds[] = $orderService['serviceID'];
        }
        $orderTourists = TouristForm::getTouristsByOrderId($order->orderId);

        if (empty($orderTourists)) {
            return [];
        }

        foreach ($orderTourists as $orderTourist) {

            $tourist = new TouristForm($this->namespace);
            $tourist->loadTouristByID($orderTourist['TouristID']);

            $tourists[] = $tourist;
        }

        $displayInfo = [];
        foreach ($tourists as $tourist) {
            $touristInfo = $tourist->getBaseInfo();
            $docInfo = $tourist->getDocInfo();
            $servicesIds = $tourist->getTouristServicesIds();

            $isVisaServiceLinked = false;
            $isInsuranceLinked = false;
            foreach ($orderServices as $service) {
                if (in_array($service['serviceID'], $servicesIds)) {

                    if ($service['serviceType'] == ServicesFactory::VISA_SERVICE_TYPE) {
                        $isVisaServiceLinked = true;
                    }

                    if ($service['serviceType'] == ServicesFactory::INSURANCE_SERVICE_TYPE) {
                        $isInsuranceLinked = true;
                    }

                    $OrdersServicesTourist = OrdersServicesTouristsRepository::findByServiceAndOrderTouristIds($service['serviceID'], $touristInfo['touristId']);

                    $touristInfo['services'][] = [
                        'serviceType' => $service['serviceType'],
                        'serviceId' => $service['serviceID'],
                        'serviceName' => $service['serviceName'],
                        'status' => $service['status'],
                        'bonuscardNumber' => $OrdersServicesTourist->getMileCard(),
                        'aviaLoyalityProgrammId' => $OrdersServicesTourist->getLoyalityProgramId()
                    ];
                }
            }

            $needServices = [];
            if ($touristInfo['needVisa']) {

                $needServices[] = [
                    'serviceType' => ServicesFactory::VISA_SERVICE_TYPE,
                    'linked' => $isVisaServiceLinked
                ];
            }
            unset($touristInfo['needVisa']);

            if ($touristInfo['needInsurance']) {

                $needServices[] = [
                    'serviceType' => ServicesFactory::INSURANCE_SERVICE_TYPE,
                    'linked' => $isInsuranceLinked
                ];
            }
            unset($touristInfo['needInsurance']);

            // вытаскиваем доп поля туриста через новый код
            $touristAdditionalData = [];
            $orderTourist = OrderTouristRepository::getByOrderIdAndTouristId($params['orderId'], $touristInfo['touristId']);
            $touristAdditionalFields = OrderAdditionalFieldRepository::getTouristFieldWithId($orderTourist);
            foreach ($touristAdditionalFields as $touristAdditionalField) {
                $touristAdditionalData[] = $touristAdditionalField->toArray();
            }

            $touristInfo['document'] = $docInfo;
            $touristInfo['needServices'] = $needServices;
            $touristInfo['touristAdditionalData'] = $touristAdditionalData;
            $displayInfo['tourists'][] = $touristInfo;
        }

        return $displayInfo;

    }

    /**
     * Сформировать структуру для создания счёта в УТК
     * @param $params array
     */
    public function prepareInvoiceParams($params)
    {

        $invoiceParams = [];

        $orderForm = OrderForm::createInstance($this->namespace);
        $order = $orderForm->getOrderById($params['orderId']);

        $orderSearchForm = new OrderSearchForm($params);
        $orderServices = $orderSearchForm->getOrdersServices($order['OrderID']);

        $invoiceParams['orderId'] = $order['OrderID'];
        $invoiceParams['orderIdUTK'] = $order['OrderID_UTK'];
        $invoiceParams['orderDate'] = (new DateTime($order['OrderDate']))->format('Y-m-d\TH:i:s');
        $invoiceParams['invoiceId'] = '';
        $invoiceParams['invoiceIdUTK'] = '';
        $invoiceParams['invoiceDate'] = (new DateTime())->format('Y-m-d\TH:i:s');
        $invoiceParams['status'] = InvoiceForm::STATUS_WAIT_TO_CREATE;

        foreach ($params['services'] as $invServicesKey => $invoiceService) {

            foreach ($orderServices as $orderService) {

                if ($orderService['serviceID'] == $invoiceService['serviceId']) {

                    $invoiceService['serviceIdUTK'] = $orderService['serviceUtkId'];
                    $invoiceService['serviceName'] = $orderService['serviceName'];

                    $invoiceService['currency'] = $params['currency'];
                    $invoiceService['commission'] = 0;

                    $orderTotalSum = $orderService['supplierPrice'] + $orderService['agencyProfit'];

                    // paymentType (string - required) - Тип выставления счета:
                    // 1 - полный
                    // 2 - частичный
                    $invoiceService['partial'] = ($orderTotalSum >= $invoiceService['invoicePrice']) ? 2 : 1;

                    $invoiceParams['serviceTableShort'][] = $invoiceService;
                }
            }
        }

        return $invoiceParams;
    }

    /**
     * Добавить туриста в заявку или обновить его данные
     * @param $params
     * @return array|bool
     */
    public function setOrderTourist($params)
    {
        $orderId = $params['orderId'];

        $tourist = new TouristForm($this->namespace);

        if (!isset($params['touristId']) || empty($params['touristId'])) {
            $touristInfo = $params;
            $touristInfo['document'] = [
                'name' => $params['document']['firstName'],
                'middleName' => $params['document']['middleName'],
                'surname' => $params['document']['surName'],
                'docSerial' => $params['document']['serialNum'],
                'docNumber' => $params['document']['number'],
                'validFrom' => $params['document']['issueDate'],
                'validTill' => $params['document']['expiryDate'],
                'issueBy' => $params['document']['issueDepartment'],
                'docTypeId' => $params['document']['documentType'],
                'citizenship' => $params['document']['citizenship']
            ];

            $touristInfo['orderId'] = $orderId;
            $touristInfo['serviceId'] = empty($touristInfo['serviceId']) ? 0 : $touristInfo['serviceId'];
            $touristInfo['isTourLeader'] = !empty($touristInfo['isTourLeader']) || $touristInfo['isTourLeader'] == true
                ? 1 : 0;

            if (!empty($touristInfo['needServices']) && count($touristInfo['needServices']) > 0) {

                foreach ($touristInfo['needServices'] as $addService) {

                    if ($addService['serviceType'] == ServicesFactory::VISA_SERVICE_TYPE) {
                        $touristInfo['needVisa'] = ($addService['required'] == true) ? 1 : 0;
                    }

                    if ($addService['serviceType'] == ServicesFactory::INSURANCE_SERVICE_TYPE) {
                        $touristInfo['needInsurance'] = ($addService['required'] == true) ? 1 : 0;
                    }
                }
            }

            $tourist->setParamsMapping([
                'touristId' => 'touristId',
                'orderId' => 'orderId',
                'firstName' => 'name',
                'middleName' => 'middleName',
                'surName' => 'surname',
                'email' => 'email',
                'phone' => 'phone',
                'sex' => 'sex',
                'birthdate' => 'birthDate',
                'address' => 'address',
                'isTourLeader' => 'tourLeader',
                'needVisa' => 'needVisa',
                'needInsurance' => 'needInsurance',
                'document' => 'document',
                'services' => 'services',
            ]);

            $tourist->setAttributes($touristInfo);
        } else {
            $tourist->loadTouristByID($params['touristId']);
        }

        $orderSearchForm = OrderSearchForm::createInstance();
        $orderServices = $orderSearchForm->getOrdersServices($orderId);

        if (!empty($params['services']) && count($params['services']) > 0) {
            foreach ($params['services'] as $serviceInfo) {
                foreach ($orderServices as $orderService) {
                    if ($orderService['serviceID'] == $serviceInfo['serviceId'] || $orderService['offline']) {
                        $tourist->linkToService($orderService['serviceID']);
                    }
                }
            }
        } else {
            if (!empty($touristInfo['touristId'])) {
                $existedTourist = new TouristForm($this->namespace);
                $existedTourist->loadTouristByID($touristInfo['touristId']);

                if (!empty($existedTourist)) {
                    foreach ($existedTourist->getTouristServicesIds() as $serviceId) {
                        $tourist->linkToService($serviceId);
                    }
                }
            }
        }

        if (!empty($params['detachServices']) && count($params['detachServices']) > 0) {

            foreach ($params['detachServices'] as $serviceInfo) {

                foreach ($orderServices as $orderService) {

                    if ($orderService['serviceID'] == $serviceInfo['serviceId'] || $orderService['offline']) {
                        $tourist->unlinkToService($orderService['serviceID']);
                    }
                }
            }
        }

        if (!$tourist->save()) {
            $this->errorCode = $tourist->getLastError();
            return false;
        }

        return ['touristId' => $tourist->touristId];
    }

    /**
     * Удаление привязки туриста к услугам
     */
    public function removeTouristFromOrder($params)
    {

        $tourist = new TouristForm($this->namespace);
        $tourist->loadTouristByID($params['touristId']);

        if (!$tourist->removeFromServices()) {
            $this->errorCode = OrdersErrors::CANNOT_REMOVE_TOURIST_FROM_SERVICES;
            return false;
        }

        if (!$tourist->removeFromOrder()) {
            $this->errorCode = OrdersErrors::CANNOT_REMOVE_TOURIST_FROM_ORDER;
            return false;
        }

        return ['touristId' => $tourist->touristId, 'operation' => 'delete'];
    }

    /**
     * Удалить заявку и её вложенные объекты
     * @param $params array параметры удаления заявки
     * @return array|bool
     */
    public function removeOrder($params)
    {

        // todo для этой команды надо будет обязательно реализовать проверку доступа

        $result = [];

        if (empty($params['orderId']) && $params['orderIdUtk'] == '') {
            $this->errorCode = OrdersErrors::ORDER_ID_NOT_SET;
            return null;
        }

        $orderForm = new OrderForm($params, $this->namespace);

        if (!empty($params['orderId'])) {
            $existedOrder = $orderForm->getOrderById($params['orderId']);
        }

        if (empty($existedOrder)) {
            $existedOrder = $orderForm->getOrderByUTKId($params['orderIdUtk']);
        }

        if (empty($existedOrder)) {
            $this->errorCode = OrdersErrors::ORDER_NOT_FOUND;
            return null;
        }

        $params['orderId'] = $existedOrder['OrderID'];

        $orderForm->setAttributes($params);

        if (!$orderForm->removeOrder()) {
            $this->errorCode = OrdersErrors::ORDER_NOT_FOUND;
            return null;
        };

        return ['orderId' => $existedOrder['OrderID'], 'action' => 'remove'];
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->errorCode;
    }

    /**
     * Проверка прав доступа пользователя для указанного метода
     * @param $action
     * @return bool
     */
    private function checkCommandRights($commandId, $userToken)
    {

        try {

            $cmdRights = CommandRights::getCommandRights($commandId);

            if (!$cmdRights) {

                throw new KmpInvalidUserRightsException(
                    get_class($this),
                    __FUNCTION__,
                    OrdersErrors::CANNOT_GET_CURRENT_COMMAND_RIGHTS,
                    ['commandId' => $commandId]
                );
            }

            $this->checkRights($cmdRights, $userToken);

        } catch (KmpInvalidSettingsException $kse) {

            LogHelper::logExt(
                $kse->class,
                $kse->method,
                $this->module->getCxtName($kse->class, $kse->method),
                $this->module->getError($kse->getCode()),
                $kse->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = OrdersErrors::CANNOT_GET_CURRENT_USER_RIGHTS;
            return false;

        } catch (KmpInvalidUserRightsException $kure) {

            LogHelper::logExt(
                $kure->class,
                $kure->method,
                $this->module->getCxtName($kure->class, $kure->method),
                $this->module->getError($kure->getCode()),
                $kure->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = OrdersErrors::NOT_ENOUGH_USER_RIGHTS;
            return false;
        }
        return true;
    }


}
