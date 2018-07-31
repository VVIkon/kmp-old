<?php

/**
 * Class UtkService
 * Реализует функциональности управления услугой УТК
 */
class UtkService extends KFormModel implements IService
{
    const STATUS_ACTIVE = 1;
    const STATUS_DELETED = 2;
    /**
     * Идентифкатор заявки
     * @var int
     */
    public $orderId;

    /**
     * Идентифкатор услуги
     * @var int
     */
    public $serviceId;

    /**
     * Тип услуги
     * @var string
     */
    public $serviceType;

    /**
     * Идентифкатор услуги в Gpts
     * @var
     */
    public $serviceGptsId;

    /**
     * Идентифкатор услуги в УТК
     * @var int
     */
    public $serviceIDUtk;

    /**
     * Время последнего обновления услуги
     * @var string
     */
    public $serviceDateUpdate;

    /**
     * Текстовое описание статуса услуги
     * @var string
     */
    public $status;

    /**
     * Дата начала действия услуги
     * @var string
     */
    public $startDateTime;

    /**
     * Дата окончания действия услуги
     * @var string
     */
    public $endDateTime;

    /**
     * Идентификатор услуги в системе поставщика
     * @var string
     */
    public $refNum;

    /**
     * Идентифкатор поставщика услуги
     * @var int
     */
    public $supplierId;

    /**
     * Идентифкатор поставщика услуги
     * @var int
     */
    public $supplierIdUtk;

    /**
     * Название компании поставщика услуги
     * @var string
     */
    public $supplierCompanyName;

    /**
     * Продажная сумма в валюте продажи
     * @var float
     */
    public $saleSum;

    /**
     * Cумма поставщика в валюте поставщика
     * без комиссии агента
     * @var float
     */
    public $supplierPrice;

    /**
     * Валюта поставщика
     * @var float
     */
    public $netCurrency;

    /**
     * Валюта продажи
     * @var float
     */
    public $saleCurrency;

    /**
     * Процент комиссии агента
     * @var float
     */
    public $commission;

    /**
     * Сумма комиссии агента
     * в валюте продажи
     * @var float
     */
    public $commissionSum;

    /**
     * Идентифкатор тура услуги
     * в валюте продажи
     * @var float
     */
    public $serviceTour;

    /**
     * Идентифкатор предложения услуги
     * в валюте продажи
     * @var float
     */
    public $offerId;

    /**
     * Признак того, что услуга сформирована оффлайн(непосредственно в УТК)
     * @var bool
     */
    public $offline;

    /**
     * Комментарий
     * @var string
     */
    public $description;

    /**
     * Дополнительная информация
     * @var array
     */
    public $extra;

    /**
     * Идентифкатор города услуги
     * @var int
     */
    public $cityId;

    /**
     * Идентифкатор страны услуги
     * @var int
     */
    public $countryId;

    /**
     * Наименование услуги
     * @var string
     */
    public $serviceName;

    /**
     * Идентифкатор родительской услуги в КТ
     * @var int
     */
    public $parentServiceId;

    /**
     * Детали услуги
     * @var array
     */
    public $serviceDetails;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules() {
        return array(
            ['orderId,serviceID,serviceGptsId,serviceType,serviceIDUtk,serviceDateUpdate,status,
            startDateTime,endDateTime,refNum,supplierId,supplierIdUtk,offline,
            supplierCompanyName,saleSum,supplierPrice,saleSum,netSum,netCurrency,
            saleCurrency,commission,commissionSum,serviceName,description,parentServiceId,serviceDetails,
            cityId,countryId', 'safe']
        );
    }

    /**
     * Получение атрибутов специфичных для услуги
     * @return array
     */
    public function getExAttributes() {
        return [];
    }

    /**
     * Задание атрибутов специфичных для услуги
     * @param $attrs
     */
    public function setExAttributes($attrs) {

       /* $exProperties  = ['serviceName', 'supplierId','serviceDescription','paymentCurrencyCode'];

        foreach ($exProperties as $exProperty) {

            if (isset($attrs[$exProperty]) && !empty($attrs[$exProperty])) {
                $this->$exProperty = $attrs[$exProperty];
            }
        }*/
    }

    /**
     * Получение атрибутов группы услуг специфичных для указанного типа услуги
     * @param $services
     * @return array
     */
    public function getServicesGroupExAttributes($services) {
        return [];
    }

    public function setAttributes($params, $safeOnly = true) {

        parent::setAttributes($params, $safeOnly);

        return true;
    }

    public function save() {

        if (empty($this->serviceID)) {
            return $this->serviceAdd();
        }

    }

    public function serviceAdd() {

        if (empty($this->orderId)) {
            return null;
        }

        $command = Yii::app()->db->createCommand();

        $res = $command->insert('kt_orders_services', array(
            'ServiceID_UTK'     => $this->serviceIDUtk,
            'ServiceID_GP'      => $this->serviceGptsId,
            'Status'            => $this->status,
            'ServiceType'       => $this->serviceType,
            'TourID'            => $this->serviceTour,
            'OfferID'           => $this->offerId,
            'DateStart'         => $this->startDateTime,
            'DateFinish'        => $this->endDateTime,
            'AmendAllowed'      => '',
            'DateAmend'         => '',
            'DateOrdered'       => $this->serviceDateUpdate,

            'SupplierPrice'     => $this->supplierPrice,
            'KmpPrice'          => $this->saleSum - $this->commission,
            'AgencyProfit'      => $this->commission,
            'SupplierCurrency'  => $this->netCurrency,
            'SaleCurrency'      => $this->saleCurrency,
            'Offline'           => $this->offline,
            'OrderID'           => $this->orderId,
            'Extra'             => $this->extra,
            'CityID'            => $this->cityId,
            'CountryID'         => $this->countryId,
            'ServiceName'       => $this->serviceName,
            'SupplierID'        => $this->supplierId,
            'SupplierSvcID'     => $this->refNum,
            'ServiceID_main'    => $this->parentServiceId
        ));

        $this->serviceId = Yii::app()->db->lastInsertID;

        return $this->serviceId;
    }

    /**
     * Установить ид предложения для услуги
     * @param $offerId
     */
    public function setOfferId($offerId) {
        $this->offerId = $offerId;
    }

    /**
     * Вывод атрибутов услуги в виде отфильтрованного массива
     * @param null $params фильтры
     * @return array атрибуты услуги
     */
    public function toArray($params = NULL) {
        $result = [];

        $reflect = new ReflectionClass($this);
        if ($params != NULL && is_array($params)) {

            foreach ($params as $param) {

                if (isset($param) && !empty($param) && $reflect->hasProperty($param)) {
                    $result[$param] = $this->$param;
                }
            }

        } else {

            $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {

                $prop = $property->getName();
                $result[$prop] = $prop;
            }
        }

        return $result;
    }

    /**
     * Вывод атрибутов услуги в виде массива с указанным шаблоном
     * @return array атрибуты услуги
     */
    public function toArrayLongInfo() {

        return $this->toArray([]);
    }

    /**
     * Вывод атрибутов услуги в виде массива с указанным шаблоном представления
     * @return array атрибуты услуги
     */
    public function toArrayDetailInfo() {

        return $this->toArray([]);
    }

    /**
     * Задать наименование услуги
     * @param $name
     */
    public function setServiceName($name) {
        $this->serviceName = $name;
    }
 }

