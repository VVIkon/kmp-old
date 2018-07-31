<?php


/**
 * Class FlightOffer
 * Класс для работы с предложением авиаперелёта
 */
class FlightProviderOffer extends ProviderOffer
{

    /**
     * Идентификатор найденного предложения
     * @var string
     */
    private $offerKey;

    /**
     * Токен найденного предложения
     * @var string
     */
    private $token;

    /**
     * Название маршрута
     * @var string
     */
    private $routeName;

    /**
     * Нетто цена предложения в валюте поставщика
     * @var string
     */
    private $currencyNetto;

    /**
     * Нетто цена предложения
     * @var string
     */
    private $amountNetto;

    /**
     * Брутто цена предложения в валюте поставщика
     * @var string
     */
    private $currencyBrutto;

    /**
     * Брутто цена предложения
     * @var string
     */
    private $amountBrutto;

    /** @var float комиссия агента */
    private $commission;

    /** @var string валюта комиссии агента */
    private $commissionCurrency;

    /**
     * Признак доступности предложения в текущий момент
     * @var int
     */
    private $available;

    /**
     * Дата последнего приобретения билетов
     * @var string
     */
    private $lastTicketingDate;

    /**
     * Код поставщика
     * @var string
     */
    private $supplierCode;

    /**
     * Наименование поставщика на русском языке
     * @var string
     */
    private $supplierNameRus;

    /**
     * Наименование поставщика на английском языке
     * @var string
     */
    private $supplierNameEng;

    /**
     * Тариф перелёта
     * @var string
     */
    private $flightTariff;

    /**
     * Тип тарифа перелёта
     * @var string
     */
    private $fareType;

    /**
     * Тип тарифа перелёта
     * @var
     */
    private $offerDateTime;

    /**
     * Маршрут перелёта
     * @var objects array
     */
    private $route;

    private $id;

