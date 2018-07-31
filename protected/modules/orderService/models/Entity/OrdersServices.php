<?php

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * Модель услуги
 * @property $ServiceID    bigint(20) Auto Increment    ID услуги
 * @property $ServiceID_UTK    varchar(36) NULL    ID услуги в УТК
 * @property $ServiceID_GP    varchar(36) NULL    ID услуги в ГПТС
 * @property $Status    tinyint(4) [0]    Состояние забронированной услуги (см.[[Состояния услуги]])
 * @property $ServiceType    int(11) [6]    Тип услуги (по умолчанию, тур)
 * @property $TourID    bigint(20) NULL    ID тура в справочнике
 * @property $OfferID    int(11) NULL    Номер ценового предложения внутри тура
 * @property $DateStart    datetime NULL    Дата/время начала услуги (дата заезда)
 * @property $DateFinish    datetime NULL    Дата/время окончания услуги (дата выезда)
 * @property $AmendAllowed    tinyint(1) NULL [0]    Можно ли изменять услугу онлайн: (false - нелья; true - можно)
 * @property $DateAmend    datetime NULL    Дата, до которой можно изменять услугу онлайн
 * @property $DateOrdered    timestamp NULL [CURRENT_TIMESTAMP]    Дата бронирования услуги
 * @property $SupplierPrice    decimal(15,2) NULL [0.00]    Цена поставщика
 * @property $KmpPrice    decimal(15,2) NULL [0.00]    Цена клиента , конечного покупателя услуги.
 * @property $AgencyProfit    decimal(15,2) NULL [0.00]    Комиссия агента в валюте себестоимости услуги
 * @property $SupplierCurrency    int(11) NULL [978]    Валюта поставщика услуги
 * @property $SaleCurrency    int(11) [978]    Код валюты себестоимости услуги
 * @property $SalePenalty    decimal(15,2) NULL [0.00]    Штраф покупателя
 * @property $SalePenaltyCurr    int(11) NULL [978]    Валюта штрафа покупателя
 * @property $NetPenalty    decimal(15,2) NULL [0.00]    Штраф поставщика
 * @property $NetPenaltyCurr    int(11) NULL [978]    Валюта штрафа поставщика
 * @property $OrderID    bigint(20)    № заявки
 * @property $Extra    text NULL    Дополнительная информация об услуге
 * @property $CityID    int(11) NULL    Код города предоставления услуги
 * @property $CountryID    int(11) NULL    Код страны предоставления услуги
 * @property $SupplierID    int(11) NULL    Код поставщика
 * @property $SupplierSvcID    varchar(12) NULL    это ID услуги поставщика
 * @property $ServiceName    varchar(500) NULL    Краткое описание услуги ( 'ИСПАНИЯ; БАРСЕЛОНА; Test Barcelona Hotel Double for Single Occupancy Bed & Continental Breakfast' )
 * @property $ServiceID_main    bigint(20) NULL [0]    Признак доп.услуги к сервису. 0 - основная услуга ID - дополнительная услуга к основной с индексом ID
 * @property $Offline    tinyint(1) NULL [0]    признак услуги online (0) / offline (1)
 * @property $agreementSet    tinyint(4) NULL [0]    Договор с поставщиком.
 * @property $RestPaymentAmount    decimal(15,2) NULL [0.00]    Остаток к оплате по услуге в валюте поставщика с учетом выставленных счетов
 * @property $isPaid    tinyint(1) NULL [0]    факт оплаты услуги true/false ( 1/ 0 )
 * @property $ktService
 * @property $dateCreate    datetime NULL    Дата/время создания услуги
 *
 *
 * @property OrdersServicesTourists [] $ServiceTourists
 * @property OrderServicePrice [] $OrderServicePrices
 * @property OrderServicePenalty [] $OrderServicePenalties
 * @property OrderModel $OrderModel
 * @property OrderTourist [] $OrderTourists
 * @property OrderServicePrice $OrderServiceClientPrice
 * @property OrdersServicesAddService[] $addServices
 * @property RefServices $RefService
 * @property City $city
 * @property Country $country
 */
class OrdersServices extends CActiveRecord implements StateFullInterface, Serializable, LoggerInterface
{
    /**
     * Используем трейт машины состояний, чтобы пользоваться моделью как FSM
     */
    use StateMachineTrait, MultiLang, CurrencyTrait;

    // статусы
    const STATUS_NEW = 0;
    const STATUS_W_BOOKED = 1;
    const STATUS_BOOKED = 2;
    const STATUS_W_PAID = 3;
    const STATUS_P_PAID = 4;
    const STATUS_PAID = 5;
    const STATUS_CANCELLED = 6;
    const STATUS_VOIDED = 7;
    const STATUS_DONE = 8;
    const STATUS_MANUAL = 9;

    /**
     * Список всех статусов услуги
     * @var array
     */
    protected $statuses = [
        OrdersServices::STATUS_NEW,
        OrdersServices::STATUS_W_BOOKED,
        OrdersServices::STATUS_BOOKED,
        OrdersServices::STATUS_W_PAID,
        OrdersServices::STATUS_P_PAID,
        OrdersServices::STATUS_PAID,
        OrdersServices::STATUS_CANCELLED,
        OrdersServices::STATUS_VOIDED,
        OrdersServices::STATUS_DONE,
        OrdersServices::STATUS_MANUAL,
    ];

    /**
     * Таблица соответсвий статусов к кодам сообщений
     * @var array
     */
    protected $statusMsgCodes = [
        OrdersServices::STATUS_NEW => 123,
        OrdersServices::STATUS_W_BOOKED => 131,
        OrdersServices::STATUS_BOOKED => 130,
        OrdersServices::STATUS_W_PAID => 128,
        OrdersServices::STATUS_P_PAID => 132,
        OrdersServices::STATUS_PAID => 125,
        OrdersServices::STATUS_CANCELLED => 133,
        OrdersServices::STATUS_VOIDED => 134,
        OrdersServices::STATUS_DONE => 129,
        OrdersServices::STATUS_MANUAL => 124,
    ];

    private $serviceTypesMetadata = [
        1 => [
            'name' => 'Hotel',
        ],
        2 => [
            'name' => 'Avia',
        ],
    ];

    /**
     * Валидатор для модели
     * @var
     */
    protected $Validator;

    /**
     * Состояния FSM
     * @var
     */
    protected $states;

    /**
     * Переходы FSM
     * @var
     */
    protected $transitions;

    public $DateStart;
    public $DateFinish;
    public $SupplierPrice;
    public $KmpPrice;
    public $AgencyProfit;

