<?php

/**
 * Class UtkOrder
 * Реализует функциональность работы с данными заявки для УТК
 */
class UtkOrder extends KFormModel
{
    const STATUS_UNDEFINED = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_CLOSED = 2;
    const STATUS_ANNULED = 3;
    const STATUS_STAND_BY = 4;

    /** @var int Идентифкатор заявки */
    public $orderId;

    /**
     * @var string номер заявки
     */
    public $orderNumber;

    /** @var string Идентифкатор заявки в УТК */
    public $orderIdUtk;

    /** @var string Идентифкатор заявки в GPTS */
    public $orderIdGpts;

    /** @var int Статус заявки */
    public $status;

    /** @var int признак VIP заявки */
    public $vip;

    /** @var string дата заявки */
    public $orderDate;

    /** @var string ид клиента КТ */
    public $clientId;

    /** @var string ид клиента УТК */
    public $clientIdUtk;

    /**  @var string номер агентского договора в КТ */
    public $contractId;

    /**  @var string номер агентского договора в УТК */
    public $contractIdUtk;

    /** @var string ид агентства КТ */
    public $agentCompanyId;

    /** @var string ид агентства УТК */
    public $agentCompanyIdUtk;

    /** @var string ид пользователя агентства КТ */
    public $agentId;

    /** @var string ид пользователя агентства УТК */
    public $agentIdUtk;

    /** @var Service[] */
    public $services;

    /** @var UtkServiceTourist[] ид пользователя агентства УТК */
    public $tourists;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules()
    {
        return [
            ['orderId, orderNumber, orderIdUtk, orderIdGpts, status, vip, orderDate, clientId, clientIdUtk, contractId,
             contractId, contractIdUtk, agentCompanyId, agentCompanyIdUtk, agentId, agentIdUtk,
             services, tourists', 'safe'
            ]
        ];
    }

    /**
     * Инициализация свойств заявки
     * @param $orderId
     * @return bool
     */
    public function load($orderId)
    {
        $orderInfo = $this->getOrderInfo($orderId);

        $tourist = new TouristForm('');
        $touristsInfo = TouristForm::getTouristsByOrderId($orderId);
        foreach ($touristsInfo as $touristInfo) {
            if ($touristInfo['TourLeader']) {
                $tourist->loadTouristByID($touristInfo['TouristID']);
            }
        }

        $agency = $this->getAgency($orderInfo['AgentID']);
        $contractInfo = $this->getAgencyContractInfo($orderInfo['ContractID']);
        $agencyUser = $this->getAgencyUser($orderInfo['CompanyManager']);

        $this->loadServices($orderId);
        $this->loadTourists($orderId);

        $this->setAttributes([
            'orderId' => $orderInfo['OrderID'],
            'orderNumber' => $orderInfo['orderNumber'],
            'orderIdUtk' => ($orderInfo['OrderID_UTK']) ? $orderInfo['OrderID_UTK'] : 0,
            'orderIdGpts' => ($orderInfo['OrderID_GP']) ? $orderInfo['OrderID_GP'] : 0,
            'status' => StatusesMapperHelper::getUtkByKtStatus(
                $orderInfo['Status'],
                StatusesMapperHelper::STATUS_TYPE_ORDER
            ),
            'vip' => ($orderInfo['VIP']) ? $orderInfo['VIP'] : 0,
            'orderDate' => $orderInfo['OrderDate'],
            'clientId' => $agency['AgentID'],
            'clientIdUtk' => $agency['AgentID_UTK'],
            'contractId' => $contractInfo['ContractID'],
            'contractIdUtk' => $contractInfo['ContractID_UTK'],
            'agentCompanyId' => $orderInfo['AgentID'],
            'agentCompanyIdUtk' => $agency['AgentID_UTK'],
            'agentId' => $orderInfo['CompanyManager'],
            'agentIdUtk' => $agencyUser->userUTKId,
        ]);
    }

    /**
     * Загрузить данные сервисов заявки
     * @param $orderId int номер заявки
     * @return array
     */
    protected function loadServices($orderId)
    {
        $order = OrderForm::createInstance('');
        $orderInfo = $order->getOrderById($orderId);

        $servicesInfo = OrderSearchForm::getOrdersServices([$orderId]);

        if (empty($servicesInfo)) {
            return [];
        }
        $services = [];

        foreach ($servicesInfo as $serviceInfo) {

            $utkService = new UtkOrderService();
            $utkService->load($serviceInfo['serviceID']);

            $services[] = $utkService;
        }

        $this->services = $services;
    }