    /**
     * Конструктор класса
     * @param $module object
     * @param $type int тип ответа от провайдера
     */
    public function __construct($module)
    {
        parent::__construct($module);
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Инициализация свойств предложения
     * @param $params
     * @return bool
     */
    public function initParams($params)
    {
        $this->id = isset($params['id']) ? $params['id'] : null;
        $this->offerKey = $params['offerKey'];
        $this->routeName = $params['routeName'];
        $this->currencyNetto = $params['currencyNetto'];
        $this->amountNetto = $params['amountNetto'];
        $this->currencyBrutto = $params['currencyBrutto'];
        $this->amountBrutto = $params['amountBrutto'];
        $this->commissionCurrency = $params['commissionCurrency'];
        $this->commission = $params['commission'];
        $this->available = $params['available'];
        $this->lastTicketingDate = $params['lastTicketingDate'];
        $this->flightTariff = $params['flightTariff'];
        $this->offerDateTime = $params['offerDateTime'];
        $this->fareType = $params['fareType'];

        $this->supplierCode = $params['supplierCode'];

        $this->supplierNameRus = isset($params['nameRus']) ? $params['nameRus'] : '';
        $this->supplierNameEng = isset($params['nameEng']) ? $params['nameEng'] : '';

        foreach ($params['route'] as $trip) {
            $trip['offerKey'] = $this->offerKey;
            $this->route[] = new FlightTrip($trip);
        }
    }

    /**
     * Проверка даты:
     * 1. дата д.б. указанного формата
     * 2. дата д.б. не меньше текущей
     * @param $dt
     * @param string $format ='Y-m-d H:i:s'
     * @return null / date
     */
    private function checkDate($dt, $format='Y-m-d H:i:s'){
        try {
            //вылидность даты
            $d = DateTime::createFromFormat($format, $dt);
            $valid = DateTime::getLastErrors();
            if ($valid['warning_count']!=0 or $valid['error_count']!=0){
                return null;
            }
            // дата д.б. будущая
            $now = new DateTime();
            if ( $d >= $now ) {
                return $dt;
            } else {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Сохранить предложение в кэше предложений
     * @param $token
     * @return bool
     */
    public function toCache($token)
    {
        $transaction = Yii::app()->db->beginTransaction();

        $command = Yii::app()->db->createCommand();

        try {

            $a = [
                'token' => $token,
                'offerKey' => $this->offerKey,
                'routeName' => $this->routeName,
                'currencyNetto' => $this->currencyNetto,
                'amountNetto' => $this->amountNetto,
                'currencyBrutto' => $this->currencyBrutto,
                'amountBrutto' => $this->amountBrutto,
                'commissionAmount' => 0,
                'commissionCurrency' => $this->commissionCurrency,
                'supplierCode' => $this->supplierCode,
                'flightTariff' => $this->flightTariff,
                'fareType' => $this->fareType,
                'available' => $this->available,
                'OfferDateTime' => $this->offerDateTime,
                'lastTicketingDate' => $this->checkDate($this->lastTicketingDate, 'Y-m-d H:i')
            ];
           // LogHelper::logExt(get_class($this), __METHOD__, '----------startSearch-2.1', '', $a, 'info', 'system.searcherservice.info');
            $res = $command->insert('fl_Offer', $a);

        } catch (Exception $e) {
            $transaction->rollback();
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_CREATE_SEARCH_REQUEST,
                $command->getText(),
                $e
            );
        }

        if (!$this->tripsToCache(Yii::app()->db->lastInsertID)) {
            $transaction->rollback();
            return false;
        }

        $transaction->commit();

        return true;
    }

    /**
     * Сохранение маршрутов в БД
     * @param $id
     * @return bool
     */
    private function tripsToCache($id)
    {
        foreach ($this->route as $trip) {
            $trip->toCache($id);
        }
        return true;
    }

    /**
     * Получить информацию об указанных предложениях
     * @param $token
     * @return mixed
     */
    public function fromCache($token)
    {
        $command = Yii::app()->db->createCommand();

        $command->select('token, id, offerKey, routeName, currencyNetto, amountNetto, currencyBrutto, amountBrutto,
            commissionAmount commission, commissionCurrency, supplierCode, suppliers.Name nameRus, suppliers.EngName nameEng, GatewayID providerId, flightTariff, fareType,
            lastTicketingDate, available, OfferDateTime offerDateTime');

        $command->from('fl_Offer offers');
        $command->leftJoin('kt_ref_suppliers suppliers', 'offers.supplierCode = suppliers.SupplierID');
        $command->where('token = :token', [':token' => $token]);

        try {
            $offersInfo = $command->queryAll();

        } catch (Exception $e) {

            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_OFFER,
                $command->getText(),
                $e
            );
        }

        return $offersInfo;
    }

    /**
     * Получить код поставщика
     * @param $supplierCode
     * @param $gatewayId
     * @return bool
     */
    public function getSupplierCode($supplierCode, $gatewayId)
    {

        $command = Yii::app()->db->createCommand();

        $command->select('SupplierId');

        $command->from('kt_ref_suppliers');

        $command->where('(SupplierID_GPTS = :supplierCode or SupplierID_UTK = :supplierCode)and GatewayID =:gatewayId',
            [
                ':supplierCode' => $supplierCode,
                ':gatewayId' => $gatewayId,
            ]
        );

        try {
            $supplierCode = $command->queryRow();
        } catch (Exception $e) {

            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_OFFER,
                $command->getText(),
                $e
            );
        }

        return isset($supplierCode['SupplierId']) ? $supplierCode['SupplierId'] : false;
    }

    /**
     * Сравнение предложения с указанным предложением
     * @param $offer
     * @return bool
     */
    public function isEqual($offer)
    {
        if (empty($offer)) {
            return false;
        }

        if ($this->token != $offer->token
            || $this->routeName != $offer->routeName
            || $this->currencyNetto != $offer->currencyNetto
            || $this->amountNetto != $offer->amountNetto
            || $this->currencyBrutto != $offer->currencyBrutto
            || $this->amountBrutto != $offer->amountBrutto
            || $this->commission != $offer->commission
            || $this->commissionCurrency != $offer->commissionCurrency
            || $this->available != $offer->available
            || $this->supplierCode != $offer->supplierCode
            || $this->fareType != $offer->fareType
            || $this->fareType != $offer->fareType
        ) {
            return false;
        }

        if (count($this->route) != count($offer->route)) {
            return false;
        }

        foreach ($this->route as $key => $route) {

            if (!$this->route[$key]->isEqual($offer->route[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Установка свойств класса
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'flightClass':
                $this->flightClass = $value;
                break;
            case 'charter':
                $this->charter = $value;
                break;
            case 'regular':
                $this->regular = $value;
                break;
            case 'flexibleDays':
                $this->flexibleDays = $value;
                break;
            case 'adult':
                $this->adult = $value;
                break;
            case 'children':
                $this->children = $value;
                break;
            case 'infants':
                $this->infants = $value;
                break;
            case 'childrenAges':
                $this->childrenAges = $value;
                break;
            case 'offerLimit':
                $this->offerLimit = $value;
                break;
        }
    }

    /**
     * Получение свойств
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'flightClass':
                return $this->flightClass;
                break;
            case 'charter':
                return $this->charter;
                break;
            case 'regular':
                return $this->regular;
                break;
            case 'flexibleDays':
                return $this->flexibleDays;
                break;
            case 'adult':
                return $this->adult;
                break;
            case 'children':
                return $this->children;
                break;
            case 'infants':
                return $this->infants;
                break;
            case 'childrenAges':
                return $this->childrenAges;
                break;
            case 'offerLimit':
                return $this->offerLimit;
                break;
            case 'supplierCode':
                return $this->supplierCode;
                break;
        }
    }

    /**
     * Проверка значений в свойствах класса
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        switch ($name) {
            case 'flightClass':
                return isset($this->flightClass);
                break;
            case 'charter':
                return isset($this->charter);
                break;
            case 'regular':
                return isset($this->regular);
                break;
            case 'flexibleDays':
                return isset($this->flexibleDays);
                break;
            case 'adult':
                return isset($this->adult);
                break;
            case 'children':
                return isset($this->children);
                break;
            case 'infants':
                return isset($this->infants);
                break;
            case 'childrenAges':
                return isset($this->childrenAges);
                break;
            case 'offerLimit':
                return isset($this->offerLimit);
                break;
            case 'supplierCode':
                return isset($this->supplierCode);
                break;
            default :
                return false;
        }
    }

    /**
     * Получить коды аэропортов предложения
     * @return array
     */
    public function getOfferAirportsIataCodes()
    {
        $iataCodes = [];

        foreach ($this->route as $trip) {
            $iataCodes = array_merge($iataCodes, $trip->getTripAirportsIataCodes());
        }

        return $iataCodes;
    }

    public function toArray($lang, $currencyCode, $currency = null)
    {
        if (is_null($currency)) {
            $currency = CurrencyRates::getInstance();
        }

        //$currencyId = $currency->getCurrencyIdByCode($currencyCode);

        $props = [
            'offerKey' => $this->offerKey,
            'id' => $this->id,
            'price' => [
                'local' => [
                    'amountNetto' => $currency->calculateInCurrency(
                        (float)$this->amountNetto, $this->currencyNetto, 'RUB'
                    ),
                    'amountBrutto' => $currency->calculateInCurrency(
                        (float)$this->amountBrutto, $this->currencyBrutto, 'RUB'
                    ),
                    'agentCommission' => $currency->calculateInCurrency(
                        (float)$this->commission, $this->commissionCurrency, 'RUB'
                    ),
                ],
                /** @todo разобраться с валютами */
                'viewCurrency' => [
                    'amountNetto' => $currency->calculateInCurrency(
                        (float)$this->amountNetto, $this->currencyNetto, $currencyCode
                    ),
                    'amountBrutto' => $currency->calculateInCurrency(
                        (float)$this->amountBrutto, $this->currencyBrutto, $currencyCode
                    ),
                    'agentCommission' => $currency->calculateInCurrency(
                        (float)$this->commission, $this->commissionCurrency, $currencyCode
                    )
                ],
                'nativeSupplier' => [
                    'amountNetto' => $this->amountNetto,
                    'supplierCurrency' => $this->currencyNetto,
                ],
            ],
            'lastTicketingDate' => $this->lastTicketingDate,
            'supplierCode' => $this->supplierCode,
            'supplierName' => ($lang == LangForm::LANG_EN ? $this->supplierNameEng : $this->supplierNameRus),
            'flightTariff' => $this->flightTariff,
            'recommendedTariff' => '',
            'fareType' => $this->fareType,
        ];


        foreach ($this->route as $trip) {
            $props['itinerary'][] = $trip->toArray($lang, $currency);
        }

        return $props;
    }
}
