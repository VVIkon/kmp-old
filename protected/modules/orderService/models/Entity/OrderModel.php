<?php

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Модель заявки
 *
 * @property $UserID
 * @property $CompanyManager
 * @property $KMPManager
 * @property $AgentID
 * @property $DOLC
 * @property $orderNumber
 *
 * @property Contract $Contract
 * @property Account $creator
 * @property Account $companyManager
 * @property Account $managerKMP
 * @property Company $Company
 * @property OrdersServices [] $OrdersServices
 */
class OrderModel extends CActiveRecord implements StateFullInterface, Serializable, LoggerInterface
{
    use StateMachineTrait, MultiLang, CurrencyTrait;

    /**
     * Статусы заявки
     */
    const STATUS_NEW = 0;
    const STATUS_MANUAL = 1;
    const STATUS_PAID = 2;
    const STATUS_CLOSED = 3;
    const STATUS_ANNULED = 4;
    const STATUS_W_PAID = 5;
    const STATUS_DONE = 9;
    const STATUS_BOOKED = 10;

    /**
     * Список всех статусов услуги
     * @var array
     */
    protected $statuses = [
        OrderModel::STATUS_NEW,
        OrderModel::STATUS_MANUAL,
        OrderModel::STATUS_PAID,
        OrderModel::STATUS_CLOSED,
        OrderModel::STATUS_ANNULED,
        OrderModel::STATUS_W_PAID,
        OrderModel::STATUS_DONE,
        OrderModel::STATUS_BOOKED,
    ];

    /**
     * Таблица соответсвий статусов к кодам сообщений
     * @var array
     */
    protected $statusMsgCodes = [
        OrderModel::STATUS_NEW => 123,
        OrderModel::STATUS_MANUAL => 124,
        OrderModel::STATUS_PAID => 125,
        OrderModel::STATUS_CLOSED => 126,
        OrderModel::STATUS_ANNULED => 127,
        OrderModel::STATUS_W_PAID => 128,
        OrderModel::STATUS_DONE => 129,
        OrderModel::STATUS_BOOKED => 130
    ];

    protected $states;

    protected $transitions;

    /**
     * @var int Статус
     */
    protected $Status;

    /** @var int ID заявки в базе КТ */
    public $OrderID;

    /**
     * @var string номер заявки человечий
     */
    public $orderNumber;

    /**  @var string ID заявки в базе УТК */
    protected $OrderID_UTK;

    /**  @var int ID заявки в базе GPTS */
    protected $OrderID_GP;

    /**
     * @var string Дата-время создания заявки
     */
    protected $OrderDate;

    /**
     * @var int признак VIP-заявки
     */
    protected $VIP;

    /* @var bool признак архивной заявки */
    public $Archive;

    /**
     * @var int Идентификатор договора, для которого создана заявка
     */
    public $ContractID;

    /**
     * @var int Блокирующее поле. '0'- заявка в работе , '1' - заявка заблокирована.
     */
    protected $Blocked;

    /**
     * @var string Комментарий к заявке
     */
    protected $comment;

    /**
     * Инициализация OWM
     * @throws Exception
     */
    public function init()
    {
        $OWM_Config = Yii::app()->getModule('orderService')->getConfig('OWM');

        if ($OWM_Config && isset($OWM_Config['STATES']) && isset($OWM_Config['TRANSITIONS'])) {
            $this->states = $OWM_Config['STATES'];
            $this->transitions = $OWM_Config['TRANSITIONS'];
        } else {
            throw new Exception('OWM не сконфигурирован');
        }
    }

