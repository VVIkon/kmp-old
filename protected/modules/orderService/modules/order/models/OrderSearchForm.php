<?php

/**
 * Class OrderSearchForm
 * Реализует функциональность поиска заявок
 */
class OrderSearchForm extends CFormModel
{
    /** @var int ID заявки */
    public $orderId;

    /** @var string номер заявки */
    public $orderNumber;

    /** @var int Статус заявки */
    public $orderStatus;

    /** @var int Смещение в списке заявок */
    public $offset;

    /** @var int Число заявок из списка */
    public $limit;

    /** @var string Фамилия туриста */
    public $touristName;

    /** @var string Наименование страны */
    public $countryName;

    /** @var string Наименование города */
    public $cityName;

    /** @var string Фамилия менеджера, сформировавшего заявку */
    public $managerName;

    /** @var int ID компании клиента заявки */
    public $clientId;

    /** @var bool Признак того, что заявка в архиве */
    public $archived;

    /** @var string Фильтр по дате начала заявки - после указанной даты */
    public $startDate;

    /**  @var string Фильтр по дате начала заявки - до указанной даты */
    public $finishDate;

    /** @var string Дата модификации (от) */
    public $modificationDateFrom;

    /** @var string Дата модификации (до) */
    public $modificationDateTo;

    /** @var bool|null Фильтр по типу заявки (оффлайн/онлайн) */
    private $offline;

    /** @var array Параметры для фильтрации */
    private $values;

    /** @var array Список полей для сортировки */
    private $sortFields;

    /** @var string Направление сортировки */
    private $sortDir;

    /** @var string Ошибки */
    private $errors;

    /**
     * Алиасы для запроса полей из таблицы
     */
    private $fieldsAliases = [
        'dateStart' => 'startdate',
        'touristName' => 'touristLastName',
        'agentCompany' => 'agentCompany',
        'countryName' => 'country',
        'cityName' => 'city',
        'status' => 'status',
        'lastChangeDate' => 'DOLC',
        'offline' => 'on_off',
        'orderNumber' => 'OrderID'
    ];

    /**
     * Конструктор объекта
     * @param array $params
     */
    public function __construct($params)
    {
        $this->values = $params;

        $this->orderId = isset($params['orderId']) ? $params['orderId'] : null;
        $this->orderNumber = isset($params['orderNumber']) ? $params['orderNumber'] : null;
        $this->orderStatus = isset($params['orderStatus']) ? $params['orderStatus'] : null;
        $this->touristName = isset($params['touristName']) ? $params['touristName'] : null;
        $this->managerName = isset($params['managerName']) ? $params['managerName'] : null;
        //$this->managerCompanyId = isset($params['agencyId']) ? $params['agencyId'] : null;
        $this->clientId = isset($params['clientId']) ? $params['clientId'] : null;

        if (isset($params['archived'])) {
            $this->archived = ($params['archived']) ? 1 : 0;
        } else {
            $this->archived = -1;
        }

        $this->startDate = isset($params['startDate']) ? $params['startDate'] : null;
        $this->finishDate = isset($params['finishDate']) ? $params['finishDate'] : null;

        $this->modificationDateFrom = isset($params['modificationDateFrom']) ? $params['modificationDateFrom'] : null;
        $this->modificationDateTo = isset($params['modificationDateTo']) ? $params['modificationDateTo'] : null;

        $this->countryName = isset($params['countryName']) ? $params['countryName'] : null;
        $this->cityName = isset($params['cityName']) ? $params['cityName'] : null;

        /* сия странная конструкция здесь потому, ято в базе этот параметр определен как char >.< */
        $this->offline = isset($params['offline']) ? ((bool)$params['offline'] ? 1 : 0) : null;

        $this->sortFields = isset($params['sortBy']) ? $params['sortBy'] : null;
        $this->sortDir = isset($params['sortDir']) ? $params['sortDir'] : null;

        $this->offset = isset($params['offset']) ? $params['offset'] : null;
        $this->limit = isset($params['limit']) ? $params['limit'] : null;

        parent::__construct();
        $this->validate();
        $this->errors = $this->getErrors();
    }

