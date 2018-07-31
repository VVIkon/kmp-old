<?php

use Symfony\Component\Validator\Validation;

/**
 * Импорт заявки (ex. из УТК)
 */
class ImportOrderDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    // названия структур, специфичных для типов услуг
    private $serviceTypeStructures = [
        1 => [
            'offerInfo' => 'hotelOffer'
        ],
        2 => [
            'offerInfo' => 'aviaOffer'
        ]
    ];

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $utkOrder = $params['orderData'];
        $gptsOrder = null;

        if (empty($utkOrder['orderIdUTK'])) {
            $this->setError(OrdersErrors::UTK_ORDER_ID_NOT_SET, $utkOrder);
            return false;
        }
        if (empty($utkOrder['Tourists']) || !is_array($utkOrder['Tourists'])) {
            $this->setError(OrdersErrors::NO_TOURISTS_IN_ORDER, [
                'orderIdUTK' => $utkOrder['orderIdUTK']
            ]);
            return false;
        }
        if (empty($utkOrder['Services']) || !is_array($utkOrder['Services'])) {
            $this->setError(OrdersErrors::NO_SERVICES_IN_ORDER, [
                'orderIdUTK' => $utkOrder['orderIdUTK']
            ]);
            return false;
        }

        $Order = $this->setOrder($utkOrder);
        if (is_null($Order)) {
            return false;
        }
        if (!$this->updateOrderWithUtkData($Order, $utkOrder)) {
            $this->addLog('Для существующих онлайн заявок данные из УТК не берем');
            return false;
        }
        if (!empty($utkOrder['GPTSorderId'])) {
            $gptsOrder = $this->getGptsOrder($utkOrder);
            /*
            * Некрасиво, да. null - ошибка, false - заявка не нашлась в GP, обрабатываем как архивную
            */
            if (is_null($gptsOrder)) {
                return false;
            } elseif ($gptsOrder === false) {
                $Order->setArchive(true);
                $gptsOrder = null;
            } else {
                $this->updateOrderWithGptsData($Order, $gptsOrder['order']);
            }
        }
        $Services = [];
        $ServicesPrices = [];

        // обработка услуг
        foreach ($utkOrder['Services'] as $utkService) {
            $Service = $this->setService($utkService, $Order);
            if (is_null($Service)) {
                return false;
            }

            $serviceIdUtk = $Service->getServiceIDUTK();

            if (
                $Order->isArchived() ||
                empty($Service->getServiceIDGP()) ||
                empty($this->serviceTypeStructures[$Service->ServiceType])
            ) {
                // создание услуги по данным из УТК
                if (!$this->updateServiceWithUtkData($Service, $utkService)) {
                    return false;
                }

                $Service->setOffline(true);

                $ServicePrices = $this->setServicePricesFromUtk($utkService);
                if (is_null($ServicePrices)) {
                    return false;
                }
                $ServicesPrices[$serviceIdUtk] = $ServicePrices;
            } else {
                // создание услуги по данным из GPTS
                if (empty($gptsOrder['services'][$serviceIdUtk])) {
                    $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR, [
                        'msg' => 'no such gp service',
                        'serviceIdUTK' => $serviceIdUtk,
                        'gpOrderServices' => $gptsOrder['services']
                    ]);
                    return false;
                }

                $gpService = $gptsOrder['services'][$serviceIdUtk];
                $Offer = $this->setOfferFromGpts($Service, $gpService);
                if (is_null($Offer)) {
                    return false;
                }
                if (!$this->updateServiceWithGptsData($Service, $gpService, $Offer)) {
                    return false;
                }
                $ServicesPrices[$serviceIdUtk] = $Offer->getPriceOffers();
            }

            $Services[$serviceIdUtk] = $Service;
        }

        $OrderTourists = [];
        $TouristsServiceLinks = [];
        $hasTourleader = false; // хак на турлидера

        // обработка туристов из УТК
        foreach ($utkOrder['Tourists'] as $utkTourist) {
            $linkedServiceUtkId = $utkTourist['serviceIdUTK'];
            $linkedService = $Services[$linkedServiceUtkId];

            if (is_null($linkedService)) {
                $this->setError(OrdersErrors::TOURISTS_LINKS_TO_SERVICES_INCORRECT, ['touristServiceUtkId' => $linkedServiceUtkId]);
                return false;
            }

            // сохраняем только туристов, привязанных к УТК'шным услугам
            if (
                $Order->isArchived() ||
                (!$linkedService->isKtService() && empty($linkedService->getServiceIDGP()))
            ) {
                $uniqueTouristId = 'utk' . $utkTourist['personIdUTK'];
                if (!isset($OrderTourists[$uniqueTouristId])) {
                    $OrderTourists[$uniqueTouristId] = $this->setOrderTouristFromUtk($utkTourist, $Order);
                    $TouristsServiceLinks[$uniqueTouristId] = [];
                }
                $TouristsServiceLinks[$uniqueTouristId][] = $linkedServiceUtkId;
                if ((bool)$utkTourist['isTourLead']) {
                    $hasTourleader = true;
                }
            }
        }

        // обработка туристов из GPTS
        if (!is_null($gptsOrder)) {
            foreach ($gptsOrder['tourists'] as $gpTourist) {
                /* Убираем ссылки на КТ'шные услуги */
                $touristServiceLinks = $gptsOrder['touristServiceLinks'][$gpTourist['touristIdGP']];
                $touristServiceLinks = array_filter($touristServiceLinks, function ($utkServiceId) use ($Services) {
                    return !$Services[$utkServiceId]->isKtService();
                });

                // если турист привязан только к КТ'шным услугам, то создавать его не надо'
                if (count($touristServiceLinks) === 0) {
                    continue;
                }

                $uniqueTouristId = 'gp' . $gpTourist['touristIdGP'];
                $OrderTourists[$uniqueTouristId] = $this->setOrderTouristFromGpts($gpTourist, $Order);
                $TouristsServiceLinks[$uniqueTouristId] = $touristServiceLinks;

                if (!$hasTourleader) {
                    $OrderTourists[$uniqueTouristId]->setTourLeader(true);
                    $hasTourleader = true;
                }
            }
        }

        $isNewOrder = (is_null($Order->getOrderId())) ? true : false;

        $response = [];
        $importingProcess = Yii::app()->db->beginTransaction();

        /**
         * @todo добавить обработку на случай, когда туристы привязаны к несуществующей услуге
         */
        try {
            $Order->save();
            $Order->refresh();
            $orderId = $Order->getOrderId();
            $response['orderId'] = $orderId;
            $response['services'] = [];
            $response['tourists'] = [];

            if ($isNewOrder) {
                $OrderHistory = new OrderHistory();
                $OrderHistory->setObjectData($Order);
                $OrderHistory->setOrderData($Order);
                $OrderHistory->setActionResult(0);
                $OrderHistory->setCommentTpl('{{122}} {{orderId}}');
                $OrderHistory->setCommentParams([
                    'orderId' => $orderId
                ]);
                $this->addOrderAudit($OrderHistory, 1);
            }

            foreach ($Services as $Service) {
                $isNew = empty($Service->getServiceID());
                $Service->setOrderId($orderId);
                $Service->save();

                if ($isNew) {
                    foreach ($ServicesPrices[$Service->getServiceIDUTK()] as $ServicePrice) {
                        $Service->addPrice($ServicePrice);
                    }
                } elseif ($Service->isOffline()) {
                    // проверка для обработки старых заявок без *правильных* структур цен
                    if (count($Service->getServicePrices()) > 0) {
                        foreach ($ServicesPrices[$Service->getServiceIDUTK()] as $ServicePrice) {
                            $Service->updatePrice($ServicePrice);
                        }
                    } else {
                        foreach ($ServicesPrices[$Service->getServiceIDUTK()] as $ServicePrice) {
                            $Service->addPrice($ServicePrice);
                        }
                    }
                }

                $response['services'][] = [
                    'serviceId' => $Service->getServiceID(),
                    'serviceIdUTK' => $Service->getServiceIDUTK(),
                    'action' => $isNew ? 'create' : 'update'
                ];
            }

            foreach ($OrderTourists as $uniqueTouristId => $OrderTourist) {
                $OrderTourist->setOrderID($orderId);
                $OrderTourist->save();

                if (strpos($uniqueTouristId, 'utk') === 0) {
                    // вытаскивать инфу, создавали ли мы туриста, муторно и бессмысленно, считаем что обновили
                    $response['tourists'][] = [
                        'touristId' => $OrderTourist->getTouristID(),
                        'touristIdUTK' => $OrderTourist->getTourist()->getUTKTouristID(),
                        'action' => 'update'
                    ];
                }

                foreach ($TouristsServiceLinks[$uniqueTouristId] as $utkServiceId) {
                    if (!isset($Services[$utkServiceId])) {
                        $this->setError(OrdersErrors::TOURISTS_LINKS_TO_SERVICES_INCORRECT, [
                            'orderIdUTK' => $utkOrder['orderIdUTK'],
                            'touristIdUTK' => $OrderTourist->getTourist()->getUTKTouristID(),
                            'touristIdGP' => $OrderTourist->getTourist()->getGPTSTouristID(),
                            'linkedServiceIdUTK' => $utkServiceId
                        ]);
                        return false;
                    }

                    $serviceId = $Services[$utkServiceId]->getServiceID();
                    $orderTouristId = $OrderTourist->getTouristID();

                    $ServiceTourist = OrdersServicesTouristsRepository::findByServiceAndOrderTouristIds($serviceId, $orderTouristId);

                    if (is_null($ServiceTourist)) {
                        $ServiceTourist = new OrdersServicesTourists();
                        $ServiceTourist->setServiceID($serviceId);
                        $ServiceTourist->setTouristID($orderTouristId);
                        $ServiceTourist->save();
                    }
                }
            }

            $importingProcess->commit();
        } catch (Exception $e) {
            $importingProcess->rollback();
            $this->setError(OrdersErrors::CANNOT_CREATE_ORDER, [
                'msg' => $e->getMessage()
            ]);
            return false;
        }

        $this->params['object'] = $Order->serialize();

        $this->mergeResponse($response);
    }

    /**
     * Получение данных заявки из GPTS
     * @param array $utkOrder данные заявки от УТК
     * @return array|false|null данные заявки из GPTS | заявки в GPTS нет | ошибка
     */
    private function getGptsOrder(array $utkOrder)
    {
        $gpOrderId = $utkOrder['GPTSorderId'];
        $requestData = [
            'services' => []
        ];

        foreach ($utkOrder['Services'] as $utkService) {
            if (!empty($utkService['GPTSserviceID'])) {
                $requestData['services'][] = [
                    'serviceID' => $utkService['serviceIdUTK'],
                    'serviceType' => $utkService['serviceType'],
                    'addServices' => [],
                    'engineData' => [
                        'gateId' => 5,
                        'data' => [
                            'GPTS_order_ref' => $gpOrderId,
                            'GPTS_service_ref' => $utkService['GPTSserviceID']
                        ]
                    ]
                ];
            }
        }
        if (!empty($requestData)) {
            $apiClient = new ApiClient(Yii::app()->getModule('orderService'));

            try {
                $response = json_decode($apiClient->makeRestRequest('supplierService', 'SupplierGetOrder', $requestData), true);
            } catch (Exception $e) {
                $this->setError(OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR, [
                    'msg' => $e->getMessage()
                ]);
                return null;
            }

            if ($response['status'] !== 0) {
                if ($response['errorCode'] === 49) {
                    return false;
                }

                $this->setError(OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR, [
                    'code' => $response['errorCode'],
                    'msg' => $response['errors']
                ]);

                return null;
            } else {
                $gptsOrder = [
                    'order' => $response['body']['supplierOrder']['order'],
                    'services' => [],
                    'tourists' => [],
                    'touristServiceLinks' => []
                ];

                foreach ($response['body']['supplierOrder']['tourists'] as $tourist) {
                    $gptsOrder['tourists'][$tourist['touristIdGP']] = $tourist;
                    $gptsOrder['touristServiceLinks'][$tourist['touristIdGP']] = [];
                }

                foreach ($response['body']['supplierOrder']['services'] as $service) {
                    $utkServiceId = $service['orderService']['serviceId'];
                    $gptsOrder['services'][$utkServiceId] = $service['orderService'];
                    $gptsOrder['services'][$utkServiceId]['supplierServiceData'] = $service['supplierServiceData'];

                    foreach ($service['serviceTourists'] as $touristLink) {
                        $gptsOrder['touristServiceLinks'][$touristLink][] = $utkServiceId;
                    }
                }

                return $gptsOrder;
            }
        } else {
            $this->setError(OrdersErrors::NO_SERVICES_IN_ORDER, [
                'requestData' => $requestData
            ]);
            return null;
        }
    }

    /*
    * Инициализация модели заявки
    * @param array $utkOrder данные заявки от УТК
    * @return OrderModel|null объект заявки или null в случае ошибки
    */
    private function setOrder(array $utkOrder)
    {
        $Order = new OrderModel();

        $hasOfflineServices = false;
        $hasOnlineServices = false;

        foreach ($utkOrder['Services'] as $utkService) {
            if ((bool)$utkService['online'] === true) {
                $hasOnlineServices = true;
            } else {
                $hasOfflineServices = true;
            }
        }

        $isExistingOrder = false;

        if (!empty($utkOrder['orderId'])) {
            $FoundOrder = OrderModelRepository::getByOrderId($utkOrder['orderId']);
            if (!is_null($FoundOrder)) {
                $Order = $FoundOrder;
                $isExistingOrder = true;
            } else {
                $this->setError(OrdersErrors::ORDER_ID_INCORRECT, [
                    'orderId' => $utkOrder['orderId'],
                    'orderIdUTK' => $utkOrder['orderIdUTK']
                ]);
                return null;
            }
        } elseif (!empty($utkOrder['GPTSorderId'])) {
            $FoundOrder = OrderModelRepository::getByGPTSOrderId($utkOrder['GPTSorderId']);
            if (!is_null($FoundOrder)) {
                $Order = $FoundOrder;
                $isExistingOrder = true;
            }
        }

        if ($isExistingOrder) {
            $presentUTKId = $Order->getOrderIDUTK();
            if (!empty($presentUTKId) && $presentUTKId !== $utkOrder['orderIdUTK'] && !empty($utkOrder['orderId'])) {
                $this->setError(OrdersErrors::UTK_ORDER_ID_INCORRECT, [
                    'orderId' => $Order->getOrderId(),
                    'presentOrderIdUTK' => $presentUTKId,
                    'receivedOrderIdUTK' => $utkOrder['orderIdUTK']
                ]);
                return null;
            } else {
                $Order->setOrderIDUTK($utkOrder['orderIdUTK']);
            }
        } else {
            $FoundOrder = OrderModelRepository::getByUTKOrderId($utkOrder['orderIdUTK']);
            if (is_null($FoundOrder)) {
                if ($hasOnlineServices && empty($utkOrder['GPTSorderId'])) {
                    $this->setError(OrdersErrors::ORDER_GPTS_ID_NOT_SET, [
                        'orderIdUTK' => $utkOrder['orderIdUTK']
                    ]);
                    return null;
                } else {
                    $Order->setOrderIDUTK($utkOrder['orderIdUTK']);
                }
            } else {
                $Order = $FoundOrder;
                $isExistingOrder = true;
            }
        }

        if (!empty($utkOrder['GPTSorderId'])) {
            $Order->setOrderIDGP($utkOrder['GPTSorderId']);
        }

        return $Order;
    }

    /**
     * Обновление модели заявки полученными от УТК данными
     * @param OrderModel $Order объект заявки
     * @param array $utkOrder данные заявки от УТК
     * @param bool $isExistingOrder флаг наличия данной заявки в базе КТ
     * @return bool результат операции
     */
    private function updateOrderWithUtkData(OrderModel &$Order, array $utkOrder)
    {
        $isExistingOrder = !empty($Order->orderId);
        $isOnline = !empty($utkOrder['GPTSorderId']);

        $utkOrderStatusMapping = [
            1 => OrderModel::STATUS_NEW,
            2 => OrderModel::STATUS_CLOSED,
            3 => OrderModel::STATUS_ANNULED,
            4 => OrderModel::STATUS_MANUAL
        ];

        // флаг VIP-заявки
        if (!empty($utkOrder['VIP']) && (int)$utkOrder['VIP'] === 1) {
            $Order->markAsVIP();
        } else {
            $Order->unmarkAsVIP();
        }

        // для существующих онлайн-заявок данные не переписываем
        if ($isOnline && $isExistingOrder) {
            return true;
        }

        // статус заявки
//        if (!isset($utkOrderStatusMapping[(int)$utkOrder['status']])) {
//            $this->setError(OrdersErrors::ORDER_STATUS_NOT_SET, [
//                'orderIdUTK' => $utkOrder['orderIdUTK'],
//                'status' => $utkOrder['status']
//            ]);
//            return false;
//        }
//        $Order->setStatus($utkOrderStatusMapping[(int)$utkOrder['status']]);

        // дата создания заявки
        $orderDate = false;
        if (!empty($utkOrder['orderDate'])) {
            $orderDate = strtotime($utkOrder['orderDate']);
        }
        if ($orderDate !== false) {
            if ($orderDate > time()) {
                $orderDate = time();
            }
            $Order->setOrderDate(date('Y-m-d H:i:s', $orderDate));
        } else {
            if (!$isExistingOrder) {
                $this->setError(OrdersErrors::ORDER_DATE_INCORRECT, [
                    'orderIdUTK' => $utkOrder['orderIdUTK'],
                    'date' => $utkOrder['orderDate']
                ]);
                return false;
            }
        }

        // создатель заявки
        $Creator = null;
        if (!empty($utkOrder['agentIdUTK'])) {
            $Creator = AccountRepository::getByUTKId($utkOrder['agentIdUTK']);
        }
        if (is_null($Creator)) {
            $this->setError(OrdersErrors::AGENCY_USER_NOT_FOUND, [
                'orderIdUTK' => $utkOrder['orderIdUTK'],
                'creatorId' => $utkOrder['agentIdUTK']
            ]);
            return false;
        } else {
            $Order->bindCreator($Creator);
        }

        // клиент
        $Agency = null;
        if (!empty($utkOrder['clientIdUTK'])) {
            $Agency = CompanyRepository::getByUTKId($utkOrder['clientIdUTK']);
        }
        if (is_null($Agency)) {
            $this->setError(OrdersErrors::AGENT_NOT_FOUND, [
                'orderIdUTK' => $utkOrder['orderIdUTK'],
                'agencyId' => $utkOrder['clientIdUTK']
            ]);
            return false;
        } else {
            try {
                $Order->bindAgency($Agency);
            } catch (KmpException $ke) {
                $this->setError($ke->getCode(), $ke->params);
                return false;
            } catch (Exception $e) {
                $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR, [
                    'msg' => $e->getMessage()
                ]);
                return false;
            }
        }

        // контракт
        $Contract = null;
        if (!empty($utkOrder['contractIdUTK'])) {
            $Contract = ContractRepository::getByUTKId($utkOrder['contractIdUTK']);
        }
        if (is_null($Contract)) {
            $this->setError(OrdersErrors::INCORRECT_INPUT_PARAM, [
                'msg' => 'Incorrect Contract ID',
                'orderIdUTK' => $utkOrder['orderIdUTK'],
                'contractId' => $utkOrder['contractIdUTK']
            ]);
            return false;
        } else {
            $Order->bindContract($Contract);
        }

        // признак архивной заявки
        $Order->setArchive(false);

        return true;
    }

    /**
     * Обновление модели заявки полученными от GPTS данными
     * @param OrderModel $Order объект заявки
     * @param array $gpOrder данные заявки от GPTS
     * @return bool результат операции
     */
    private function updateOrderWithGptsData(OrderModel &$Order, array $gpOrder)
    {
        if (!is_null($gpOrder['companyManager'])) {
            $CompanyManager = AccountRepository::getAccountById($gpOrder['companyManager']);
            if (!is_null($CompanyManager)) {
                $Order->bindCompanyManager($CompanyManager);
            }
        }

        if (!is_null($gpOrder['kmpManager'])) {
            $KmpManager = AccountRepository::getAccountById($gpOrder['kmpManager']);
            if (!is_null($KmpManager)) {
                $Order->bindKMPManager($KmpManager);
            }
        }
    }

    /*
    * Инициализация модели услуги
    * @param array $utkService данные услуги от УТК
    * @param OrderModel $Order объект заявки
    * @return OrdersServices|null объект услуги или null в случае ошибки
    */
    private function setService(array $utkService, OrderModel &$Order)
    {
        $Service = new OrdersServices();
        $orderId = $Order->orderId;

        if (!is_null($orderId)) {
            $isExistingService = false;

            if (!empty($utkService['serviceId'])) {
                $FoundService = OrdersServicesRepository::findById((int)$utkService['serviceId']);
                if (is_null($FoundService)) {
                    $this->setError(OrdersErrors::SERVICE_UTK_ID_INCORRECT, [
                        'orderId' => $orderId,
                        'serviceId' => $utkService['serviceId']
                    ]);
                    return null;
                }
                $Service = $FoundService;
                $isExistingService = true;
            } elseif (!empty($utkService['GPTSserviceID'])) {
                $FoundService = OrdersServicesRepository::findByGPTSId($utkService['GPTSserviceID']);
                if (!is_null($FoundService)) {
                    $Service = $FoundService;
                    $isExistingService = true;
                }
            }

            if ($isExistingService) {
                $presentUTKId = $Service->getServiceIDUTK();
                if (!empty($presentUTKId) && $presentUTKId !== $utkService['serviceIdUTK']) {
                    $this->setError(OrdersErrors::SERVICE_UTK_ID_INCORRECT, [
                        'orderId' => $orderId,
                        'presentServiceIdUTK' => $presentUTKId,
                        'receivedServiceIdUTK' => $utkService['serviceIdUTK']
                    ]);
                    return null;
                } else {
                    $Service->setServiceIDUTK($utkService['serviceIdUTK']);
                }
            } else {
                $FoundService = OrdersServicesRepository::findByUTKId($utkService['serviceIdUTK']);
                if (is_null($FoundService)) {
                    if ((bool)$utkService['online'] && empty($utkService['GPTSserviceID'])) {
                        $this->setError(OrdersErrors::SERVICE_ID_NOT_SET, [
                            'msg' => 'no gpts service ID',
                            'orderId' => $orderId,
                            'online' => true,
                            'GPTSserviceID' => $utkService['GPTSserviceID']
                        ]);
                        return null;
                    }
                } else {
                    $Service = $FoundService;
                    $isExistingService = true;
                }
            }

            if ($isExistingService && $Service->getOrderId() !== $orderId) {
                $this->setError(OrdersErrors::SERVICES_IDS_FROM_DIFFERENT_ORDERS, [
                    'serviceId' => $utkService['serviceId'],
                    'presentOrderId' => $FoundService->getOrderId(),
                    'receivedOrderId' => $orderId
                ]);
                return null;
            }

            $Service->setOrderId($orderId);
        }

        $Service->setServiceIDUTK($utkService['serviceIdUTK']);
        if (!empty($utkService['GPTSserviceID'])) {
            $Service->setServiceIDGP($utkService['GPTSserviceID']);
        }

        $Service->setServiceType((int)$utkService['serviceType']);

        return $Service;
    }

    /**
     * Обновление модели услуги полученными от УТК данными
     * @param OrdersServices $Service объект услуги
     * @param array $utkService данные услуги от УТК
     * @return bool результат операции
     */
    private function updateServiceWithUtkData(OrdersServices &$Service, array $utkService)
    {
        $isExistingService = !empty($Service->serviceId);

        $utkServiceStatusMapping = [
            1 => OrdersServices::STATUS_BOOKED,
            2 => OrdersServices::STATUS_VOIDED,
            3 => OrdersServices::STATUS_MANUAL
        ];

        if (!isset($utkServiceStatusMapping[(int)$utkService['status']])) {
            $this->setError(OrdersErrors::INCORRECT_SERVICE_STATUS, [
                'serviceIdUTK' => $utkService['serviceIdUTK'],
                'status' => $utkService['status']
            ]);
            return false;
        }

        $Service->setStatus($utkServiceStatusMapping[(int)$utkService['status']]);
        $Service->setServiceType((int)$utkService['serviceType']);
        $Service->setOffline(!(bool)$utkService['online']);

        $startDate = !empty($utkService['startDateTime']) ? strtotime($utkService['startDateTime']) : false;
        if ($startDate !== false) {
            $Service->setDateStart(date('Y-m-d H:i:s', $startDate));
        } else {
            if (!$isExistingService) {
                $this->setError(OrdersErrors::DATE_START_INCORRECT, [
                    'serviceIdUTK' => $utkService['serviceIdUTK'],
                    'date' => $utkService['startDateTime']
                ]);
                return false;
            }
        }

        $endDate = !empty($utkService['endDateTime']) ? strtotime($utkService['endDateTime']) : false;
        if ($endDate !== false) {
            $Service->setDateFinish(date('Y-m-d H:i:s', $endDate));
        } else {
            if (!$isExistingService) {
                $this->setError(OrdersErrors::DATE_END_INCORRECT, [
                    'serviceIdUTK' => $utkService['serviceIdUTK'],
                    'date' => $utkService['endDateTime']
                ]);
                return false;
            }
        }

        $Supplier = null;
        if (!empty($utkService['supplierId'])) {
            $Supplier = SupplierRepository::getById((int)$utkService['supplierId']);
        }
        if (is_null($Supplier) && !empty($utkService['supplierIdGPTS'])) {
            $Supplier = SupplierRepository::getByGPTSSupplierId((int)$utkService['supplierIdGPTS']);
        }
        if (is_null($Supplier) && !empty($utkService['supplierIdUTK'])) {
            $Supplier = SupplierRepository::getByUTKSupplierId($utkService['supplierIdUTK']);
        }
        if (!is_null($Supplier)) {
            $Service->bindSupplier($Supplier);
        }

        $City = null;
        if (!empty($utkService['cityIdGPTS'])) {
            $City = CityRepository::getByGPTSId($utkService['cityIdGPTS']);
        }
        if (is_null($City) && !empty($utkService['cityId'])) {
            $City = CityRepository::getByIATACode($utkService['cityId']);
        }
        if (!is_null($City)) {
            $Service->bindCity($City);
        }

        $CurrencyRates = CurrencyRates::getInstance();

        $Service->setSupplierPrice((float)$utkService['NetSum']);
        if ($utkService['NetCurrency'] == 'руб.') {
            $utkService['NetCurrency'] = 'RUB';
        }
        $supplierCurrency = $CurrencyRates->getIdByCode($utkService['NetCurrency']);
        if ($supplierCurrency === false) {
            $this->setError(OrdersErrors::CURRENCY_INCORRECT, [
                'serviceIdUTK' => $utkService['serviceIdUTK'],
                'NetCurrency' => $utkService['NetCurrency']
            ]);
            return false;
        }
        $Service->setSupplierCurrency($supplierCurrency);

        $Service->setKmpPrice((float)$utkService['SaleSum']);
        if ($utkService['SaleCurrency'] == 'руб.') {
            $utkService['SaleCurrency'] = 'RUB';
        }
        $saleCurrency = $CurrencyRates->getIdByCode($utkService['SaleCurrency']);
        if ($saleCurrency === false) {
            $this->setError(OrdersErrors::CURRENCY_INCORRECT, [
                'serviceIdUTK' => $utkService['serviceIdUTK'],
                'SaleCurrency' => $utkService['SaleCurrency']
            ]);
            return false;
        }
        $Service->setSaleCurrency($saleCurrency);

        if($utkService['CommisionSum'] < 0){
            $utkService['CommisionSum'] *= -1;
        }
        $Service->setAgencyProfit((float)$utkService['CommisionSum']);

        $Service->setSalePenalty((float)$utkService['SalePenalty']);
        if ($utkService['SalePenaltyCurr'] == 'руб.') {
            $utkService['SalePenaltyCurr'] = 'RUB';
        }
        $penaltyCurrency = $CurrencyRates->getIdByCode($utkService['SalePenaltyCurr']);
        if ($penaltyCurrency === false) {
            $this->setError(OrdersErrors::CURRENCY_INCORRECT, [
                'serviceIdUTK' => $utkService['serviceIdUTK'],
                'SalePenaltyCurr' => $utkService['SalePenaltyCurr']
            ]);
            return false;
        }
        $Service->setSalePenaltyCurrency($penaltyCurrency);

        $Service->setNetPenalty((float)$utkService['NetPenalty']);
        if ($utkService['NetPenaltyCurr'] == 'руб.') {
            $utkService['NetPenaltyCurr'] = 'RUB';
        }
        $penaltyCurrency = $CurrencyRates->getIdByCode($utkService['NetPenaltyCurr']);
        if ($penaltyCurrency === false) {
            $this->setError(OrdersErrors::CURRENCY_INCORRECT, [
                'serviceIdUTK' => $utkService['serviceIdUTK'],
                'NetPenaltyCurr' => $utkService['NetPenaltyCurr']
            ]);
            return false;
        }
        $Service->setNetPenaltyCurrency($penaltyCurrency);

        switch ($Service->getServiceType()) {
            case 1: //проживание
                if (!empty($utkService['ServiceDetails']['hotelIdKT'])) {
                    $Hotel = HotelInfoRepository::getHotelById((int)$utkService['ServiceDetails']['hotelIdKT']);
                } else if (!empty($utkService['ServiceDetails']['hotelId'])) {
                    $Hotel = HotelInfoRepository::getByUTKId((int)$utkService['ServiceDetails']['hotelId']);
                } else {
                    $this->setError(OrdersErrors::SERVICE_DETAILS_INCORRECT, [
                        'msg' => 'hotel ID not set',
                        'serviceUTKId' => $utkService['serviceIdUTK']
                    ]);
                    return false;
                }

                if (is_null($Hotel)) {
                    $this->setError(OrdersErrors::SERVICE_DETAILS_INCORRECT, [
                        'msg' => 'hotel ID not found',
                        'serviceUTKId' => $utkService['serviceIdUTK'],
                        'serviceDetails' => $utkService['ServiceDetails']
                    ]);
                    return false;
                }

                $Service->setServiceName(
                    $Hotel->getCity()->getName() . ', ' .
                    $Hotel->getHotelName() . ', ' .
                    $utkService['ServiceDetails']['mealName']);
                break;
            case 2: //авиа
                $Service->setServiceName(
                    hashtableval($utkService['ServiceDetails']['dateToName'], '') . ': ' .
                    hashtableval($utkService['ServiceDetails']['routeName'], '') . ', ' .
                    hashtableval($utkService['ServiceDetails']['ticketClassName'], '')
                );
                break;
            case 3: //Трансфер
                $Service->setServiceName(
                    hashtableval($utkService['ServiceDetails']['descriptionName'], '')
                );
                break;
            case 4: //Виза
                $Service->setServiceName(
                    hashtableval($utkService['ServiceDetails']['visaTypeName'], '') . ', ' .
                    hashtableval($utkService['ServiceDetails']['validTillName'], '')
                );
                break;
            case 5: //Аренда машины
                $Service->setServiceName(
                    hashtableval($utkService['ServiceDetails']['descriptionName'], '')
                );
                break;
            case 6: //Тур
                $Service->setServiceName(
                    hashtableval($utkService['ServiceDetails']['tourName'], '')
                );
                break;
            case 7: //Ж/д билет
                $Service->setServiceName(
                    hashtableval($utkService['ServiceDetails']['routeName'], '') . ', ' .
                    hashtableval($utkService['ServiceDetails']['ticketClassName'], '')
                );
                break;
            case 8: //Пакет
                break;
            case 9: //Страховка
                $Service->setServiceName(
                    hashtableval($utkService['ServiceDetails']['assuranceTypeName'], '') .
                    $Service->getDateStart() . ' - ' .
                    $Service->getDateEnd()
                );
                break;
            case 10: //Гид
                $Service->setServiceName(
                    hashtableval($utkService['ServiceDetails']['descriptionName'], '')
                );
                break;
            case 11: //Доп.услуга
                $Service->setServiceName(
                    hashtableval($utkService['ServiceDetails']['descriptionName'], '')
                );
                break;
            case 12: //Экскурсия
                $Service->setServiceName(
                    hashtableval($utkService['ServiceDetails']['descriptionName'], '')
                );
                break;
            default:
                $Service->setServiceName($utkService['serviceDescription']);
                break;
        }

        return true;
    }

    /**
     * Инициализация и заполнение модели оффера по данным от getGptsOrder
     * @param OrdersServices $Service - объект услуги
     * @param array $gpService - данные услуги из getGptsOrder
     * @return AviaOffer|HotelOffer|null - объект оффера или null в случае ошибки
     */
    private function setOfferFromGpts(OrdersServices &$Service, array $gpService)
    {

        $gpOfferData = $gpService['supplierServiceData'];
        $offerStructName = $this->serviceTypeStructures[$Service->ServiceType]['offerInfo'];
        $offerInfo = $gpOfferData[$offerStructName];

        $isExistingOffer = !empty($Service->ServiceID);

        switch ($Service->ServiceType) {
            case 1:
                $offerInfo['adult'] = $offerInfo['adults'];
                $offerInfo['child'] = $offerInfo['children'];
                break;
            case 2:
                // тут со структурой все нормально
                break;
        }

        try {
            if ($isExistingOffer) {
                $Offer = $Service->getOffer();
            } else {
                $Offer = OfferCreator::createFromArray($offerInfo, $Service->getServiceNameByType($Service->ServiceType));
            }
        } catch (Exception $e) {
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR, [
                'error' => $e->getMessage(),
                'offerInfo' => $offerInfo,
                'Service' => $Service->getSLOrderService()
            ]);
            return null;
        }
        switch ($Service->ServiceType) {
            case 1: // Hotel
                $Offer->setBookData([
                    'hotelReservation' => [
                        'reservationNumber' => $gpOfferData['hotelReservation']['reservationNumber'],
                        'gateId' => 5,  // тк оффер запрашивали строго в ГПТС, здесь можно поставить 5ку
                        'engine' => $gpOfferData['engineData']
                    ]
                ]);
                break;
            case 2: // Avia
                $aviaReservation = $gpOfferData['aviaReservations'][0];
                $Offer->importBookData([
                    'PNR' => $aviaReservation['PNR'],
                    'segments' => $aviaReservation['segments'],
                    'supplierCode' => $aviaReservation['supplierCode'],
                    'status' => $aviaReservation['status'],
                    'offerKey' => $gpOfferData['engineData']['offerKey'],
                    'gateId' => $gpOfferData['engineData']['gateId'],
                    'service_ref' => $gpOfferData['engineData']['data']['GPTS_service_ref'],
                    'order_ref' => $gpOfferData['engineData']['data']['GPTS_order_ref']
                ]);
                break;
        }
        // плановые штрафы
        $Offer->clearCancelPenalties();
        $Offer->importCancelPenalties($gpOfferData['cancelPenalties']);

        return $Offer;
    }

    /**
     * Обновление модели услуги полученными от GPTS данными
     * @param OrdersServices $Service объект услуги
     * @param array $gpService данные услуги от GPTS
     * @param AviaOffer|HotelOffer $Offer объект оффера
     * @return bool результат операции
     */
    private function updateServiceWithGptsData(OrdersServices &$Service, array $gpService, &$Offer)
    {
        $Service->fromOffer($Offer);

        /**
         * нельзя обновлять с понижением статуса
         */
        if ($Service->getStatus() < (int)$gpService['status']) {
            $Service->setStatus((int)$gpService['status']);
        }

        $Service->setOffline(false);

        $PriceOffers = $Offer->getPriceOffers();

        if (count($PriceOffers)) {
            foreach ($PriceOffers as $PriceOffer) {
                $Service->fromPriceOffer($PriceOffer);
            }
        } else {
            $this->setError(OrdersErrors::CANNOT_CREATE_SERVICE, ['msg' => 'no price offers']);
            return false;
        }

        return true;
    }

    /*
    * Инициализация ценовых параметров услуги
    * @param array $utkService данные услуги от УТК
    * @return OrderServicePrice[] массив ценовых параметров (для поставщика и клиента)
    */
    private function setServicePricesFromUtk(array $utkService)
    {
        $SupplierServicePrice = new OrderServicePrice();
        $SupplierServicePrice->setType('supplier');
        $SupplierServicePrice->fromArray([
            'amountNetto' => $utkService['NetSum'],
            'amountBrutto' => $utkService['NetSum'],
            'currency' => ($utkService['NetCurrency'] == 'руб.') ? 'RUB' : $utkService['NetCurrency'],
            'commission' => [
                'amount' => 0,
                'percent' => 0,
                'currency' => ($utkService['NetCurrency'] == 'руб.') ? 'RUB' : $utkService['NetCurrency'],
            ],
        ]);

        $ClientServicePrice = new OrderServicePrice();
        $ClientServicePrice->setType('client');
        $ClientServicePrice->fromArray([
            'amountNetto' => $utkService['SaleSum'],
            'amountBrutto' => $utkService['SaleSum'],
            'currency' => ($utkService['SaleCurrency'] == 'руб.') ? 'RUB' : $utkService['SaleCurrency'],
            'commission' => [
                'amount' => (float)$utkService['CommisionSum'],
                'percent' => 0,
                'currency' => ($utkService['SaleCurrency'] == 'руб.') ? 'RUB' : $utkService['SaleCurrency'],
            ],
        ]);

        return [
            $SupplierServicePrice,
            $ClientServicePrice
        ];
    }

    /**
     * Инициализация модели туриста в заявке по данным УТК
     * @param array $utkTourist данные туриста от УТК
     * @param OrderModel $Order объект заявки
     * @return OrderTourist|null объект туриста или null в случае ошибки
     */
    private function setOrderTouristFromUtk(array $utkTourist, OrderModel &$Order)
    {
        $Tourist = null;
        $orderId = $Order->getOrderId();

        if (!empty($utkTourist['personId'])) {
            $Tourist = TouristRepository::getTouristById((int)$utkTourist['personId']);
        }

        if (is_null($Tourist)) {
            $Tourist = TouristRepository::getTouristByUTKId($utkTourist['personIdUTK']);
        }

        if (is_null($Tourist)) {
            $Tourist = new Tourist();
            $Tourist->setUTKTouristID($utkTourist['personIdUTK']);

            $Tourist->setFIO(
                $utkTourist['firstName'],
                $utkTourist['lastName'],
                $utkTourist['middleName']
            );

            $Tourist->setSex((int)$utkTourist['sex']);

            $birthdate = false;
            if (!empty($utkTourist['dateOfBirth'])) {
                $birthdate = DateTime::createFromFormat('Y-m-d\TH:i:s', $utkTourist['dateOfBirth']);
            }
            if ($birthdate !== false && $birthdate > DateTime::createFromFormat('Y-m-d', '1900-01-01')) {
                $Tourist->setBirthdate($birthdate->format('Y-m-d'));
            }

            $Tourist->save();
        }

        if (!is_null($orderId)) {
            $OrderTourist = OrderTouristRepository::getByOrderAndTourist($orderId, $Tourist->getTouristIDbase());
        }
        if (!isset($OrderTourist)) {
            $OrderTourist = new OrderTourist();
            $OrderTourist->bindTourist($Tourist);
        }

        $OrderTourist->setTourLeader((bool)$utkTourist['isTourLead']);

        return $OrderTourist;
    }

    /**
     * Инициализация модели туриста в заявке по данным GPTS
     * @param array $gpTourist данные туриста от GPTS
     * @param OrderModel $Order объект заявки
     * @return OrderTourist|null объект туриста или null в случае ошибки
     */
    private function setOrderTouristFromGpts(array $gpTourist, OrderModel &$Order)
    {
        $Tourist = null;
        $orderId = $Order->getOrderId();

        $Tourist = TouristRepository::getTouristByGPTSId($gpTourist['touristIdGP']);

        if (is_null($Tourist)) {
            $Tourist = new Tourist();
            $Tourist->setGPTSTouristID($gpTourist['touristIdGP']);

            $Tourist->setFIO(
                $gpTourist['firstName'],
                $gpTourist['lastName'],
                $gpTourist['middleName']
            );

            $Tourist->setSex((int)$gpTourist['maleFemale']);

            $birthdate = false;
            if (!empty($gpTourist['dateOfBirth'])) {
                $birthdate = DateTime::createFromFormat('Y-m-d H:i', $gpTourist['dateOfBirth']);
            } else {
                /* очень грязный костыль, санкционированный Андреем */
                $birthdate = DateTime::createFromFormat('Y-m-d', '1990-01-01');
            }
            if ($birthdate !== false && $birthdate > DateTime::createFromFormat('Y-m-d', '1900-01-01')) {
                $Tourist->setBirthdate($birthdate->format('Y-m-d'));
            }

            $Tourist->setEmail($gpTourist['email']);
            $Tourist->setPhone($gpTourist['phone']);

            $Tourist->save();

            $Citizenship = CountryRepository::getById($gpTourist['touristdocs']['citizenshipId']);

            $TouristDocument = new TouristDocument();
            $TouristDocument->fromArray([
                'firstName' => $gpTourist['touristdocs']['firstName'],
                'lastName' => $gpTourist['touristdocs']['lastName'],
                'middleName' => $gpTourist['touristdocs']['middleName'],
                'serialNumber' => $gpTourist['touristdocs']['docSerial'],
                'number' => $gpTourist['touristdocs']['docNumber'],
                'citizenship' => $Citizenship->CountryCode,
                'documentType' => $gpTourist['touristdocs']['docType'],
                'expiryDate' => $gpTourist['touristdocs']['docExpiryDate']
            ]);
            $TouristDocument->bindTourist($Tourist);
            $TouristDocument->save();
        }

        if (!is_null($orderId)) {
            $OrderTourist = OrderTouristRepository::getByOrderAndTourist($orderId, $Tourist->getTouristIDbase());
        }
        if (!isset($OrderTourist)) {
            $OrderTourist = new OrderTourist();
            $OrderTourist->bindTourist($Tourist);
        }

        return $OrderTourist;
    }

}