    /**
     * Загрузить данные туристов заявки
     * @param $orderId
     * @return array
     */
    protected function loadTourists($orderId)
    {
        $order = OrderForm::createInstance('');

        $servicesInfo = OrderSearchForm::getOrdersServices($orderId);

        $servicesIds = [];
        foreach ($servicesInfo as $serviceInfo) {
            $servicesIds[] = $serviceInfo['serviceID'];
        }
        $touristsInfo = TouristForm::getServicesTourists($servicesIds);

        if (empty($touristsInfo)) {
            return [];
        }
        $tourists = [];
        foreach ($touristsInfo as $touristInfo) {
            $utkTourist = new UtkServiceTourist();
            $utkTourist->load($touristInfo['TouristID'], $touristInfo['ServiceID']);
            $tourists[] = $utkTourist;
        }
        $this->tourists = $tourists;
    }

    /**
     * Получить представление услуг заявки в виде массива
     * @return array
     */
    protected function getServicesInfo()
    {
        $servicesInfo = [];

        if (empty($this->services)) {
            return $servicesInfo;
        }

        foreach ($this->services as $service) {
            $servicesInfo[] = $service->toArray();
        }
        return $servicesInfo;
    }

    /**
     * Получить представление туристов с привязкой к услугам заявки в виде массива
     * @return array
     */
    protected function getTouristsInfo()
    {
        $touristsInfo = [];

        if (empty($this->tourists)) {
            return $touristsInfo;
        }

        foreach ($this->tourists as $tourist) {
            $touristsInfo[] = $tourist->toArray();
        }
        return $touristsInfo;
    }

    /**
     * Получение свойств объекта в виде массива
     * @return array
     */
    public function toArray()
    {

        return [
            'orderId' => $this->orderId,
            'orderNumber' => $this->orderNumber,
            'orderIdUTK' => ($this->orderIdUtk) ? $this->orderIdUtk : null,
            'GPTSorderId' => (integer)$this->orderIdGpts,
            'status' => (integer)$this->status,
            'VIP' => (integer)$this->vip,
            'orderDate' => UtkDateTime::getUtkDate($this->orderDate),
            'clientId' => $this->clientId,
            'clientIdUTK' => $this->clientIdUtk,
            'clientCompanyName' => $this->agentCompanyId,
            'contractId' => $this->contractId,
            'contractIdUTK' => $this->contractIdUtk,
            'agentId' => $this->agentId,
            'agentIdUTK' => $this->agentIdUtk,
            'agentCompanyId' => $this->agentCompanyId,
            'agentCompanyIdUTK' => $this->agentCompanyIdUtk,
            'Services' => $this->getServicesInfo(),
            'Tourists' => $this->getTouristsInfo()
        ];
    }

    /**
     * Получить информацию об агентстве
     * @param $agencyId
     */
    private function getAgency($agencyId)
    {
        $agencyForm = new AgentForm('');

        $agency = $agencyForm->getAgencyByID($agencyId);
        if (empty($agency)) {
            throw new KmpException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::AGENT_NOT_FOUND,
                [
                    'agentCompanyId' => $agencyId['AgentID'],
                ]
            );
        }

        return $agency;
    }

    /**
     * Получить информацию о заявке
     * @param $orderId
     */
    private function getOrderInfo($orderId)
    {
        $order = OrderForm::createInstance('');
        $orderInfo = $order->getOrderById($orderId);
        if (empty($orderInfo)) {
            throw new KmpException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::ORDER_NOT_FOUND,
                [
                    'orderId' => $orderId,
                ]
            );
        }
        return $orderInfo;
    }

    /**
     * Получить информацию о контракте с агентством
     * @param $contractId
     */
    private function getAgencyContractInfo($contractId)
    {
        $contractForm = new AgencyContractForm('');
        $contractInfo = $contractForm->getContractById($contractId);
        if (empty($contractInfo)) {
            throw new KmpException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::AGENCY_CONTRACT_NOT_FOUND,
                [
                    'contractId' => $contractId,
                ]
            );
        }
        return $contractInfo;
    }

    /**
     * Получение пользователя агентства
     * @param $userId
     */
    private function getAgencyUser($userId)
    {
        $agencyUserForm = new AgencyUserForm('');
        $agencyUser = $agencyUserForm->getUserById($userId);
        if (empty($agencyUser)) {
            throw new KmpException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::AGENCY_USER_NOT_FOUND,
                [
                    'contractId' => $userId,
                ]
            );
        }

        return $agencyUser;
    }
}