    public function tableName()
    {
        return 'kt_orders';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'OrdersServices' => array(self::HAS_MANY, 'OrdersServices', 'OrderID'),
            'OrderTourists' => array(self::HAS_MANY, 'OrderTourist', 'OrderID'),
            'OrderDocuments' => array(self::HAS_MANY, 'OrderDocument', 'orderID'),
            'Contract' => array(self::BELONGS_TO, 'Contract', 'ContractID'),
            'creator' => array(self::BELONGS_TO, 'Account', 'UserID'),
            'companyManager' => array(self::BELONGS_TO, 'Account', 'CompanyManager'),
            'managerKMP' => array(self::BELONGS_TO, 'Account', 'KMPManager'),
            'Company' => array(self::BELONGS_TO, 'Company', 'AgentID')
        );
    }

    /**
     * Базовая инициализация из массива
     * @param $params
     */
    public function fromArray($params)
    {
        $this->AgentID = $params['companyId'];
        $this->UserID = $params['userId'];
        $this->ContractID = $params['contractId'];
        $this->CompanyManager = $params['companyManagerId'];
        $this->KMPManager = $params['kmpManagerId'];
    }

    /**
     * Сериализация для передачи через контекст делегатов
     * @return string
     */
    public function serialize()
    {
        return serialize([
            $this->OrderID,
            $this->orderNumber,
            $this->OrderDate,
            $this->OrderID_GP,
            $this->OrderID_UTK,
            $this->Status,
            $this->AgentID,
            $this->UserID,
            $this->VIP,
            $this->ContractID,
            $this->Blocked,
            $this->comment,
            $this->CompanyManager,
            $this->KMPManager,
            $this->Archive
        ]);
    }

    /**
     * Десериализация
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list(
            $this->OrderID,
            $this->orderNumber,
            $this->OrderDate,
            $this->OrderID_GP,
            $this->OrderID_UTK,
            $this->Status,
            $this->AgentID,
            $this->UserID,
            $this->VIP,
            $this->ContractID,
            $this->Blocked,
            $this->comment,
            $this->CompanyManager,
            $this->KMPManager,
            $this->Archive
            ) = unserialize($serialized);

        if ($this->OrderID) {
            $this->setIsNewRecord(false);
        }
    }

    /**
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->OrderID;
    }

    /**
     * @return int|null
     */
    public function getOrderIDGP()
    {
        return $this->OrderID_GP;
    }

    /**
     * @param int $OrderID_GP
     */
    public function setOrderIDGP($OrderID_GP)
    {
        $this->OrderID_GP = $OrderID_GP;
    }

    /**
     * @return int|null
     */
    public function getOrderIDUTK()
    {
        return $this->OrderID_UTK;
    }

    /**
     * @param string $OrderID_GP
     */
    public function setOrderIDUTK($OrderID_UTK)
    {
        $this->OrderID_UTK = (string)$OrderID_UTK;
    }

    /**
     * @param string $OrderDate
     */
    public function setOrderDate($OrderDate)
    {
        $this->OrderDate = $OrderDate;
    }

    public function markAsVIP()
    {
        $this->VIP = true;
    }

    public function unmarkAsVIP()
    {
        $this->VIP = false;
    }

    /* Установка признака архива */
    public function setArchive($archive)
    {
        $this->Archive = ($archive) ? 1 : 0;
    }

    /**
     * Возвращает состояние заявки архиная/неархивная
     * @return bool
     */
    public function isArchived()
    {
        return (bool)$this->Archive;
    }

    /**
     * Установка клиента в заявке
     * @param Company $Company компания-клиент
     */
    public function bindAgency(Company $Company)
    {
        $this->AgentID = $Company->AgentID;

        $CompanyManager = $Company->getCompanyManager();
        if (!isset($CompanyManager)) {
            throw new KmpException(
                __CLASS__, __FUNCTION__,
                OrdersErrors::CANNOT_GET_AGENCY_MANAGER,
                ['AgentID' => $Company->AgentID]
            );
        }
        $this->CompanyManager = $CompanyManager->UserID;

        $KMPManager = $Company->getKmpManager();
        if (!isset($KMPManager)) {
            throw new KmpException(
                __CLASS__, __FUNCTION__,
                OrdersErrors::CANNOT_GET_RESPONSIBLE_MANAGER,
                ['AgentID' => $Company->AgentID]
            );
        }
        $this->KMPManager = $KMPManager->UserID;
    }

    /**
     * Установка создателя заявки
     * @param Account $Creator пользователь-создатель
     */
    public function bindCreator(Account $Creator)
    {
        $this->UserID = $Creator->UserID;
    }

    /**
     * Установка контракта заявки
     * @param Contract $Contract
     */
    public function bindContract(Contract $Contract)
    {
        $this->ContractID = $Contract->ContractID;
    }

    /**
     * @return Contract|null
     */
    public function getContract()
    {
        return $this->Contract;
    }

    public function countServices()
    {
        if (is_null($this->OrdersServices)) {
            return 0;
        }
        return count($this->OrdersServices);
    }

    /**
     * Берем все услуги
     * @return OrdersServices []
     */
    public function getOrderServices()
    {
        return $this->OrdersServices;
    }

    /**
     * Проверка что заява оффлайн
     * @return bool
     */
    public function isOffline()
    {
        $OrdersServices = $this->getOrderServices();

        foreach ($OrdersServices as $OrdersService) {
            if ($OrdersService->isOffline()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Берем всех туристов заявки
     * @return OrderTourist []
     */
    public function getOrderTourists()
    {
        return $this->OrderTourists;
    }

    /**
     * Удаление заявки, если она пуста
     * актуально при создании
     */
    public function deleteIfEmpty()
    {
        $OrdersServices = $this->getOrderServices();
        $OrderTourists = $this->getOrderTourists();

        $orderItems = count($OrdersServices) + count($OrderTourists);

        if (!$orderItems) {
            $this->delete();
        }
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        if ($this->Status === null) {
            return self::STATUS_NEW;
        } else {
            return $this->Status;
        }
    }

    /**
     * @param $Status
     * @return bool
     */
    public function inStatus($Status)
    {
        return $this->Status == $Status;
    }

    /**
     * @param mixed $Status
     * @return bool
     */
    public function setStatus($Status)
    {
        if (in_array($Status, $this->statuses)) {
            $this->Status = $Status;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Возвращает код сообщения из таблицы kt_messages
     */
    public function getStatusMsgCode()
    {
        return $this->statusMsgCodes[$this->getStatus()];
    }

    /**
     * Получение офферов из сервисов
     * @return array|int
     */
    public function getOffers()
    {
        $offers = [];

        try {
            foreach ($this->OrdersServices as $OrdersService) {
                $OrdersService->setLang($this->lang);
                $OrdersService->setCurrency($this->Currency);

                $offers[] = $OrdersService->getOfferArray();
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return $offers;
    }

    /**
     * @return int
     */
    public function getAgentID()
    {
        return $this->AgentID;
    }

    /**
     * @return int
     */
    public function getUserID()
    {
        return $this->UserID;
    }

    /**
     * Получение данных для логирования заявки
     * @return string
     */
    public function getLogData()
    {
        return "Создана заявка № $this->OrderID";
    }

    /**
     * @return mixed
     */
    public function getOrderID_UTK()
    {
        return $this->OrderID_UTK;
    }

    /**
     * @return DateTime
     */
    public function getOrderDate()
    {
        return new DateTime($this->OrderDate);
    }

    /**
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->Company;
    }

    /**
     * Проверка наличия турлидера в заявке
     * @return bool
     */
    public function hasTourLeader()
    {
        $OrderTourists = $this->getOrderTourists();

        if (count($OrderTourists)) {
            foreach ($OrderTourists as $OrderTourist) {
                if ($OrderTourist->isTourLeader()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Создание или обновление туриста заявки
     * @param array $params
     * @throws Exception
     * @throws TouristException
     * @return OrderTourist
     */
    public function setTourist(array $params)
    {
        $createAddFileds = false;

        if (isset($params['touristId']) && $params['touristId']) {
            // здесь ищем туриста по ID
            $OrderTourist = OrderTouristRepository::getByOrderIdAndTouristId($this->OrderID, $params['touristId']);
            if (is_null($OrderTourist)) {
                throw new TouristException(OrdersErrors::TOURIST_NOT_FOUND);
            }
        } else {
            // создаем нового туриста
            $OrderTourist = new OrderTourist();
            $OrderTourist->setOrderID($this->OrderID);
            $createAddFileds = true;
        }

        $OrderTourist->fromArray($params);

        if (!$OrderTourist->save()) {
            throw new Exception(OrdersErrors::DB_ERROR);
        }

        // создадим доп поля туриста в заявке, если новый турист
        if ($createAddFileds) {
            $addFields = AdditionalFieldTypeRepository::getTouristFieldsForCompany($this->getCompany());

            foreach ($addFields as $addField) {
                $orderAddField = new OrderAdditionalField();
                $orderAddField->bindTourist($OrderTourist);
                $orderAddField->bindAdditionalFieldType($addField);
                $orderAddField->save(false);
            }
        }

        // если в заявке нет турлидера, то сделаем туристозаявку таковым
        if (!$this->hasTourLeader()) {
            $OrderTourist->makeTourLeader();
            if (!$OrderTourist->save()) {
                throw new Exception(OrdersErrors::DB_ERROR);
            }
        }

        return $OrderTourist;
    }

    public function getSOOrder()
    {
        return [
            'orderId' => $this->OrderID,                  // ID, он же номер заявки
            'orderNumber' => $this->orderNumber,
//            'orderId_Utk' => 53454,              // № заявки в УТК
//            'orderId_Gp' => 232343,              // № заявки в GPTS
            'orderDate' => $this->OrderDate,    //Дата-время создания заявки
            'status' => $this->Status,                      // Статус заявки
            'statusName' => $this->getOrderStatusName(),
            'agentId' => $this->AgentID,                 // ID клиента
            'agencyName' => $this->getCompany()->getName(),         // Название компании
            'userId' => $this->UserID,                  // ID пользователя (KT), создавшего заявку
            'archive' => $this->Archive,                       // Признак архивной записи.
            'dolc' => $this->DOLC,        // Дата время последнего изменения заявки
            'vip' => $this->VIP,                          // признак VIP-заявки
            'contractId' => $this->ContractID,              // Идентификатор договора, для которого создана заявка
            'blocked' => 0,                      // Блокирующее поле. 0- заявка в работе ,  1 - заявка заблокирована
            'comment' => $this->comment, //  Комментарий к заявке
            'companyManager' => $this->CompanyManager,            // Идентификатор менеджера компании для заявки
            'kmpManager' => $this->KMPManager                // Идентификатор менеджера КМП для заявки
        ];
    }

    private function getOrderStatusName()
    {
        $message = MessageRepository::getByIdAndLang($this->getStatusMsgCode(), $this->getLang());

        if ($message) {
            return $message->getMessage();
        }

        return '';
    }

    /**
     * @return Account
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @return Account
     */
    public function getCompanyManager()
    {
        return $this->companyManager;
    }

    /**
     * @return Account
     */
    public function getKMPManager()
    {
        return $this->managerKMP;
    }

    /**
     * @param Account $manager - менеджер компании клиента
     */
    public function bindCompanyManager(Account $manager)
    {
        $this->companyManager = $manager->UserID;
    }

    /**
     * @param Account $manager - менеджер компании от КМП
     */
    public function bindKMPManager(Account $manager)
    {
        $this->managerKMP = $manager->UserID;
    }

    /**
     * Сохраняем без валидации
     */
    public function save($runValidation = true, $attributes = null)
    {
        return parent::save(false);
    }

    /**
     * @return mixed
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('lang', new Assert\NotBlank(array('message' => OrdersErrors::LANGUAGE_NOT_SET)));
        $metadata->addPropertyConstraint('lang', new Assert\Language(array('message' => OrdersErrors::INCORRECT_LANGUAGE)));
    }
}