    /**
     * Создание экземпляра формы без параметров
     * @return OrderSearchForm
     */
    public static function createInstance()
    {
        $fakeparams = [];
        return new self($fakeparams);
    }

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules()
    {
        return [
            ['orderId, orderStatus, offset, limit', 'numerical', 'integerOnly' => true],
            ['touristName, managerName', 'length', 'min' => 3],
            ['startDate, finishDate', 'type', 'type' => 'date', 'dateFormat' => 'yyyy-mm-dd'],
            ['modificationDateFrom, modificationDateTo', 'type', 'type' => 'date', 'dateFormat' => 'yyyy-mm-dd'],
            ['sortFields', 'fieldsExists'],
            ['sortDir', 'checkSortDirection']
        ];

    }

    /**
     * Получение списка заявок удовлетворяющих значениям фильтра
     * @return array заявки
     */
    public function getOrders()
    {
        $sortFields = (!is_array($this->sortFields)) ? '' :
            implode(',', array_map(function ($field) {
                $dbField = $this->_getfieldByAlias($field);
                return ($dbField !== false) ? $dbField : $field;
            }, $this->sortFields));

        /* NB: так как это процедура, порядок важен! */
        $requestData = [
            ':orderNumber' => $this->orderNumber,
            ':orderStatus' => $this->orderStatus,
            ':touristName' => $this->touristName, 
            ':managerName' => $this->managerName, 
            ':clientId' => $this->clientId,
            ':country' => $this->countryName, 
            ':archived' => $this->archived, 
            ':startDate' => $this->startDate, 
            ':finishDate' => $this->finishDate,
            ':offset' => $this->offset,
            ':limit' => $this->limit,
            ':sortFields' => $sortFields,
            ':sortDir' => $this->sortDir,
            ':changedFrom' => $this->modificationDateFrom,
            ':changedTill' => $this->modificationDateTo,
            ':offline' => $this->offline,
            ':userId' => Yii::app()->user->getState('userProfile')['userId'],
            ':cityName' => $this->cityName
        ];

        return Yii::app()->db
            ->createCommand(
                'CALL GetOrdersWithServicesShortInfo(' .
                implode(',', array_keys($requestData)) .
                ')')
            ->queryAll(true, $requestData);
    }

    /**
     * Получение услуг по указанным заявкам
     * @param $orders array идентификаторы заявок
     * @return array
     */
    public static function getOrdersServices($orders)
    {
        $command = Yii::app()->db->createCommand()
            ->select('ordersvc.OrderID orderId, ordersvc.ServiceID serviceID, ordersvc.ServiceID_UTK serviceUtkId,
							ordersvc.ServiceType serviceType, ordersvc.OfferID offerId,
							svcRef.Icon_url serviceIconURL, ordersvc.Status status,
							ordersvc.DateStart startDateTime, ordersvc.DateFinish endDateTime,
							ordersvc.SupplierPrice supplierPrice, ordersvc.KmpPrice kmpPrice,
							ordersvc.SupplierCurrency supplierCurrency,
							ordersvc.SaleCurrency saleCurrency, curRef.CurrencyCode supplierCurrencyCode,
							ordersvc.AgencyProfit agencyProfit, contracts.Commission contractCommission,
							contracts.CurrencyID contractCurrency, ctrctRef.CurrencyCode paymentCurrencyCode,
							ordersvc.AmendAllowed amendAllowed, ordersvc.Offline offline,
							ordersvc.DateAmend dateAmend, ordersvc.DateOrdered dateOrdered,
							ordersvc.ServiceID_main parentService,ordersvc.SupplierID supplierId,
							ordersvc.ServiceName serviceName, country.countryCode countryIataCode,
							country.Name countryName, 
							city.Name cityName, 
							ordersvc.Offline offline, 
							ordersvc.RestPaymentAmount restPaymentAmount,
							ordersvc.Extra Extra'
            )
            ->from('kt_orders_services ordersvc')
            ->leftJoin('kt_ref_services svcRef', 'ordersvc.ServiceType = svcRef.ServiceID')
            ->leftJoin('kt_ref_cities city', 'ordersvc.CityID = city.CityID')
            ->leftJoin('kt_ref_countries country', 'ordersvc.CountryID = country.CountryID')
            ->leftJoin('kt_orders orders', 'ordersvc.OrderID = orders.OrderID')
            ->leftJoin('kt_users mgrs', 'orders.UserID = mgrs.UserID')
            ->leftJoin('kt_companies agencies', 'mgrs.AgentID = agencies.AgentID')
            ->leftJoin('kt_contracts contracts', 'orders.ContractID = contracts.ContractID')
            ->leftJoin('kt_ref_currencies curRef', 'ordersvc.SupplierCurrency = curRef.CurrencyID')
            ->leftJoin('kt_ref_currencies ctrctRef', 'contracts.CurrencyID = ctrctRef.CurrencyID')
            ->where(array('in', 'ordersvc.OrderID', $orders));
        return $command->queryAll();
    }

