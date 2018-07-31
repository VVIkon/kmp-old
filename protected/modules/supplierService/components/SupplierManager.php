<?php

/** класс, управляющий сервисов поставщиков */
class SupplierManager
{
    /**  @var KModule Используется для хранения ссылки на текущий модуль */
    private $module;

    /** @var string namespace для записи логов */
    private $namespace;

    /** @var int Код ошибки */
    private $errorCode;

    /** @var SupplierManagerValidator Валидатор менеджера поставщиков */
    private $validator;

    public function __construct()
    {
        $this->module = YII::app()->getModule('supplierService');
        $this->namespace = $this->module->getConfig('log_namespace');
        $this->validator = new SupplierManagerValidator();
    }

    /**
     * Выполнение действия сервиса поставщиков
     * @param string $action действие для выполнения
     * @param mixed[] $params структура с параметрами
     * @return mixed[]|false структура ответа или false в случае ошибки
     * @throws KmpException
     * @throws Exception
     */
    public function runAction($action, $params)
    {
        try {
            switch ($action) {
                case 'getOffer':
                    if ($this->validator->checkGetOffer($params)) {
                        $engine = SupplierFactory::getSupplierEngine($params['supplierId']);
                        $response = $engine->getOffer($params);
                        return $response;
                    } else {
                        return false;
                    }
                    break;
                case 'getCancelRules':
                    if ($this->validator->checkGetCancelRules($params)) {
                        $engine = SupplierFactory::getSupplierEngine($params['gateId']);

                        $response = $engine->getCancelRules($params);
                        if (is_array($response)) {
                            return $response;
                        } else {
                            $this->errorCode = $response;
                            return false;
                        }
                    } else {
                        return false;
                    }
                    break;
                case 'supplierServiceCancel':
                    if ($this->validator->checkCancelService($params)) {
                        $engine = SupplierFactory::getSupplierEngine($params['gateId']);
                        $response = $engine->serviceCancel($params);

                        if (is_array($response)) {
                            return $response;
                        } else {
                            $this->errorCode = $response;
                            return false;
                        }
                    } else {
                        return false;
                    }
                    break;
                case 'supplierModifyService':
                    if ($this->validator->checkModifyService($params)) {
                        // проверим доступно ли изменение брони по ID поставщика
                        $RefSuppliers = RefSuppliers::model()->findByPk($params['supplierId']);

                        if (is_null($RefSuppliers)) {
                            $this->errorCode = SupplierErrors::SUPPLIER_NOT_FOUND;
                            return false;
                        }

                        if (!$RefSuppliers->getSupportsModification()) {
                            $this->errorCode = SupplierErrors::SUPPLIER_DOES_NOT_SUPPORT_MODIFICATION;
                            return false;
                        }

                        $engine = SupplierFactory::getSupplierEngine($params['gateId']);

                        try {
                            $response = $engine->serviceModify($params);
                            return $response;
                        } catch (Exception $e) {
                            $this->errorCode = $e->getCode();
                            return false;
                        }
                    } else {
                        return false;
                    }
                    break;
                case 'serviceBooking':
                    if ($this->validator->checkServiceBooking($params)) {
                        $engine = SupplierFactory::getSupplierEngine($params['supplierId']);
                        $response = $engine->serviceBooking($params);
                        return $response;
                    } else {
                        return false;
                    }
                    break;
                case 'issueTickets':
                    if ($this->validator->checkIssueTickets($params)) {
                        $supplierId = isset($params['bookData']['pnrData']['engine']['type'])
                            ? $params['bookData']['pnrData']['engine']['type']
//                      todo реализовать получение поставщика из структуры сегментов после
//                      todo реализации мультисегментного перелёта
                            : $params['bookData']['segments']['engine']['type'];
                        $engine = SupplierFactory::getSupplierEngine($supplierId);
                        $response = $engine->issueTickets($params);

                        return $response;
                    } else {
                        return false;
                    }
                    break;
                case 'getEtickets':
                    if ($this->validator->checkGetEtickets($params)) {
                        $eTickets = [];

                        foreach ($params['tickets'] as $ticket) {
                            $SupplierService = SupplierServiceFactory::getSupplierServiceByType($params['serviceType']);
                            $SupplierService->initFromTicket($ticket);
                            $engine = SupplierFactory::getSupplierEngine($SupplierService->getGateId());

                            $ticket['serviceType'] = $params['serviceType'];

                            $eTickets[] = $engine->getEtickets($ticket);
                        }
                        return $eTickets;
                    } else {
                        return false;
                    }
                    break;
                case 'getFareRule':
                    if ($this->validator->checkGetFareRule($params)) {
                        $engine = SupplierFactory::getSupplierEngine(SupplierFactory::GPTS_ENGINE);
                        $fareRule = $engine->getFareRule($params);
                        LogHelper::logExt(get_class($this), __METHOD__, 'getFareRule', json_encode($params), $fareRule, 'info', $this->namespace . 'info');
                        return $fareRule;
                    } else {
                        return false;
                    }
                    break;
                case 'getServiceStatus':
                    if ($this->validator->checkGetServiceStatus($params)) {
                        $engine = SupplierFactory::getSupplierEngine(SupplierFactory::GPTS_ENGINE);
                        $response = $engine->getServiceStatus($params);
                        return $response;
                    } else {
                        return false;
                    }
                    break;
                case 'supplierGetOrder':
                    $suplierOrders = [];
                    if ($this->validator->checkSupplierGetOrder($params)) {
                        $inServices = $params['services'];
                        $inParams = [];
                        $order = [];

                        foreach ($inServices as $inService) {
                            $gateId = StdLib::nvl($inService['engineData']['gateId']);
                            $GPTS_order_ref = StdLib::nvl($inService['engineData']['data']['GPTS_order_ref']);
                            $serviceID = StdLib::nvl($inService['serviceID']);
                            $serviceType = StdLib::nvl($inService['serviceType']);
                            $GPTS_service_ref = StdLib::nvl($inService['engineData']['data']['GPTS_service_ref']);
                            // если $inParams - пуст, создаём, заполняем
                            if (StdLib::nvl($inParams['gateId'], 0) == 0 && StdLib::nvl($inParams['GPTS_order_ref'], 0) == 0) {
                                $order = [
                                    'gateId' => $gateId,
                                    'GPTS_order_ref' => $GPTS_order_ref
                                ];
                                $order['service'][] = [
                                    'serviceID' => $serviceID,
                                    'serviceType' => $serviceType,
                                    'GPTS_service_ref' => $GPTS_service_ref
                                ];
                                // Если найдены gateId и GPTS_order_ref, заполняем его сервис
                                // !!!! Все остальные пары (gateId и GPTS_order_ref) не включаются!!!!
                            } elseif ($inParams['gateId'] == $gateId && $inParams['GPTS_order_ref'] == $GPTS_order_ref) {
                                $servs = StdLib::nvl($order['service'], []);
                                $flag = false; // если добавлять нечего
                                foreach ($servs as $serv) {
                                    $flag = true;
                                    if (StdLib::nvl($serv['serviceID'],0) == $serviceID && StdLib::nvl($serv['serviceType'],0) == $serviceType && StdLib::nvl($serv['GPTS_service_ref'],0) == $GPTS_service_ref) {
                                        $flag = false;
                                    }
                                }
                                if ($flag == true) { // если нет такого сервиса у заявке
                                    $order['service'][] = [
                                        'serviceID' => $serviceID,
                                        'serviceType' => $serviceType,
                                        'GPTS_service_ref' => $GPTS_service_ref
                                    ];
                                }
                            }
                            $inParams = $order;
                            $inParams['addServices'] = StdLib::nvl($inService['addServices']);
                        }
                        $engine = SupplierFactory::getSupplierEngine($inParams['gateId']);
                        $suplierOrders = $engine->getSupplierGetOrder($inParams);

                    }
                    LogHelper::logExt(get_class($this), __METHOD__, 'supplierGetOrder', '', ['inputParams' => json_encode($params), 'outputParams' => $suplierOrders], 'info', $this->namespace . 'info');
                    return ['supplierOrder' => $suplierOrders];
                case 'setServiceData':
                    $resp = [];
                    if ($this->validator->checkSetServiceDate($params)) {
                        $engineData = $params['engineData'];
                        $engine = SupplierFactory::getSupplierEngine($engineData['gateId']);
                        $resp = $engine->setServiceData($params);
                    }
                    LogHelper::logExt(get_class($this), __METHOD__, 'setServiceData', json_encode($params), $resp, 'info', $this->namespace . 'info');
                    return $resp;
                default:
                    return false;
            }
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
            $this->errorCode = $ke->getCode();
//            return false;
            throw $ke;
        } catch (Exception $e) {
            LogHelper::logExt(
                __CLASS__, __FUNCTION__, 'supplier manager error', $e->getMessage(),
                ['msg' => $e->getMessage()],
                LogHelper::MESSAGE_TYPE_ERROR,
                'system.supplierservice.errors'
            );
            throw $e;
        }
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->errorCode;
    }
}