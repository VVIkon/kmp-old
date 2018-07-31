<?php

/**
 * Class Service
 * Реализует функциональности управления услугой
 */
class Service extends KFormModel implements IService
{
    /** @var int Идентифкатор заявки */
    public $orderId;

    /** Идентифкатор услуги в Gpts */
    public $serviceGptsId;

    /** Идентифкатор услуги в УТК */
    public $serviceUtkId;

    /** @var int Идентифкатор услуги */
    public $serviceID;

    /** @var int Тип услуги */
    public $serviceType;

    public $serviceTour;

    /** @var string URL иконки типа заявки */
    public $serviceIconURL;

    /** @var int Статус уcлуги */
    public $status;

    /** @var string Дата начала действия услуги */
    public $startDateTime;

    /** @var string Дата окончания действия услуги */
    public $endDateTime;

    /** @var string какая-то дата */
    public $serviceDateUpdate;

    /** @var string Цена услуги в валюте поставщика */
    public $supplierPrice;

    /** @var string Стоимость услуги в валюте продажи */
    public $saleSum;

    /** @var string Код валюты поставщика */
    public $supplierCurrency;

    /** Код валюты оплаты */
    public $saleCurrency;

    public $netCurrency;

    /** @var string Буквенный код валюты поставщика */
    public $supplierCurrencyCode;

    /** @var string Буквенный код валюты договора */
    public $paymentCurrencyCode;

    /** Сумма в валюте договора */
    public $paymentSum;

    /** @var string Комиссия агентства в валюте поставщика для укзанной услуги */
    public $commission;

    /** @var string Комиссия агентства в локальной валюте для укзанной услуги */
    public $localCommission;

    /** @var string Комиссия агентства в запрашиваемой валюте для укзанной услуги */
    public $requestedCommission;

    /** @var string Скидка, предоставленная агенством для указанной услуги */
    public $discount;

    /** @var string Комиссия агентсва по договору */
    public $contractCommission;

    /** @var string Признак разрешения внесения изменений в данные услуги */
    public $amendAllowed;

    /** @var string Дата внесения изменений в услугу */
    public $dateAmend;

    /** @var string Дата формирования услуги */
    public $dateOrdered;

    /** @var string Страна оказания услуги */
    public $countryName;

    /** @var string Код страны оказания услуги */
    public $countryId;

    /** @var string Код страны по классификации IATA */
    public $countryIataCode;

    /** @var string Название города оказания услуги */
    public $cityName;

    /** @var string Код города оказания услуги */
    public $cityId;

    /** @var string Стоимость услуги в валюте поставщика за вычетом комиссии агента */
    public $supplierNetPrice;

    /**  @var string Стоимость услуги в местной валюте */
    public $localSum;

    /** @var string Стоимость услуги в местной валюте за вычетом комиссии агента */
    public $localNetSum;

    /** @var string Стоимость услуги в запрашиваемой валюте */
    public $requestedSum;

    /** @var string Стоимость услуги в запрашиваемой валюте за вычетом комиссии агента */
    public $requestedNetSum;

    /** @var int Идентифкатор поставщика услуги */
    public $supplierId;

    /** Идентифкатор родительской услуги */
    public $parentServiceId;

    /** @var string Наименование услуги */
    public $serviceName;

    /** @var bool Признак того, что услуга сформирована оффлайн(непосредственно в УТК) */
    public $offline;

    /** @var string Идентификатор услуги у поставщика */
    public $supplierServiceId;

    /** Описание услуги */
    public $serviceDescription;

    /** Ид предложения услуги */
    public $offerId;

    /** @var array Детали услуги */
    public $serviceDetails;

    /** @var string Дополнительная информация по услуге */
    public $comment;

    public $refNum;

    /** @var string признак согласия с условиями оказания услуги */
    public $agreementSet;

    public $cancelAbility;

    public $modifyAbility;

    public $Extra;

    /**
     * @var string Штрафы
     */
    public $SalePenalty;
    public $SalePenaltyCurr;
    public $NetPenalty;
    public $NetPenaltyCurr;