    /**
     * Получить информацию о счетах и оплатах заявки
     * @param $orderId
     * @return array|CDbDataReader
     */
    public function getOrderInvoices($orderId)
    {

        $command = Yii::app()->db->createCommand()
            ->select('inv.Status status,inv.InvoiceID invoiceId,
						TRIM(inv.InvoiceID_UTK) invoiceNum, inv.InvoiceDate creationDate,
						 TRIM(inv.InvoiceID_UTK) description, inv.InvoiceAmount invoiceSum,
						cur.CurrencyCode invoiceCur, svc.ServiceID serviceId,
						svc.ServicePrice serviceSum, svccur.CurrencyCode serviceCur,
						 ordsvc.ServiceName serviceName, invpay.Amount paymentSum,
						 paycur.CurrencyCode paymentCur, pay.Status paymentStatus,
						  pay.PaymentDate paymentDate')
            ->from('kt_orders_services ordsvc')
            ->leftJoin('kt_invoices_services svc', 'svc.serviceID = ordsvc.ServiceID')
            ->leftJoin('kt_invoices inv', 'inv.InvoiceID = svc.InvoiceID')
            ->leftJoin('kt_ref_currencies cur', 'inv.CurrencyID = cur.CurrencyID')
            ->leftJoin('kt_ref_currencies svccur', 'svc.CurrencyID = svccur.CurrencyID')
            ->leftJoin('kt_payments_invoices invpay', 'invpay.InvoiceID = svc.InvoiceID')
            ->leftJoin('kt_payments pay', 'pay.PaymentID = invpay.PaymentID')
            ->leftJoin('kt_ref_currencies paycur', 'invpay.CurrencyID = paycur.CurrencyID')
            ->where('ordsvc.OrderID = :orderId and nullif(inv.InvoiceID,\'\') is not null ', array(':orderId' => $orderId));

        return $command->queryAll();
    }

    /**
     * Получение информации о заявке
     * по идентифкатору заявки
     * @return null
     */
    public function getOrder()
    {
        if (empty($this->orderId)) {
            return null;
        }

        $command = Yii::app()->db->createCommand()
            ->select(
                'kt_orders.OrderID orderId,
                kt_orders.orderNumber orderNumber,
                kt_orders.OrderID_UTK orderIdUtk,
                kt_orders.OrderID_GP orderIdGp,
                kt_orders.orderDate,
                kt_orders.UserID userId,
                kt_orders.AgentID agentId,
                kt_orders.Status status,
                kt_orders.VIP VIP,
                kt_orders.ContractID contractID,
                kt_orders.archive,
                kt_orders.UserID managerId,
                
                (select c.Name from kt_companies c where c.AgentID = agency.parentId)  companyMainOffice,
                agency.Name agencyName,
                agency.Type companyRoleType,
                
                mgr.UserID AS KMPManagerID,
                mgr.Surname AS KMPManagerSurname,
                mgr.Name AS KMPManagerName,
                mgr.SndName AS KMPManagerMiddleName,
                
                CompanyManager.UserID AS agencyManagerID,
                CompanyManager.Surname AS agencyManagerSurname,
                CompanyManager.Name AS agencyManagerName,
                CompanyManager.SndName AS agencyManagerMiddleName,
                
                CompanyResponsibleManager.UserID AS agencyResponsibleManagerID,
                CompanyResponsibleManager.Surname AS agencyResponsibleManagerSurname,
                CompanyResponsibleManager.Name AS agencyResponsibleManagerName,
                CompanyResponsibleManager.SndName AS agencyResponsibleManagerMiddleName,
                
                min(ordersvc.DateStart) as startDate,
                max(ordersvc.DateFinish) as endDate,
                turbase.Name as touristFirstName, 
                turbase.Surname as touristLastName,
                turbase.Email as liderEmail, 
                turbase.Phone as liderPhone,
                count(distinct turorder.TouristID) touristsNums'
            )
            ->from('kt_orders')
            ->leftJoin('kt_orders_services ordersvc', 'kt_orders.OrderID = ordersvc.OrderID')
            ->leftJoin('kt_tourists_order turorder', 'kt_orders.OrderID = turorder.OrderID')
            ->leftJoin('kt_orders_services_tourists osrvtur',
                'turorder.TouristID = osrvtur.TouristID and turorder.TourLeader = 1')
            ->leftJoin('kt_tourists_base turbase', 'turorder.TouristIDbase = turbase.TouristIDbase')
            ->leftJoin('kt_users mgr', 'mgr.UserID = kt_orders.UserID')
            ->leftJoin('kt_companies agency', 'kt_orders.AgentID = agency.AgentID')
            ->leftJoin('kt_users CompanyManager', 'kt_orders.CompanyManager = CompanyManager.UserID')
            ->leftJoin('kt_users CompanyResponsibleManager', 'kt_orders.KMPManager = CompanyResponsibleManager.UserID')
            ->where('kt_orders.OrderID = :orderId', array(':orderId' => $this->orderId));
        return $command->queryRow();
    }

    /**
     * Создание новой заявки
     * @return null
     */
    public function createOrder($orderUtkId, $agencyId, $agentId)
    {

        if (empty($orderUtkId)) {
            return null;
        }

        $agentId = !empty($agentId) ? $agentId : null;

        $command = Yii::app()->db
            ->createCommand("select CreateOrder(:in_orderIdUtk, :in_agencyId, :in_agentId);");

        $command->bindParam(":in_orderIdUtk", $orderUtkId, PDO::PARAM_STR);
        $command->bindParam(":in_agencyId", $agencyId, PDO::PARAM_STR);
        $command->bindParam(":in_agentId", $agentId, PDO::PARAM_STR);

        $this->orderId = $command->queryScalar();
        return $this->orderId;
    }

    /**
     * Получение информации о заявке
     * по идентифкатору заявки
     * @return null
     */
    public function getOrderById()
    {

        if (empty($this->orderId)) {
            return null;
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_orders')
            ->where('kt_orders.OrderID = :orderId', array(':orderId' => $this->orderId));

        return $command->queryRow();
    }

    /**
     * Установка идентифкатора туриста
     * являющегося турлидером
     * @param $tourLeaderId
     * @return bool|int
     */
    public function setTourLeaderId($tourLeaderId)
    {

        if (empty($tourLeaderId) || empty($this->orderId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand();

        $res = true;
        $res = $command->update('kt_orders', [
            'TouristID' => $tourLeaderId
        ], 'OrderID = :order_id', [':order_id' => $this->orderId]);

        return $res;
    }

    /**
     * Проверка существования полей в таблице
     * по указанных алиасам
     * @return bool
     */
    public function fieldsExists()
    {

        if ($this->sortFields == null) {
            return true;
        }

        if (!is_array($this->sortFields)) {
            $this->addError('sortFields', 'invalid');
            return false;
        }

        foreach ($this->sortFields as $sortField) {
            if (!$this->_checkfieldExist($sortField)) {
                $this->addError('sortFields', 'invalid');
                return false;
            }
        }

    }

    /**
     * Получение названия поля в таблице по его алиасу
     * @param $alias string  алиас для поля
     * @return string название поля в таблице | false
     */
    private function _getfieldByAlias($alias)
    {

        if ($this->_checkfieldExist($alias)) {
            return $this->fieldsAliases[$alias];
        }

        return false;
    }

    /**
     * Получить заявку по
     * @param $utkId
     * @return bool
     */
    public function getOrderByUTKId($utkId)
    {

        if (empty($utkId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_orders')
            ->where('kt_orders.OrderID_UTK = :utkId', array(':utkId' => $utkId));

        return $command->queryRow();
    }

    /**
     * Проверка существования указанного поля в таблице заявок
     * @param $fieldName алиас поля
     */
    private function _checkfieldExist($fieldName)
    {

        if (!array_key_exists($fieldName, $this->fieldsAliases)) {
            return false;
        }

        return true;
    }

    /**
     * Проверка направления сортировки
     * @return bool
     */
    public function checkSortDirection()
    {

        if (empty($this->sortDir)) {
            return true;
        }

        if (strtolower($this->sortDir) != 'asc' && strtolower($this->sortDir) != 'desc') {
            $this->addError('sortDir', 'invalid');
            return false;
        }
        return true;
    }


}