    public function tableName()
    {
        return 'kt_orders_services';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'Penalties' => array(self::HAS_MANY, 'OrdersServicesPenalty', 'ServiceID'),
            'RefService' => array(self::BELONGS_TO, 'RefServices', 'ServiceType'),
            'Invoices' => array(self::MANY_MANY, 'Invoice', 'kt_invoices_services(InvoiceID, ServiceID)'),
            'InvoiceServices' => array(self::HAS_MANY, 'InvoiceService', 'ServiceID'),
            'OrderTourists' => array(self::MANY_MANY, 'OrderTourist', 'kt_orders_services_tourists(ServiceID, TouristID)'),
            'ServiceTourists' => array(self::HAS_MANY, 'OrdersServicesTourists', 'ServiceID'),
            'OrderServicePrices' => array(self::HAS_MANY, 'OrderServicePrice', 'serviceId'),
            'OrderServicePenalties' => array(self::HAS_MANY, 'OrderServicePenalty', 'serviceId'),
            'OrderModel' => array(self::BELONGS_TO, 'OrderModel', 'OrderID'),
            'addServices' => array(self::HAS_MANY, 'OrdersServicesAddService', 'serviceId'),
            'city' => array(self::BELONGS_TO, 'City', 'CityID'),
            'country' => array(self::BELONGS_TO, 'Country', 'CountryID')
        );
    }

    /**
     * Инициализация - берет данные о состояниях и переходах машины из конфига
     * @throws Exception
     */
    public function init()
    {
        $SWM_Config = Yii::app()->getModule('orderService')->getConfig('SWM');
        $this->dateCreate = date("Y-m-d H:i:s");

        if ($SWM_Config && isset($SWM_Config['STATES']) && isset($SWM_Config['TRANSITIONS'])) {
            $this->states = $SWM_Config['STATES'];
            $this->transitions = $SWM_Config['TRANSITIONS'];
        } else {
            throw new Exception('SWM не сконфигурирован');
        }
    }

    /**
     * @return mixed
     */
    public function getOfferID()
    {
        return $this->OfferID;
    }

    /**
     * @return City
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $serviceName название услуги
     */
    public function setServiceName($serviceName)
    {
        $this->ServiceName = $serviceName;
    }

    /**
     * Возвращает счета из услуги
     * @return Invoice []
     */
    public function getInvoices()
    {
        return $this->Invoices;
    }

    /**
     * @return mixed
     */
    public function getServiceType()
    {
        return $this->ServiceType;
    }

    /**
     * @param int $serviceType
     */
    public function setServiceType($serviceType)
    {
        $this->ServiceType = $serviceType;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->OrderID;
    }

    /**
     * @param int $OrderId
     */
    public function setOrderId($OrderId)
    {
        $this->OrderID = $OrderId;
    }

    public function agreementSet()
    {
        $this->agreementSet = 1;
    }

    public function getServiceName()
    {
        return $this->ServiceName;
    }

    /**
     * @return mixed
     */
    public function getServiceID()
    {
        return $this->ServiceID;
    }

    /**
     * @param mixed $ServiceID
     */
    public function setServiceID($ServiceID)
    {
        $this->ServiceID = $ServiceID;
    }

    /**
     * Инжектирование валидатора, пока сомнительная функция со старым кодом
     * @param $Validator
     */
    public function setValidator($Validator)
    {
        $this->Validator = $Validator;
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
        return $Status == $this->Status;
    }

    /**
     * @return mixed
     */
    public function getSupplierID()
    {
        return $this->SupplierID;
    }

    /**
     * @param mixed $SupplierID
     */
    public function setSupplierID($SupplierID)
    {
        $this->SupplierID = $SupplierID;
    }

    /**
     * Установка поставщика услуги
     * @param RefSuppliers $Supplier объект поставщика
     */
    public function bindSupplier(RefSuppliers $Supplier)
    {
        $this->SupplierID = $Supplier->getSupplierID();
    }

    /**
     * Установка "главного" города услуги
     * @param City $City объект города
     */
    public function bindCity(City $City)
    {
        $this->CityID = $City->getCityId();
        $this->CountryID = $City->getCountryId();
    }

    /**
     * @return OrderModel|null
     */
    public function getOrderModel()
    {
        return $this->OrderModel;
    }

    /**
     * @return OrdersServicesTourists []
     */
    public function getServiceTourists()
    {
        return $this->ServiceTourists;
    }

    /**
     * Валидация общих параметров для создания услуги
     * @param array|null $params
     * @return bool|int|mixed
     */
    public function validate($params)
    {
        if ($this->Validator) {
            try {
                $this->Validator->checkServiceCommonParams($params);
                $this->Validator->checkServiceCreateParams($params);
                return false;
            } catch (KmpInvalidArgumentException $kae) {
                return $kae->getCode();
            }
        }
    }

    /**
     * Сериализация для передачи через контекст данных
     * @return string
     */
    public function serialize()
    {
        return serialize(
            [
                $this->OfferID,
                $this->ServiceID,
                $this->ServiceID_UTK,
                $this->ServiceID_GP,
                $this->ServiceType,
                $this->Status,
                $this->OrderID,
                $this->agreementSet,
                $this->ServiceName,
                $this->SupplierID,
                $this->DateStart,
                $this->DateFinish,
                $this->SalePenalty,
                $this->SalePenaltyCurr,
                $this->NetPenalty,
                $this->NetPenaltyCurr,
                $this->SupplierPrice,
                $this->KmpPrice,
                $this->AgencyProfit,
                $this->SupplierCurrency,
                $this->SaleCurrency,
                $this->ktService,
                $this->Offline,
                $this->RestPaymentAmount,
                $this->Extra,
                $this->dateCreate
            ]
        );
    }

    /**
     * Десериализация
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list(
            $this->OfferID,
            $this->ServiceID,
            $this->ServiceID_UTK,
            $this->ServiceID_GP,
            $this->ServiceType,
            $this->Status,
            $this->OrderID,
            $this->agreementSet,
            $this->ServiceName,
            $this->SupplierID,
            $this->DateStart,
            $this->DateFinish,
            $this->SalePenalty,
            $this->SalePenaltyCurr,
            $this->NetPenalty,
            $this->NetPenaltyCurr,
            $this->SupplierPrice,
            $this->KmpPrice,
            $this->AgencyProfit,
            $this->SupplierCurrency,
            $this->SaleCurrency,
            $this->ktService,
            $this->Offline,
            $this->RestPaymentAmount,
            $this->Extra,
            $this->dateCreate
            ) = unserialize($serialized);

        if ($this->ServiceID) {
            $this->setIsNewRecord(false);
        }
    }


    /**
     * Получение оффера из услуги
     * @return Offer|int
     * @throws Exception
     */
    public function getOfferArray()
    {
        $ContractCurrency = $this->getOrderModel()->getContract()->getCurrency();

        $penalties = null;

        $OrderServicePenalties = $this->getOrderServicePenalties();

        /*
         * подразумевается, что у нас только 1 начисленный штраф, хотя связь 1 ко многим,
         * поэтому делаем break;
        */
        if (count($OrderServicePenalties)) {
            foreach ($OrderServicePenalties as $OrderServicePenalty) {
                $OrderServicePenalty->setLang($this->lang);
                $OrderServicePenalty->addCurrencyToConvert('localCurrency', CurrencyStorage::findByString(643));
                $OrderServicePenalty->addCurrencyToConvert('viewCurrency', $this->Currency);
                $OrderServicePenalty->addCurrencyToConvert('clientCurrency', CurrencyStorage::findByString($this->SaleCurrency));

                $penalties = $OrderServicePenalty->toArray();
                break;
            }
        }

        $serviceSalesTermsInfo = null;
        $OrderServicePrices = $this->getOrderServicePrices();

        if (count($OrderServicePrices)) {
            $SalesTermsInfo = new SalesTermsInfo();

            $SalesTermsInfo->addCurrency('local', CurrencyStorage::findByString(643));
            $SalesTermsInfo->addCurrency('view', $this->Currency);
            $SalesTermsInfo->addCurrency('client', $ContractCurrency);
            $SalesTermsInfo->addCurrency('supplier', CurrencyStorage::findByString($this->SupplierCurrency));

            foreach ($OrderServicePrices as $OrderServicePrice) {
                $SalesTermsInfo->addPrice($OrderServicePrice);
            }

            $serviceSalesTermsInfo = $SalesTermsInfo->getArray();
        }

        $offer['serviceId'] = $this->getServiceID();
        $offer['serviceType'] = $this->ServiceType;
        $offer['requestData'] = [    // Данные запроса, для которого предложение
            "adult" => 0,      // Количество взрослых
            "child" => 0       // Количество детей
        ];
        $offer['serviceStatus'] = $this->Status;
        $offer['serviceTourists'] = $this->getTouristsData();
        $offer['penalties'] = $penalties;
        $offer['offerInfo'] = null;
        $offer['serviceSalesTermsInfo'] = $serviceSalesTermsInfo;
        $offer['ktService'] = (bool)$this->ktService;

        // остаток по платежу
        $offer['restPaymentAmount'] = CurrencyRates::getInstance()->calculateInCurrencyByIds($this->RestPaymentAmount, $this->SupplierCurrency, $ContractCurrency->getId());
        $offer['restPaymentAmountCurrency'] = $ContractCurrency->getCode();

        $offer['addServices'] = [];
        // доп услуги
        $addServices = $this->getAddServices();
        foreach ($addServices as $addService) {
            $addService->setViewCurrency($this->Currency);
            $offer['addServices'][] = $addService->toSOAddService();
        }

        $OfferClass = $this->offerFactory();
        if ($OfferClass) {
            $OfferClass->setLang($this->lang);
            $OfferClass->addCurrency('client', $ContractCurrency);
            $OfferClass->addCurrency('view', $this->Currency);
            $OfferClass->addCurrency('local', CurrencyStorage::findByString(643));
            $OfferClass->addCurrency('supplier', CurrencyStorage::findByString($this->SupplierCurrency));

            $offer['offerInfo'] = $OfferClass->toArray($this->getServiceID());
        }

        return $offer;
    }

    /**
     * Получение объекта оффера из услуги
     * @return ServiceOfferInterface
     * @throws Exception
     */
    public function getOffer()
    {
        $ModelClass = $this->offerFactory();

        if (!$ModelClass) {
            throw new Exception("Оффер не найден");
        }

        return $ModelClass;
    }

    /**
     * Заполнение данных заявки из оффера отеля
     * @param HotelOffer $HotelOffer
     */
    public function fromHotelOffer(HotelOffer $HotelOffer)
    {
        // номер предложения
        $this->OfferID = $HotelOffer->getOfferId();

        // тип сервиса
        $this->ServiceType = $HotelOffer->getServiceType();

        // даты заезда и выезда
        $this->DateStart = $HotelOffer->getDateFrom();
        $this->DateFinish = $HotelOffer->getDateTo();

        // Город
        $this->CityID = $HotelOffer->getCityId();

        // Страна
        $this->CountryID = $HotelOffer->getCountryId();

        // Название услуги
        $this->ServiceName = $HotelOffer->generateServiceName();

        // Код поставщика
        $SupplierCode = $HotelOffer->getSupplierCode();
        $this->SupplierID = RefSuppliers::getSupplierIdByCode($SupplierCode);
        $this->SupplierID = 5;
    }

    /**
     * Заполнение данных заявки из оффера
     * @param ServiceOfferInterface $Offer
     */
    public function fromOffer(ServiceOfferInterface $Offer)
    {
        // номер предложения
        $this->OfferID = $Offer->getOfferId();

        // тип сервиса
        $this->ServiceType = $Offer->getServiceType();

        // даты заезда и выезда
        $this->DateStart = $Offer->getDateFrom();
        $this->DateFinish = $Offer->getDateTo();

        // Город
        $this->CityID = $Offer->getCityId();

        // Страна
        $this->CountryID = $Offer->getCountryId();

        // Название услуги
        $this->ServiceName = $Offer->generateServiceName();

        // Код поставщика
        $this->SupplierID = $Offer->getSupplier()->getSupplierID();
        $this->SupplierID = 5;
    }

    /**
     * @return OrderServicePrice[]
     */
    public function getServicePrices()
    {
        return $this->OrderServicePrices;
    }

    /**
     * @return OrderServicePrice
     */
    public function getClientPrice()
    {
        $prices = $this->OrderServicePrices;

        foreach ($prices as $price) {
            if ($price->isClient()) {
                return $price;
            }
        }
    }

    /**
     * Заполнение данных услуги из ценового предложения
     * @param AbstractPriceOffer $PriceOffer
     */
    public function fromPriceOffer(AbstractPriceOffer $PriceOffer)
    {
        if ($PriceOffer->isSupplier()) {
            $this->SupplierPrice = $PriceOffer->getBrutto();
            $this->SupplierCurrency = $PriceOffer->getCurrencyId();
        } elseif ($PriceOffer->isClient()) {
            $this->KmpPrice = $PriceOffer->getBrutto();
            $this->SaleCurrency = $PriceOffer->getCurrencyId();
        }
    }

    /**
     * Добавление ценового объекта в услугу
     * @param AbstractPriceOffer $PriceOffer
     */
    public function addPrice(AbstractPriceOffer $PriceOffer)
    {
        $OrderServicePrice = new OrderServicePrice();
        $OrderServicePrice->setServiceId($this->ServiceID);
        $OrderServicePrice->setType($PriceOffer->getType());
        $OrderServicePrice->fromArray($PriceOffer->toArray());

        if (!$OrderServicePrice->save(false)) {
            throw new DomainException('Не удалось сохранить ценовое предложение', OrdersErrors::DB_ERROR);
        }

        // taxes
        $taxes = $PriceOffer->getTaxes();

        foreach ($taxes as $tax) {
            $OrderServicePriceTax = new OrderServicePriceTax();
            $OrderServicePriceTax->fromArray($tax->toArray(), $OrderServicePrice->getPrimaryKey());
            $OrderServicePriceTax->save(false);
        }
    }

    /**
     * Обновление цены в услуге
     * @param AbstractPriceOffer $PriceOffer
     */
    public function updatePrice(AbstractPriceOffer $PriceOffer)
    {
        $CurrentOrderServicePrices = $this->getServicePrices();

        // найдем нужный тип цены
        if (count($CurrentOrderServicePrices)) {
            foreach ($CurrentOrderServicePrices as $CurrentOrderServicePrice) {
                if ($CurrentOrderServicePrice->getType() == $PriceOffer->getType()) {
                    $OrderServicePrice = $CurrentOrderServicePrice;
                    break;
                }
            }
        } else {
            throw new LogicException('Нельзя обновить несуществующие цены в услуге', OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
        }

        $OrderServicePrice->fromArray($PriceOffer->toArray());

        if (!$OrderServicePrice->save(false)) {
            throw new DomainException('Не удалось сохранить ценовое предложение', OrdersErrors::DB_ERROR);
        }
    }


    /**
     * Получение туристов заявки
     * используется старая модель туристов
     * @return array
     */
    protected function getTouristsData()
    {
        $touristsInfo = [];

        $serviceTouristsInfo = TouristForm::getServiceTourists($this->serviceID);

        if (count($serviceTouristsInfo)) {
            foreach ($serviceTouristsInfo as $serviceTouristInfo) {
                $touristsInfo[] = [
                    'touristId' => $serviceTouristInfo['TouristID'],
                    'attached' => true,
                    'firstName' => $serviceTouristInfo['Name'],
                    'middleName' => $serviceTouristInfo['MiddleName'],
                    'surName' => $serviceTouristInfo['Surname']
                ];
            }

            return $touristsInfo;
        } else {
            return [];
        }
    }

    /**
     * @return OrderTourist []
     */
    public function getOrderTourists()
    {
        return $this->OrderTourists;
    }


    /**
     * Получение данных туриста в зависимости от типа услуги
     * @return array
     */
    public function getServiceTouristsArray()
    {
        $ServiceTourist = $this->serviceTouristFactory();
        $OrderTourists = $this->getOrderTourists();
        $touristData = [];

        if (count($OrderTourists)) {
            foreach ($OrderTourists as $OrderTourist) {
                $OrdersServicesTourist = OrdersServicesTouristsRepository::findByServiceAndOrderTouristIds($this->ServiceID, $OrderTourist->getTouristID());

                $touristData[] = $ServiceTourist->getTouristData($OrderTourist->getTourist(), $OrderTourist->getDocument(), $OrdersServicesTourist);
            }
        }

        return $touristData;
    }


    /**
     * ПОлучение имени сервиса по коду типа
     * @param $type
     * @return mixed
     * @throws Exception
     */
    public function getServiceNameByType($type)
    {
        if (array_key_exists($type, $this->serviceTypesMetadata)) {
            return $this->serviceTypesMetadata[$type]['name'];
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getServiceNameType()
    {
        if (array_key_exists($this->ServiceType, $this->serviceTypesMetadata)) {
            return $this->serviceTypesMetadata[$this->ServiceType]['name'];
        } else {
            return '';
        }
    }

    /**
     * Возвращает класс адаптер к туристу для получения нужных данных туриста в зависимости от типа услуги
     * @return AbstractServiceTourist
     * @throws Exception
     */
    protected function serviceTouristFactory()
    {
        $className = $this->getServiceNameByType($this->getServiceType()) . 'Tourist';

        if (class_exists($className)) {
            return new $className();
        } else {
            throw new Exception("Не найден класс $className");
        }
    }


    /**
     * Фабричный метод по получению объекта оффера в зависимости от услуги
     * @return ServiceOfferInterface|false
     * @throws Exception
     */
    protected function offerFactory()
    {
        if ($this->RefService) {
            $modelClassName = $this->RefService->getModelName();
        } else {
            throw new Exception('RefService not found');
        }

        if (empty($modelClassName)) {
            return false;
        }

        $repositoryClassName = $modelClassName . 'Repository';

        // проверим есть ли такой класс, если есть, то сразу создадим
        if (class_exists($repositoryClassName)) {
            $OfferObject = $repositoryClassName::findByOfferId($this->getOfferID());
        } else {
            throw new Exception("Класс репозиторий оффера \"$repositoryClassName\" не найден");
        }

        return $OfferObject;
    }

    /**
     * Сохранение данных брони в оффер
     * @param BookData $BookData
     */
    public function setBookData(BookData $BookData)
    {
        // если есть данные бронирования
        if(!empty($BookData->getBookData())){
            $offer = $this->getOffer();
            $offer->setBookData($BookData->getBookData());
            $offer->save(false);
        }

        $addServicesBookData = $BookData->getAddServices();
        $addServices = $this->getAddServices();

        foreach ($addServicesBookData as $addServiceBookData) {
            foreach ($addServices as $addService) {
                if ($addServiceBookData['offerId'] == $addService->getOfferId()) {
                    $addService->setStatus($addServiceBookData['status']);
                    $addService->save(false);
                    break;
                }
            }
        }
    }

    /**
     * Возвращает поставщика услуги
     * @return bool|RefSuppliers
     */
    public function getSupplier()
    {
        $Offer = $this->getOffer();

        if ($Offer) {
            return $Offer->getSupplier();
        } else {
            return false;
        }
    }


    /**
     * Сохранение новых цен из структуры ss_salesTerms
     * @param array $SSSalesTerm
     */
    public function setNewSalesTermsFromSSSalesTerm(array $SSSalesTerm)
    {
        // сохраним новые цены в оффер
//        $this->getOffer()->updateFromSSSalesTerms($SSSalesTerm);

        // сохраним новые цены в саму услугу
        $CurrencyRates = CurrencyRates::getInstance();

        foreach ($SSSalesTerm as $salesTermType => $salesTerm) {
            $NewCurrencyId = $CurrencyRates->getIdByCode($salesTerm['currency']);

            $this->setSaleTerm($salesTermType, $salesTerm);

            switch ($salesTermType) {
                case 'supplier':
                    $this->setAttribute('SupplierPrice', $CurrencyRates->calculateInCurrencyByIds($salesTerm['amountBrutto'], $NewCurrencyId, $this->SupplierCurrency));
                    break;
                case 'client':
                    $this->setAttribute('KmpPrice', $CurrencyRates->calculateInCurrencyByIds($salesTerm['amountBrutto'], $NewCurrencyId, $this->SaleCurrency));
                    break;
            }
        }

        $this->calculateRestPaymentAmount();
    }

    /**
     * @return Currency
     */
    public function getSaleCurrency()
    {
        return CurrencyStorage::getById($this->SaleCurrency);
    }

    /**
     * @return Currency
     */
    public function getSupplierCurrency()
    {
        return CurrencyStorage::getById($this->SupplierCurrency);
    }

    /**
     * Проверка факта оплаты услуги
     * @return bool
     */
    public function isPaid()
    {
        return $this->isPaid == 1;
    }

    /**
     * Получение данных для логирования заявки
     * @return string
     */
    public function getLogData()
    {
        return "Создана услуга № $this->ServiceID ({$this->getServiceName()})";
    }

    /**
     * Получение данных шлюза из услуги
     * @return array
     */
    public function getEngineData()
    {
        $Offer = $this->getOffer();
        return $Offer->getEngineData();
    }

    /**
     * @param string $DateStart
     */
    public function setDateStart($DateStart)
    {
        $this->DateStart = date('Y-m-d H:i:s', strtotime($DateStart));
    }

    /**
     * @param mixed $DateFinish
     */
    public function setDateFinish($DateFinish)
    {
        $this->DateFinish = date('Y-m-d H:i:s', strtotime($DateFinish));
    }

    /**
     * @return string
     */
    public function getDateStart()
    {
        return $this->DateStart;
    }

    /**
     * @return string
     */
    public function getDateEnd()
    {
        return $this->DateFinish;
    }

    /**
     * @return mixed
     */
    public function getKmpPrice()
    {
        return $this->KmpPrice;
    }

    /**
     * @return string
     */
    public function getSupplierPrice()
    {
        return $this->SupplierPrice;
    }

    /**
     * Проверка что услуга оффнлайн
     * @return bool
     */
    public function isOffline()
    {
        return $this->Offline == 1;
    }

    /**
     * @param mixed $Offline
     */
    public function setOffline($Offline)
    {
        $this->Offline = ($Offline) ? 1 : 0;
    }

    /**
     * @param int $Status
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
     * @return mixed
     */
    public function getServiceIDGP()
    {
        return $this->ServiceID_GP;
    }

    /**
     * @param int $ServiceID_GP
     */
    public function setServiceIDGP($ServiceID_GP)
    {
        $this->ServiceID_GP = (int)$ServiceID_GP;
    }

    /**
     * @return string|null
     */
    public function getServiceIDUTK()
    {
        return $this->ServiceID_UTK;
    }

    /**
     * @param string $utkServiceId
     */
    public function setServiceIDUTK($utkServiceId)
    {
        $this->ServiceID_UTK = $utkServiceId;
    }

    /**
     * Возвращает код сообщения из таблицы kt_messages
     */
    public function getStatusMsgCode()
    {
        return $this->statusMsgCodes[$this->getStatus()];
    }

    /**
     * Добавление туриста к услуге с валидацией
     * @param OrderTourist $OrderTourist
     * @param array $touristData
     * @throws Exception
     */
    public function addTourist(OrderTourist $OrderTourist, array $touristData)
    {
        $Offer = $this->getOffer();
        // попробуем создать связь
        $ServiceTourist = $this->serviceTouristFactory();
        $ServiceTourist->setOffer($Offer);
        $ServiceTourist->setTourist($OrderTourist->getTourist());
        $ServiceTourist->setTouristDocument($OrderTourist->getDocument());
        $ServiceTourist->validate();

        // поищем туриста, может он уже присоединен к услуге
        $OrdersServicesTourist = OrdersServicesTourists::model()->findByAttributes(['ServiceID' => $this->ServiceID, 'TouristID' => $OrderTourist->getTouristID()]);
        if (is_null($OrdersServicesTourist)) {
            /**
             * Проверим наличие мест в услуге
             */
            // найдем всех текущих туристов в услуге
            $CurrentOrderTourists = $this->getOrderTourists();
            if (count($CurrentOrderTourists)) {
                foreach ($CurrentOrderTourists as $CurrentOrderTourist) {
                    $Tourists[] = $CurrentOrderTourist->getTourist();
                }
            }
            // подкинем нового туриста, которого хотим добавить
            $Tourists[] = $OrderTourist->getTourist();

            // вместе с новым туристом проверим места
            if (!$Offer->checkTouristAges($Tourists)) {
                throw new ServiceTouristException(OrdersErrors::INCORRECT_OFFER_AGE_GROUPS);
            }

            $OrdersServicesTourist = new OrdersServicesTourists();
        }

        $OrdersServicesTourist->setTouristID($OrderTourist->getTouristID());
        if (isset($touristData['aviaLoyalityProgrammId']) && $touristData['aviaLoyalityProgrammId']) {
            $OrdersServicesTourist->setLoyalityProgramId($touristData['aviaLoyalityProgrammId']);
        }
        if (isset($touristData['bonuscardNumber']) && $touristData['bonuscardNumber']) {
            if (!$OrdersServicesTourist->setMileCard($touristData['bonuscardNumber'])) {
                throw new ServiceTouristException(OrdersErrors::INCORRECT_MILECARD_NUMBER);
            }
        }
        $OrdersServicesTourist->setServiceID($this->ServiceID);

        if (!$OrdersServicesTourist->save()) {
            throw new Exception(OrdersErrors::DB_ERROR);
        }
    }

    /**
     * Флаг о том, что услуга рождена в КТ
     */
    public function setKtService()
    {
        $this->ktService = 1;
    }

    /**
     * Проверка рождения услуши в кт
     * @return bool
     */
    public function isKtService()
    {
        return $this->ktService == 1;
    }

    /**
     * удаление туриста из услуги
     * @param $touristId
     * @return Tourist
     * @throws ServiceTouristException
     */
    public function detachTourist($touristId)
    {
        $OrdersServicesTourist = OrdersServicesTourists::model()->findByAttributes(['ServiceID' => $this->ServiceID, 'TouristID' => $touristId]);

        if (!is_null($OrdersServicesTourist)) {
            $OrdersServicesTourist->delete();
        } else {
            throw new ServiceTouristException(OrdersErrors::NO_LINKED_TOURIST_TO_SERVICE);
        }

        // найдем туриста
        $OrderTourist = OrderTourist::model()->findByPk($touristId);
        return $OrderTourist->getTourist();
    }

    /**
     * Получение
     * @return mixed
     */
    public function getAgencyProfit()
    {
        return $this->AgencyProfit;
    }

    /**
     * Установка данных агентской комиссии
     * @param mixed $AgencyProfit
     */
    public function setAgencyProfit($AgencyProfit)
    {
        // если записана заявка, то проверим корректность комиссии по контракту
        $order = $this->getOrderModel();
        if ($order && !$this->isOffline()) {
            $commissionPercent = $this->getOrderModel()->getContract()->getCommission();
            if ($commissionPercent) {
                $maxCommission = $commissionPercent / 100 * $this->getKmpPrice();

                if (round($AgencyProfit, 2) > round($maxCommission, 2)) {
                    throw new InvalidArgumentException("Комиссия задана {$AgencyProfit}, максимальная {$maxCommission}", OrdersErrors::INCORRECT_COMMISSION);
                }
            } else {
                /**
                 * @todo Андрей сказал пока ставить 0, иначе из УТК не проходит,
                 * но вообще у него в голове своя новая бизнес-логика на сей счет
                 */
                $AgencyProfit = 0;
                //throw new InvalidArgumentException('По контракту макс комиссия - 0', OrdersErrors::MAX_COMMISSION_ZERO);
            }
        }


        $this->AgencyProfit = $AgencyProfit;

        $prices = $this->getServicePrices();
        foreach ($prices as $price) {
            if ($price->isClient()) {
                $price->setCommission($AgencyProfit);
                $price->save(false);
            }
        }
    }

    /**
     * @param float $price
     */
    public function setSupplierPrice($price)
    {
        $this->SupplierPrice = $price;
    }

    /**
     * @param float $price
     */
    public function setKmpPrice($price)
    {
        $this->KmpPrice = $price;
    }

    /**
     * @param int $currency валюта в цифровом ISO коде
     */
    public function setSupplierCurrency($currency)
    {
        $this->SupplierCurrency = $currency;
    }

    /**
     * @param int $currency валюта в цифровом ISO коде
     */
    public function setSaleCurrency($currency)
    {
        $this->SaleCurrency = $currency;
    }

    /**
     * @param float $penalty
     */
    public function setSalePenalty($penalty)
    {
        $this->SalePenalty = $penalty;
    }

    /**
     * @param int $currency валюта в цифровом ISO коде
     */
    public function setSalePenaltyCurrency($currency)
    {
        $this->SalePenaltyCurr = $currency;
    }

    /**
     * @param float $penalty
     */
    public function setNetPenalty($penalty)
    {
        $this->NetPenalty = $penalty;
    }

    /**
     * @param int $currency валюта в цифровом ISO коде
     */
    public function setNetPenaltyCurrency($currency)
    {
        $this->NetPenaltyCurr = $currency;
    }

    /**
     *
     * @return OrderServicePenalty[]|null
     */
    public function getOrderServicePenalties()
    {
        return $this->OrderServicePenalties;
    }

    /**
     *
     * @return OrderServicePrice[]|null
     */
    public function getOrderServicePrices()
    {
        return $this->OrderServicePrices;
    }

    /**
     * Обновление данных штрафа
     * @param $so_servicePenalties
     * @throws InvalidArgumentException
     * @throws DomainException
     */
    public function setPenalties($so_servicePenalties)
    {
        $ExistedServicePenalties = $this->getOrderServicePenalties();

        if (count($ExistedServicePenalties)) {
            foreach ($ExistedServicePenalties as $ExistedServicePenalty) {
                $OrderServicePenalty = $ExistedServicePenalty;
            }
        }

        // если не нашли штраф такого типа в услугу, то создадим новый
        if (!isset($OrderServicePenalty)) {
            $OrderServicePenalty = new OrderServicePenalty();
            $OrderServicePenalty->setServiceId($this->ServiceID);
        }

        $OrderServicePenalty->fromArray($so_servicePenalties);

        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();

        $violations = $validator->validate($OrderServicePenalty);
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                throw new InvalidArgumentException('Валидация штрафа', $violation->getMessage());
            }
        }

        if (!$OrderServicePenalty->save(false)) {
            throw new DomainException('Не удалось сохранить штрафы в услуге');
        }
    }

    /**
     * Обновление, установка ценовых данных
     * @param $type
     * @param array $saleTerm
     */
    public function setSaleTerm($type, array $saleTerm)
    {
        $ExistedOrderServicePrices = $this->getOrderServicePrices();

        if (count($ExistedOrderServicePrices)) {
            foreach ($ExistedOrderServicePrices as $ExistedOrderServicePrice) {
                if ($ExistedOrderServicePrice->getType() == $type) {
                    $OrderServicePrice = $ExistedOrderServicePrice;
                    break;
                }
            }
        }

        // если не нашли цены, то создадим новые
        if (!isset($OrderServicePrice)) {
            $OrderServicePrice = new OrderServicePrice();
            $OrderServicePrice->setType($type);
            $OrderServicePrice->setServiceId($this->ServiceID);
        }

        $OrderServicePrice->fromArray($saleTerm);

        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();

        $violations = $validator->validate($OrderServicePrice);
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                throw new InvalidArgumentException('Валидация цены в заявке', (int)$violation->getMessage());
            }
        }

        if (!$OrderServicePrice->save(false)) {
            throw new DomainException('Не удалось сохранить цены в услуге');
        }

