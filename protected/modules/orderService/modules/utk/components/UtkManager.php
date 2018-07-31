<?php

/**
 * Class UtkManager
 * Выпоняет операции двухсторонней синхронизации КТ и УТК
 */
class UtkManager
{

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
     * Используется для хранения ссылки на текущий модуль
     * @var object
     */
    private $module;

    /**
     * Используется для хранения ссылки на модуль заявок
     * @var
     */
    private $orderModule;

    /**
     * Клиент для выполнения запросов к УТК
     * @var
     */
    private $utkClient;


    public function __construct($module, $orderModule)
    {

        if (empty($module) || empty($orderModule)) {
            return false;
        }

        $this->module = $module;
        $this->orderModule = $orderModule;

        $this->namespace = $this->orderModule->getConfig('log_namespace');
        $this->utkClient = new UtkClient($this->module);
    }

    /**
     * Создание счёта по информации пришедшей от УТК
     * @param $params
     * @return array|bool
     */
    public function setInvoiceInfo($params)
    {
        if (!$this->checkPayStartBySyncConditions($params)) {
            return false;
        }

        $params['status'] = StatusesMapperHelper::getKtByUTKStatus(
            $params['status'],
            StatusesMapperHelper::STATUS_TYPE_INVOICE,
            ''
        );

        $ordersMgr = $this->orderModule->OrdersMgr($this->orderModule);

        try {
            $result = $ordersMgr->processInvoiceInfo($params);
        } catch (KmpException $ke) {
            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->orderModule->getCxtName($ke->class, $ke->method),
                $this->orderModule->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $ke->getCode();
            return false;
        }

        if (empty($result)) {
            $this->errorCode = $ordersMgr->getLastError();
            return false;
        }

        $this->setOrderStatus($params['orderIdUTK'], OrderForm::ORDER_STATUS_W_PAID);

        $invoiceInfo = InvoicesForm::getInvoiceById($result['invoiceId']);
        $invoiceSvcIds = InvoiceServiceForm::getInvoiceServicesIds($result['invoiceId']);

        $services = [];

        foreach ($invoiceSvcIds as $invoiceSvcId) {
            $service = new Service();
            $service->load($invoiceSvcId);

            foreach ($params['serviceTableShort'] as $invoiceServiceInfo) {
                if ($invoiceServiceInfo['serviceIdUTK'] == $service->serviceUtkId) {
                    $paidAmount = InvoiceServiceForm::getServicePaidSumInCurrencyId($service->serviceID, $service->supplierCurrency);
                    $priceInCurrency = CurrencyRates::getInstance()->calculateInCurrencyByIds($service->saleSum, $service->saleCurrency, $service->supplierCurrency);

                    $priceInCurrency = number_format($priceInCurrency, 2, '.', '');
                    $paidAmount = number_format($paidAmount, 2, '.', '');

                    if ($paidAmount >= $priceInCurrency) {
                        $paymentStatus = InvoiceForm::STATUS_PAYED_COMPLETELY;
                    } else {
                        $paymentStatus = InvoiceForm::STATUS_PAYED_PARTIAL;
                    }

                    $services[] = [
                        'serviceId' => $service->serviceID,
                        'servicePaid' => $paymentStatus
                    ];
                }
            }
        }

        $invoiceChangedParams = [
            'orderId' => $params['orderId'],
            'invoice' => $invoiceInfo['InvoiceID'],
            'invoiceState' => $invoiceInfo['Status'],
            'services' => $services,
        ];

        $this->invoiceChanged($invoiceChangedParams);

        return ['invoiceId' => $result['invoiceId']];
    }

    /**
     * Проверка входящих параметров для создания или обновления счёта
     * @param $params
     * @return bool
     */
    private function checkPayStartBySyncConditions($params)
    {
        $utkValidator = $this->module->UtkValidator($this->module);
        if (!$utkValidator->checkUTKInvoiceParams($params)) {
            $this->errorCode = $utkValidator->getLastError();
            return false;
        }

        if ($utkValidator->checkInvoiceExists($params)) {
            if (!$utkValidator->checkUTKInvoiceUpdateParams($params)) {
                $this->errorCode = $utkValidator->getLastError();
                return false;
            }
        } else {
            if (!$utkValidator->checkUTKInvoiceCreateParams($params)) {
                $this->errorCode = $utkValidator->getLastError();
                return false;
            }
        }

        return true;
    }

    /**
     * Установка состояния заявки после обновления информации по счёту заявки
     * @param $params
     */
    public function invoiceChanged($params)
    {
        $orderWfMgr = $this->module->OrderWorkflowManager();

        if(count($params['services'])) {
            if ($params['invoiceState'] == UtkInvoicePaidType::PAID_TYPE_PART_PAID ||
                $params['invoiceState'] == UtkInvoicePaidType::PAID_TYPE_PAID
            ) {
                $payFinishParams = [
                    'action' => 'PayFinish',
                    'orderId' => $params['orderId'],
                    'actionParams' => [
                        'services' => $params['services']
                    ],
                ];

                $result = $orderWfMgr->runAction($payFinishParams);
            }
        }
    }

    /**
     * Установка статуса заявки
     */
    public function setOrderStatus($orderUtkId, $status)
    {

        if (empty($status)) {
            return false;
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $order = $orderForm->getOrderByUTKId($orderUtkId);

        $order['Status'] = $status;
        $orderForm->setParamsMapping([

            'OrderID' => 'orderId',
            'OrderID_UTK' => 'orderUtkId',
            'OrderDate' => 'orderDate',
            'Status' => 'status',
            'TouristID' => 'touristId',
            'AgentID' => 'agencyId',
            'UserID' => 'agencyUserId',
            'Archive' => 'archive',
            'DOLC' => 'dolc',
            'VIP' => 'vip',
            'ContractID' => 'contractId'
        ]);

        $orderForm->setAttributes($order);
        $orderForm->updateOrder();
        return true;
    }

    public function payFinishBySync($params)
    {

        if (!$this->checkPayFinishBySyncConditions($params)) {
            return false;
        }

        $ordersMgr = $this->orderModule->OrdersMgr($this->orderModule);

        $paymentInfo = $ordersMgr->processPaymentInfo($params);

        if (!$paymentInfo) {
            $this->errorCode = $ordersMgr->getLastError();
            return false;
        }

        $this->setOrderStatus($params['orderIdUTK'], OrderForm::ORDER_STATUS_PAID);

        return ['paymentId' => $paymentInfo['paymentId']];
    }

    private function checkPayFinishBySyncConditions($params)
    {

        $utkValidator = $this->orderModule->UtkValidator($this->orderModule);

        if (!$utkValidator->checkUTKPaymentParams($params)) {
            $this->errorCode = $utkValidator->getLastError();
            return false;
        }

        /*if ($utkValidator->checkInvoiceExists($params)) {
            if (!$utkValidator->checkUTKInvoiceUpdateParams($params)) {
                $this->errorCode = $utkValidator->getLastError();
                return false;
            }
        } else {
            if (!$utkValidator->checkUTKInvoiceCreateParams($params)) {
                $this->errorCode = $utkValidator->getLastError();
                return false;
            }
        }*/

        return true;
    }

    /**
     * Отправка заявки в УТК
     * @param $params
     */
    public function exportOrderToUTK($orderId)
    {
        $utkOrder = new UtkOrder();
        $utkOrder->load($orderId);
        $utkOrderParams = $utkOrder->toArray();

//        var_dump($utkOrderParams);
//        exit;

        $this->utkClient->makeRestRequest('order', $utkOrderParams);
        return true;
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