    /**
     * Остаток к оплате по услуге в валюте поставщика с учетом выставленных счетов
     * @var float
     */
    public $RestPaymentAmount;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules()
    {
        return array(
            ['orderId,serviceID,serviceUtkId,serviceGptsId,serviceType,serviceTour,serviceIconURL,
            status,startDateTime,endDateTime,serviceDateUpdate,supplierPrice,saleSum,supplierCurrency,
            saleCurrency,netCurrency,supplierCurrencyCode,paymentCurrencyCode,commission,supplierServiceId,
            contractCommission,amendAllowed,dateAmend,dateOrdered,countryName,cityName,countryIataCode,
            parentServiceId,supplierNetPrice,localSum,supplierId,serviceName,offline,serviceDescription,
            offerId,cityId,countryId,serviceDetails,refNum,agreementSet,cancelAbility,modifyAbility,
            SalePenalty,SalePenaltyCurr,NetPenalty,NetPenaltyCurr,RestPaymentAmount,comment,Extra', 'safe']
        );
    }

    /**
     * Переопределение функции установки атрибутов
     * @todo это, наверно, чтоимт переделать, чтобы была реальная польза от проверки функции?
     */
    public function setAttributes($params, $safeOnly = true)
    {
        parent::setAttributes($params, $safeOnly);
        return true;
    }

    /**
     * Получение атрибутов специфичных для услуги
     * @return array
     */
    public function getExAttributes()
    {
        return [];
    }

    /**
     * Задание атрибутов специфичных для услуги
     * @param $attrs
     */
    public function setExAttributes($attrs)
    {

        $exProperties = ['serviceName', 'supplierId', 'serviceDescription', 'paymentCurrencyCode'];

        foreach ($exProperties as $exProperty) {

            if (isset($attrs[$exProperty]) && !empty($attrs[$exProperty])) {
                $this->$exProperty = $attrs[$exProperty];
            }
        }
    }

    /**
     * Получение атрибутов группы услуг специфичных для указанного типа услуги
     * @param $services
     * @return array
     */
    public function getServicesGroupExAttributes($services)
    {
        return [];
    }

    /**
     * Установка стоимости услуги в валюте поставщика за вычетом комиссии агента
     */
    public function setServicePrice()
    {
        $this->supplierPrice = $this->saleSum;
    }

    /**
     * Установка скидки, предоставленной агентством
     * @return null
     */
    public function setServiceDiscount()
    {
        if ($this->supplierPrice > 0) {
            $sumCommissionByContract = ($this->supplierPrice / 100) * $this->contractCommission;
            $this->discount = (floatval($sumCommissionByContract) - floatval($this->commission)) < 0
                ? 0
                : floatval($sumCommissionByContract) - floatval($this->commission);

        } else {
            $this->discount = 0;
        }
    }

    /**
     * Установка стоимости услуги в местной валюте
     * @return null
     */
    public function setServiceLocalSum()
    {
        if (in_array($this->status, [
                ServicesForm::SERVICE_STATUS_NEW,
                ServicesForm::SERVICE_STATUS_W_BOOKED,
                ServicesForm::SERVICE_STATUS_BOOKED,
                ServicesForm::SERVICE_STATUS_W_PAID,
                ServicesForm::SERVICE_STATUS_P_PAID,
                ServicesForm::SERVICE_STATUS_PAID, //added
                ServicesForm::SERVICE_STATUS_DONE,
                ServicesForm::SERVICE_STATUS_MANUAL
            ]
        )) {
            $this->localSum = CurrencyRatesWithTwoPerc::getInstance()->calculateInCurrencyByIds($this->saleSum, $this->saleCurrency, CurrencyRates::RUSSIAN_RUBLE_CURRENCY_ID);
            $this->localCommission = CurrencyRatesWithTwoPerc::getInstance()->calculateInCurrencyByIds($this->commission, $this->saleCurrency, CurrencyRates::RUSSIAN_RUBLE_CURRENCY_ID);
        }
        if (in_array($this->status, [6, 7])) {
            $this->localSum = 0;
            $this->localCommission = 0;
        }
    }

    /**
     * Установка стоимости услуги в местной валюте за вычетом агентской комиссии
     * @return null
     */
    public function setServiceLocalNetSum()
    {
        if (in_array($this->status, [
                ServicesForm::SERVICE_STATUS_NEW,
                ServicesForm::SERVICE_STATUS_W_BOOKED,
                ServicesForm::SERVICE_STATUS_BOOKED,
                ServicesForm::SERVICE_STATUS_W_PAID,
                ServicesForm::SERVICE_STATUS_P_PAID,
                ServicesForm::SERVICE_STATUS_DONE,
                ServicesForm::SERVICE_STATUS_MANUAL
            ]
        )) {
            $this->localNetSum = CurrencyRatesWithTwoPerc::getInstance()->calculateInCurrencyByIds($this->supplierNetPrice, $this->supplierCurrency, CurrencyRates::RUSSIAN_RUBLE_CURRENCY_ID);
        }

        if (in_array($this->status, [5])) {

            $sum = PaymentsForm::getServiceSumByPayments($this->serviceID);

            /*if (!empty($sum)) {
                $sum = $sum - $sum * $this->commission / $this->supplierPrice;
            }*/

            $this->localNetSum = $sum;
        }

        if (in_array($this->status, [6, 7])) {
            $this->localNetSum = 0;
        }
    }

    /**
     * Установка стоимости услуги в заправшиваемой валюте
     * @param $requestedCurrency Currency стоимость запрашиваемой валюты
     * @return null
     */
    public function setServiceRequestedSum($requestedCurrency)
    {
        if ($requestedCurrency == NULL) {
            return NULL;
        }

        if (in_array($this->status, [
                ServicesForm::SERVICE_STATUS_NEW,
                ServicesForm::SERVICE_STATUS_W_BOOKED,
                ServicesForm::SERVICE_STATUS_BOOKED,
                ServicesForm::SERVICE_STATUS_W_PAID,
                ServicesForm::SERVICE_STATUS_P_PAID,
                ServicesForm::SERVICE_STATUS_PAID, //added
                ServicesForm::SERVICE_STATUS_DONE,
                ServicesForm::SERVICE_STATUS_MANUAL
            ]
        )) {
            $this->requestedSum = CurrencyRatesWithTwoPerc::getInstance()->calculateInCurrencyByIds($this->saleSum, $this->supplierCurrency, $requestedCurrency->getId());
            $this->requestedCommission = CurrencyRatesWithTwoPerc::getInstance()->calculateInCurrencyByIds($this->commission, $this->supplierCurrency, $requestedCurrency->getId());
        }

        if (in_array($this->status, [6, 7])) {
            $this->requestedSum = 0;
            $this->requestedCommission = 0;
        }
    }

//    /**
//     * Установка стоимости услуги в валюте договора с агентством
//     * @param $contractCurrency Currency валюта по договору
//     */
//    public function setServiceSumByContractCurrency($contractCurrency)
//    {
//        if (is_null($contractCurrency)) {
//            $this->paymentSum = 0;
//            return;
//        }
//        $this->paymentSum = null;
//    }

    /**
     * Установка стоимости услуги в заправшиваемой валюте за вычетом агентской комиссии
     * @param $requestedCurrency Currency
     * @return null
     */
    public function setServiceRequestedNetSum($requestedCurrency)
    {
        if ($requestedCurrency == NULL) {
            return NULL;
        }

        if (in_array($this->status, [
                ServicesForm::SERVICE_STATUS_NEW,
                ServicesForm::SERVICE_STATUS_W_BOOKED,
                ServicesForm::SERVICE_STATUS_BOOKED,
                ServicesForm::SERVICE_STATUS_W_PAID,
                ServicesForm::SERVICE_STATUS_P_PAID,
                ServicesForm::SERVICE_STATUS_PAID,
                ServicesForm::SERVICE_STATUS_DONE,
                ServicesForm::SERVICE_STATUS_MANUAL
            ]
        )) {
            $this->requestedNetSum = CurrencyRatesWithTwoPerc::getInstance()->calculateInCurrencyByIds($this->supplierNetPrice, $this->supplierCurrency, $requestedCurrency->getId());
        }

        if (in_array($this->status, [6, 7])) {
            $this->requestedNetSum = 0;
        }
    }

    /**
     * Вывод атрибутов услуги в виде отфильтрованного массива
     * @param null $params фильтры
     * @return array атрибуты услуги
     */
    public function toArray($params = NULL)
    {
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
     * Установка ID оффера, привязанного к услуге
     * @param int $offerId ID оффера
     */
    public function setOfferId($offerId)
    {
        $this->offerId = $offerId;
    }

    /**
     * Задать наименование услуги
     * @param $name
     */
    public function setServiceName($name)
    {
        $this->serviceName = $name;
    }

    /**
     * Установка времени начала услуги
     * @param string $sdt дата в формате MySQL YYYY-MM-DD HH:MM:SS
     */
    public function setStartDateTime($sdt)
    {
        $this->startDateTime = $sdt;
    }

    /**
     * Установка времени окончания услуги
     * @param string $edt дата в формате MySQL YYYY-MM-DD HH:MM:SS
     */
    public function setEndDateTime($edt)
    {
        $this->endDateTime = $edt;
    }

    /** Установка ID города услуги */
    public function setCityId($id)
    {
        $this->cityId = $id;
    }

    /** Установка ID страны услуги */
    public function setCountryId($id)
    {
        $this->countryId = $id;
    }

    /** Установка ID поставщика */
    public function setSupplierId($id)
    {
        $this->supplierId = $id;
    }

    /**
     * Вывод атрибутов услуги в виде массива с указанным шаблоном
     * @return array атрибуты услуги
     */
    public function toArrayLongInfo()
    {

        return $this->toArray(['serviceID', 'serviceType', 'serviceName', 'serviceIconURL',
            'status', 'startDateTime', 'endDateTime', 'supplierId', 'requestedSum',
            'requestedNetSum', 'localSum', 'localNetSum', 'localCurrency', 'localCommission',
            'requestedCommission', 'discount', 'amendAllowed', 'dateAmend', 'dateOrdered', 'countryName',
            'cityName', 'countryIataCode', 'offline'
        ]);
    }

    /**
     * Вывод атрибутов услуги в виде массива с указанным шаблоном представления
     * @return array атрибуты услуги
     */
    public function toArrayDetailInfo()
    {
        return $this->toArray(['serviceID', 'serviceType', 'serviceName', 'serviceIconURL',
            'serviceDescription', 'status', 'startDateTime', 'endDateTime', 'supplierId',
            'requestedSum', 'requestedNetSum', 'supplierPrice', 'supplierNetPrice',
            'localSum', 'localNetSum', 'supplierCurrencyCode', 'paymentCurrencyCode', 'paymentSum',
            'localCurrency', 'localCommission', 'requestedCommission', 'discount', 'dateAmend', 'dateOrdered', 'offerId', 'offline', 'RestPaymentAmount', 'comment'
        ]);
    }

    public function save()
    {
        if (empty($this->serviceID)) {
            return $this->serviceAdd();
        } else {
            return $this->serviceUpdate();
        }
    }

    public function serviceAdd()
    {

        if (empty($this->orderId)) {
            return null;
        }

        $command = Yii::app()->db->createCommand();

        try {
            $res = $command->insert('kt_orders_services', [
                'ServiceID_UTK' => $this->serviceUtkId,
                'Status' => $this->status,
                'ServiceType' => $this->serviceType,
                'TourID' => $this->serviceTour,
                'OfferID' => $this->offerId,
                'DateStart' => $this->startDateTime,
                'DateFinish' => $this->endDateTime,
                'AmendAllowed' => false,
                'DateAmend' => null,
                //    'DateOrdered'       => $this->serviceDateUpdate,
                'SupplierPrice' => $this->supplierPrice,
                /** было saleSum - commission */
                'KmpPrice' => $this->saleSum,
                'AgencyProfit' => $this->commission,
                'SupplierCurrency' => $this->supplierCurrency,
                'SaleCurrency' => $this->saleCurrency,
                'OrderID' => $this->orderId,
                'Offline' => $this->offline,
                'Extra' => $this->Extra,
                'CityID' => $this->cityId,
                'CountryID' => $this->countryId,
                'ServiceName' => $this->serviceName,
                'SupplierID' => $this->supplierId,
                'SupplierSvcID' => $this->refNum,
                'agreementSet' => $this->agreementSet,
                'SalePenalty' => $this->SalePenalty,
                'SalePenaltyCurr' => $this->SalePenaltyCurr,
                'NetPenalty' => $this->NetPenalty,
                'NetPenaltyCurr' => $this->NetPenaltyCurr,
                'ktService' => 1
            ]);

            $this->serviceID = Yii::app()->db->lastInsertID;
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(), __FUNCTION__,
                OrdersErrors::CANNOT_CREATE_SERVICE,
                $command->getText(),
                $e
            );
        }

        return $this->serviceID;
    }

    public function serviceUpdate()
    {

        if (empty($this->orderId)) {
            return null;
        }

        $command = Yii::app()->db->createCommand();

        try {
            $res = $command->update('kt_orders_services', [
                'ServiceID_UTK' => $this->serviceUtkId,
                'Status' => $this->status,
                'ServiceType' => $this->serviceType,
                'OfferID' => $this->offerId,
                'DateStart' => $this->startDateTime,
                'DateFinish' => $this->endDateTime,
                'AmendAllowed' => $this->amendAllowed,
                'DateAmend' => $this->dateAmend,
                'SupplierPrice' => $this->supplierPrice,
                'KmpPrice' => $this->saleSum,
                'AgencyProfit' => $this->commission,
                'SaleCurrency' => $this->saleCurrency,
                'SupplierCurrency' => $this->supplierCurrency,
                'OrderID' => $this->orderId,
                'Offline' => $this->offline,
                'Extra' => $this->serviceDescription,
                'CityID' => $this->cityId,
                'CountryID' => $this->countryId,
                'ServiceName' => $this->serviceName,
                'SupplierID' => $this->supplierId,
                'SupplierSvcID' => $this->supplierServiceId,
                'ServiceID_GP' => $this->serviceGptsId,
                'ServiceID_main' => $this->parentServiceId,
                'agreementSet' => $this->agreementSet,
                'SalePenalty' => $this->SalePenalty,
                'SalePenaltyCurr' => $this->SalePenaltyCurr,
                'NetPenalty' => $this->NetPenalty,
                'NetPenaltyCurr' => $this->NetPenaltyCurr,
                'RestPaymentAmount' => $this->RestPaymentAmount
            ], 'ServiceID = :serviceId', [':serviceId' => $this->serviceID]);
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(), __FUNCTION__,
                OrdersErrors::CANNOT_UPDATE_SERVICE,
                $command->getText(),
                $e
            );
        }

        return $this->serviceID;
    }