//        $this->fromPriceOffer($OrderServicePrice);
//        $offer = $this->getOffer();
//        $offer->updatePrices($type, $OrderServicePrice->toArray());
//        $offer->save(false);

        $this->calculateRestPaymentAmount();
    }

    /**
     * Вычисление маржи КМП из ценовых данных
     * @param Currency $Currency
     * @return int
     */
    protected function getKMPMarginInCurrency(Currency $Currency)
    {
        $OrderServicePrices = $this->getOrderServicePrices();

        $bruttoKMP = 0;
        $nettoKMP = 0;

        if (count($OrderServicePrices)) {
            foreach ($OrderServicePrices as $OrderServicePrice) {
                $OrderServicePrice->convertPricesIntoCurrency($Currency, CurrencyRates::getInstance());

                if ($OrderServicePrice->isClient()) {
                    $bruttoKMP = $OrderServicePrice->getBrutto();
                } elseif ($OrderServicePrice->isSupplier()) {
                    $nettoKMP = $OrderServicePrice->getBrutto();
                }
            }
        } else {
            throw new LogicException('Нельзя вычислить маржу без цен');
        }

        return $bruttoKMP - $nettoKMP;
    }


//    /**
//     * ПОлучение структуры sl_modifyService
//     * @return array
//     */
//    public function getSLModifyService()
//    {
//        return [
//            'serviceId' => $this->ServiceID,       // Идентификатор сущности
//            'status' => $this->Status,                 // Статус
//            'serviceType' => $this->ServiceType,            // Тип услуги
//            'dateStart' => $this->DateStart,  // Начало действия сервиса
//            'dateFinish' => $this->DateFinish, // Окончание действия сервиса
//            'dateAmend' => $this->DateAmend,   // Таймлимит на оплату
//            'offline' => $this->Offline             // Признак офлайновой заявки
//        ];
//    }

    public function getSalesTerms()
    {
        $prices = $this->getOrderServicePrices();

        $salesTerms = [];

        foreach ($prices as $price) {
            $salesTerms[$price->getType()] = $price->toArray();
        }

        return $salesTerms;
    }

    /**
     * Получение ценовых данных оффера
     * @return SalesTermsInfo
     */
    public function getSalesTermsInfo()
    {
        // получение структуры $ss_salesTermsInfo
        $priceOffers = $this->getOrderServicePrices();
        if (count($priceOffers)) {
            $SalesTermsInfo = new SalesTermsInfo();
            foreach ($priceOffers as $priceOffer) {
                $SalesTermsInfo->addPrice($priceOffer);
            }
            $SalesTermsInfo->addCurrency('client', $this->getSaleCurrency());
            $SalesTermsInfo->addCurrency('supplier', $this->getSupplierCurrency());
            $SalesTermsInfo->addCurrency('local', CurrencyStorage::findByString(643));

            return $SalesTermsInfo;
        } else {
            return null;
        }
    }

    public function getSLOrderService()
    {
        $cancelPenalties = $this->getOffer()->getCancelPenalties();

        $cancelPenaltiesArr = null;

        foreach ($cancelPenalties as $cancelPenalty) {
            $cancelPenaltiesArr[$cancelPenalty->getType()][] = $cancelPenalty->getArray();
        }

        return [
            'serviceId' => $this->ServiceID,                // Идентификатор сущности
            'serviceId_Utk' => $this->ServiceID_UTK,        // ID сервиса в УТК
            'serviceId_Gp' => $this->ServiceID_GP,          // ID сервиса в GPTS
            'status' => $this->Status,                      // Статус
            'statusName' => $this->getServiceStatusName(),                  // Статус
            'serviceType' => $this->ServiceType,            // Тип услуги
            'serviceName' => $this->getServiceName(),            // Название услуги (формируемое для UI)
            'offerId' => $this->OfferID,                    // Идентификатор предложения (сохранённого в услуге)
            'dateStart' => $this->DateStart,                // Начало действия сервиса
            'dateFinish' => $this->DateFinish,              // Окончание действия сервиса
            'dateAmend' => $this->DateAmend,                // Таймлимит на оплату
            'dateOrdered' => $this->DateOrdered,            // Дата создания
            'salesTerms' => $this->getSalesTerms(),         // Ценовые компоненты услуги, структура     ss_salesTerms
            'discount' => 0,                                // Предоставленная по услуге скидка (валюта продажи)
            'agreementSet' => 1,                            // Признак, что клиент согласился с условиями оферты и тарифов
            'offline' => $this->Offline,                    // Признак офлайновой заявки
            'modificationAllowed' => false,                 // Признак возможности модификации услуги
            'cancellationAllowed' => true,                  // Признак возможности отмены услуги
            'countryId' => 85,                              // ID страны оказания услуги из справочника стран kt_ref_countries
            'cityId' => 2903,                               // ID города оказания услуги из справочника городов kt_ref_cities
            'servicePenalties' => '',                       // Начисленные в услуге штрафы, структура so_servicePenalties
            'cancelPenalties' => $cancelPenaltiesArr,
            'addService' => [],                             // Массив дополнительных услуг
            'addServicesAvailable' => true,                 // Возможность добавления доп.услуги к основной: true - можно  доба
            'travelPolicyFailCodes' => $this->getOffer()->getOfferValue()->getFailCodes(),
            'dateCreate' => $this->DateCreate               // Дата создания офера
        ];
    }

    /**
     * Получение названия статуса из таблицы kt_messages
     * @return string
     */
    public function getServiceStatusName()
    {
        $message = MessageRepository::getByIdAndLang($this->getStatusMsgCode(), $this->getLang());
        if ($message) {
            return $message->getMessage();
        }

        return '';
    }

    public function isDatesStartAndFinishValid()
    {
        $DateStartDateTime = new DateTime($this->DateStart);
        $DateFinishDateTime = new DateTime($this->DateFinish);
        return ($DateFinishDateTime->getTimestamp() - $DateStartDateTime->getTimestamp()) >= 0;
    }