    /**
     * Загрузить услугу из БД по указанному идентификатору в КТ
     * @param $serviceId int
     */
    public function load($serviceId)
    {

        try {
            $command = Yii::app()->db->createCommand()
                ->select('*')
                ->from('kt_orders_services')
                ->where('ServiceID = :serviceId', array(':serviceId' => $serviceId));

            $serviceInfo = $command->queryRow();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::CANNOT_GET_SERVICE,
                $command->getText(),
                $e
            );
        }

        $this->setParamsMapping([
            'ServiceID' => 'serviceID',
            'ServiceID_UTK' => 'serviceUtkId',
            'ServiceID_GP' => 'serviceGptsId',
            'Status' => 'status',
            'ServiceType' => 'serviceType',
            'TourID' => 'serviceTour',
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
            'Extra' => 'Extra',
            'CityID' => 'cityId',
            'CountryID' => 'countryId',
            'SupplierID' => 'supplierId',
            'SupplierSvcID' => 'supplierServiceId',
            'ServiceName' => 'serviceName',
            'ServiceID_main' => 'parentServiceId',
            'Offline' => 'offline',
            'agreementSet' => 'agreementSet',
            'SalePenalty' => 'SalePenalty',
            'SalePenaltyCurr' => 'SalePenaltyCurr',
            'NetPenalty' => 'NetPenalty',
            'NetPenaltyCurr' => 'NetPenaltyCurr'
        ]);

        $this->setAttributes($serviceInfo);
        $this->saleSum += $this->commission;

        return true;
    }
}