//    /**
//     * Агентская комиссия должна быть в диапазоне между ценами брутто и нетто клиента
//     * @return bool
//     */
//    public function isAgencyProfitValid()
//    {
//        return $this->AgencyProfit <= $this->getKMPMarginInCurrency($this->getSaleCurrency());
//    }

    /**
     * Подсчет остатка к оплате по услуге в валюте поставщика с учетом выставленных счетов
     */
    public function calculateRestPaymentAmount()
    {
        // выберем все счета услуги
        $InvoiceServices = InvoiceServiceRepository::getAllNotCancelledByServiceId($this->ServiceID);

        $sumOfServiceInvoicesInSupplierCurrency = 0;

        // посчитаем по ним сумма в валюте поставщика
        if ($InvoiceServices) {
            foreach ($InvoiceServices as $InvoiceService) {
                $sumOfServiceInvoicesInSupplierCurrency += CurrencyRates::getInstance()->calculateInCurrencyByIds($InvoiceService->getSum(), $InvoiceService->getCurrency()->getId(), $this->SupplierCurrency);
            }
        }

        $saleSumInSupplierCurrency = CurrencyRates::getInstance()->calculateInCurrencyByIds($this->KmpPrice, $this->SaleCurrency, $this->SupplierCurrency);

        // посчитаем остаток цены в валюте поставщика
        $calculatedSum = $saleSumInSupplierCurrency - $sumOfServiceInvoicesInSupplierCurrency;

        if ($calculatedSum > 0) {
            $this->RestPaymentAmount = $calculatedSum;
        } else {
            $this->RestPaymentAmount = 0;
        }
    }

    public function cancel()
    {
        switch ($this->ServiceType) {
            case 1:
                $this->Status = self::STATUS_VOIDED;
                break;
            case 2:
                $this->Status = self::STATUS_CANCELLED;
                break;
            default:

                break;
        }

        // отмена доп услуг
        $addServices = $this->getAddServices();
        foreach ($addServices as $addService) {
            $addService->cancel();
            $addService->save(false);
        }

        $this->RestPaymentAmount = 0;
    }

    /**
     * @param mixed $RestPaymentAmount
     */
    public function setRestPaymentAmount($RestPaymentAmount)
    {
        $this->RestPaymentAmount = $RestPaymentAmount;
    }

    /**
     * Проверка возможности выставления
     * @param $amount
     * @param Currency $currency
     * @return bool
     */
    public function canSetInvoiceServiceWithAmount($amount, Currency $currency)
    {
        if ($this->RestPaymentAmount == 0) {
            return true;
        }

        $amountInSupplierCurrency = CurrencyRates::getInstance()->calculateInCurrencyByIds($amount, $currency->getId(), $this->SupplierCurrency);
        return $this->RestPaymentAmount >= $amountInSupplierCurrency;
    }

    /**
     * Начисление штрафа
     * @param AbstractCancelPenalty $cancelPenalty
     */
    public function createServicePenaltyFromCancelPenalty(AbstractCancelPenalty $cancelPenalty)
    {
        $orderServicePenalty = new OrderServicePenalty();
        $orderServicePenalty->bindService($this);
        $orderServicePenalty->fromClientCancelPenalty($cancelPenalty);
        $orderServicePenalty->save(false);
    }

    /**
     * Создание счета на услугу
     * @param $invoiceId
     * @param $amount
     * @param Currency $currency
     * @return InvoiceService|null
     */
    public function setInvoice($invoiceId, $amount, Currency $currency)
    {
        $amountInSupplierCurrency = CurrencyRates::getInstance()->calculateInCurrencyByIds($amount, $currency->getId(), $this->SupplierCurrency);

        $InvoiceService = new InvoiceService();
        $InvoiceService->setInvoiceId($invoiceId);
        $InvoiceService->setSum($amount, $currency);
        $InvoiceService->bindOrderService($this);
        $InvoiceService->setPartial($this->RestPaymentAmount >= $amountInSupplierCurrency);

        if (!$InvoiceService->save(false)) {
            return null;
        }

        return $InvoiceService;
    }

    public function payStartStatus()
    {
        $newStatus = $this->Status;

        switch ($this->Status) {
            case self::STATUS_PAID:
            case self::STATUS_BOOKED:
            case self::STATUS_MANUAL:
                $newStatus = self::STATUS_W_PAID;
                break;
            default:
                break;
        }

        $this->Status = $newStatus;
    }

    /**
     * В услуге есть причины нарушения корпоративных политик
     * @return bool
     */
    public function hasTPViolations()
    {
        return !empty($this->getOffer()->getOfferValue()->getFailCodes());
    }

    /**
     * Причины нарушения корпоративных политик
     * @return array
     */
    public function getTPViolations()
    {
        return $this->getOffer()->getOfferValue()->getFailCodes();
    }

    /**
     * @return AbstractMinimalPrice
     */
    public function getMinimalPrice()
    {
        $minimalPriceAddField = OrderAdditionalFieldRepository::getServiceMinimalPriceField($this);

        if (is_null($minimalPriceAddField)) {
            return null;
        }

        $value = json_decode($minimalPriceAddField->getValue(), true);

        if ($value) {
            return AbstractMinimalPrice::createConcreteMinimalPriceClass($this, $value);
        }

        return null;
    }

    /**
     * @return OrdersServicesAddService[]
     */
    public function getAddServices()
    {
        return $this->addServices;
    }

    /**
     * Возвращает оффера оформленных доп услуг
     * @return AbstractHotelAddOffer[]
     */
    public function getAddOffers()
    {
        $addedAddOffers = [];

        // найдем id офферов доп услуг
        $addOfferIds = [];
        $addServices = $this->getAddServices();
        foreach ($addServices as $addService) {
            $addOfferIds[] = $addService->getOfferId();
        }

        // найдем доп оффера с такими id
        $addOffers = $this->getOffer()->getAddOffers();
        foreach ($addOffers as $addOffer) {
            if (in_array($addOffer->getId(), $addOfferIds)) {
                $addedAddOffers[] = $addOffer;
            }
        }

        return $addedAddOffers;
    }

    /**
     * Создание доп услуги
     * Добавление стоимости доп услуги к основной
     * пересчет RestPaymentAmount
     * @param $addOfferId
     * @param $required
     * @return OrdersServicesAddService
     */
    public function createAddService($addOfferId, $required)
    {
        $addOffers = $this->getOffer()->getAddOffers();

        foreach ($addOffers as $addOffer) {
            if ($addOffer->getId() == $addOfferId) {
                break;
            }
        }

        // просто скопируем все данные
        $addService = new OrdersServicesAddService();
        $addService->createFromAddOffer($addOffer);
        $addService->setRequired($required);
        $addService->bindService($this);
        $addService->save(false);

        // запишем цены
        $addOfferPrices = $addOffer->getAddOfferPrices();

        foreach ($addOfferPrices as $addOfferPrice) {
            $addServicePrice = new OrdersServicesAddServicePrice();
            $addServicePrice->bindAddService($addService);
            $addServicePrice->setType($addOfferPrice->getType());
            $addServicePrice->fromArray($addOfferPrice->toArray());
            $addServicePrice->save(false);
        }

        // просуммируем цены на доп услугу
        $servicePrices = $this->getServicePrices();

        foreach ($addOfferPrices as $addOfferPrice) {
            foreach ($servicePrices as $servicePrice) {
                if ($servicePrice->getType() == $addOfferPrice->getType()) {
                    $servicePrice->sumSalesTerm($addOfferPrice->toArray());
                    $servicePrice->save(false);
                }
            }

            if ($addOfferPrice->isClient()) {
                $this->KmpPrice += $addOfferPrice->getBrutto();
            } elseif ($addOfferPrice->isSupplier()) {
                $this->SupplierPrice += $addOfferPrice->getBrutto();
            }
        }

        // пересчитаем RestPaymentAmount
        $this->calculateRestPaymentAmount();

        return $addService;
    }

    /**
     * Удаление доп услуги из основной услуги
     * Вычитаем цену доп услуги
     * пересчет RestPaymentAmount
     * @param $addServiceId
     */
    public function removeAddService($addServiceId)
    {
        $addServices = $this->getAddServices();

        foreach ($addServices as $addService) {
            if ($addService->getId() == $addServiceId) {
                break;
            }
        }

        // вычтем цены на доп услугу
        $servicePrices = $this->getServicePrices();
        $subServicePrices = $addService->getAddOfferPrices();
        foreach ($subServicePrices as $subServicePrice) {
            foreach ($servicePrices as $servicePrice) {
                if ($servicePrice->getType() == $subServicePrice->getType()) {
                    $servicePrice->diffSalesTerm($subServicePrice->toArray());
                    $servicePrice->save(false);
                }
            }

            if ($subServicePrice->isClient()) {
                $this->KmpPrice -= $subServicePrice->getBrutto();
            } elseif ($subServicePrice->isSupplier()) {
                $this->SupplierPrice -= $subServicePrice->getBrutto();
            }
        }

        // пересчитаем RestPaymentAmount
        $this->calculateRestPaymentAmount();

        $addService->delete();
    }

    /**
     * Делаем услугу новой
     */
    public function makeNew()
    {
        $this->Status = self::STATUS_NEW;

        $addServices = $this->getAddServices();
        foreach ($addServices as $addService) {
            $addService->makeNew();
            $addService->save(false);
        }
    }

    /**
     * Делаем услугу забронированной
     */
    public function makeBooked()
    {
        $this->Status = self::STATUS_BOOKED;

        $addServices = $this->getAddServices();
        foreach ($addServices as $addService) {
            // только доп питание подтверждается вместе с основной услугой
            if ($addService->getSubService()->getId() == 1) {
                $addService->makeBooked();
                $addService->save(false);
                break; // больше здесь делать нечего
            }
        }
    }

    /**
     * Делаем услугу забронированной
     */
    public function makeWBooked()
    {
        $this->Status = self::STATUS_W_BOOKED;

        $addServices = $this->getAddServices();
        foreach ($addServices as $addService) {
            $addService->makeWBooked();
            $addService->save(false);
        }
    }

    /**
     * Делаем услугу отменённой
     */
    public function makeCancelled()
    {
        $this->Status = self::STATUS_CANCELLED;

        $addServices = $this->getAddServices();
        foreach ($addServices as $addService) {
            $addService->cancel();
            $addService->save(false);
        }
    }

    /**
     * Делаем услугу в обработку
     */
    public function makeManual()
    {
        $this->Status = self::STATUS_MANUAL;

        $addServices = $this->getAddServices();
        foreach ($addServices as $addService) {
            $addService->makeManual();
            $addService->save(false);
        }
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->Extra;
    }

    /**
     * @param mixed $Extra
     */
    public function setComment($Extra)
    {
        $this->Extra = $Extra;
    }

    /**
     * @return RefServices
     */
    public function getRefService()
    {
        return $this->RefService;
    }

    public function getTimeIntervalDays()
    {
        $DateFrom = new DateTime($this->DateStart);
        $DateTo = new DateTime($this->DateFinish);

        if ($interval = $DateTo->diff($DateFrom)) {
            return $interval->days;
        }

        return false;
    }

    /**
     * Получение цены
     * @param Currency $inCurrency
     * @return Money
     */
    public function getMoney(Currency $inCurrency)
    {
        $priceInRub = CurrencyRates::getInstance()->calculateInCurrencyByIds($this->KmpPrice, $this->getSaleCurrency()->getId(), $inCurrency->getId());
        return new Money($priceInRub, $inCurrency);
    }

    /**
     * Перезапишем save для удобства
     * чтобы всегда было без валидации
     * @return bool
     */
    public function save($runValidation = true, $attributes = null)
    {
        return parent::save(false);
    }

    /**
     * Требования к услуге для старта бронирования
     * @param ClassMetadata $metadata
     */
    static public function bookingRules(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('DateStart', new Assert\GreaterThan(array('value' => 'today', 'message' => OrdersErrors::SERVICE_DATE_START_INCORRECT)));
        $metadata->addPropertyConstraint('DateFinish', new Assert\LessThan(array('value' => '+1 year', 'message' => OrdersErrors::SERVICE_DATE_END_INCORRECT)));
    }

    /**
     * Общие требования к услуге
     * @param ClassMetadata $metadata
     */
    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('DateStart', new Assert\GreaterThan(array('value' => '2000-01-01', 'message' => OrdersErrors::SERVICE_DATE_START_INCORRECT)));
        $metadata->addPropertyConstraint('DateFinish', new Assert\GreaterThan(array('value' => '2000-01-01', 'message' => OrdersErrors::SERVICE_DATE_END_INCORRECT)));
        $metadata->addGetterConstraint('DatesStartAndFinishValid', new Assert\IsTrue(array('message' => OrdersErrors::SERVICES_DATES_INCORRECT)));
        $metadata->addPropertyConstraint('SupplierPrice', new Assert\GreaterThanOrEqual(array('value' => 0, 'message' => OrdersErrors::SERVICE_PRICE_INCORRECT)));
        $metadata->addPropertyConstraint('KmpPrice', new Assert\GreaterThanOrEqual(array('value' => 0, 'message' => OrdersErrors::SERVICE_PRICE_INCORRECT)));
        $metadata->addPropertyConstraint('AgencyProfit', new Assert\GreaterThanOrEqual(array('value' => 0, 'message' => OrdersErrors::AGENCY_PROFIT_INCORRECT)));
//        $metadata->addGetterConstraint('AgencyProfitValid', new Assert\IsTrue(array('message' => OrdersErrors::AGENCY_PROFIT_INCORRECT)));
    }

    /**
     * @return mixed
     */
    public function getDateCreate()
    {
        return $this->dateCreate;
    }


}