<?php


/**
 * Class HotelProviderOffer
 * Класс для работы с предложением размещения
 */
class HotelProviderOffer extends ProviderOffer
{
    /** @var string Идентификатор найденного предложения */
    private $offerKey;
    /** @var string Токен поиска */
    private $token;
    /** @var string ID отеля в КТ */
    private $hotelId;
    /** @var string Наименование отеля */
    private $hotelName;
    /** @var string Наименование отеля на русском */
    private $hotelNameRu;
    /** @var string Наименование отеля на английском */
    private $hotelNameEng;
    /** @var string ID города в КТ */
    private $cityId;
    /** @var string Адрес отеля */
    private $address;
    /** @var int Категория отеля */
    private $category;
    /** @var int URL с изображением отеля */
    private $imageUrl;
    /** @var string Код шлюза поставщика */
    private $supplierCode;
    /** @var string Код отеля */
    private $hotelCode;
    /** @var string Наименование поставщика на русском языке */
    private $supplierNameRus;
    /** @var string Наименование поставщика на английском языке */
    private $supplierNameEng;
    /** @var array Массив предложений номеров */
    private $roomOffers;

    /**
     * @var RefSuppliers
     */
    private $Supplier;

    /**
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
     * @param $token
     * @return bool
     */
    public function initParams($params)
    {
        if (empty($params['hotelId'])) {
            return false;
        }

        if (isset($params['supplierCode'])) {
            $this->supplierCode = isset($params['supplierCode']) ? $params['supplierCode'] : null;

            $this->Supplier = SupplierRepository::getByEngName($this->supplierCode);
        } else {
            return false;
        }

        $this->hotelCode = isset($params['hotelCode']) ? $params['hotelCode'] : '';
        $this->hotelId = $params['hotelId'];

        /** костыль, т.к. надо разделить метод на два для записи офферов при поиске и создания структуры ответа */
        $this->hotelName = isset($params['hotelName']) ? $params['hotelName'] : null;
        //$this->hotelNameRu = $params['hotelNameRU'];
        //$this->hotelNameEng = $params['hotelNameEn'];

        $this->cityId = isset($params['cityId']) ? $params['cityId'] : '';
        $this->address = $params['address'];
        $this->category = $params['category'];
        $this->imageUrl = $params['mainImageUrl'];

        $this->setRoomOffers($params['roomOffers']);

        $this->supplierNameRus = isset($params['nameRus']) ? $params['nameRus'] : '';
        $this->supplierNameEng = isset($params['nameEng']) ? $params['nameEng'] : '';

        return true;
    }

    /**
     * Задать предложения номеров
     * @param $tripType
     * @param $trips
     * @return bool
     */
    public function setRoomOffers($roomOffers)
    {
        foreach ($roomOffers as $roomOffer) {
            $roomOffer['token'] = $this->token;
            $roomOffer['hotelId'] = $this->hotelId;
            $roomOffer['supplierCode'] = $this->supplierCode;

            $RoomOffer = new RoomOffer($roomOffer);
            $RoomOffer->cancelAbility = $this->Supplier->getSupportsCancelation();
            $RoomOffer->modifyAbility = $this->Supplier->getSupportsModification();

            $this->roomOffers[] = $RoomOffer;
        }

        return true;
    }

    /**
     * Сохранить предложение в кэше предложений
     * @param $token
     * @return bool
     */
    public function toCache($token)
    {
        if (!$this->roomsToCache($token)) {
            return false;
        }

        return true;
    }

    /**
     * Сохранение предложений номеров в БД
     * @param $token
     * @return bool
     */
    private function roomsToCache($token)
    {
        if (empty($this->roomOffers)) {
            return false;
        }

        $builder = Yii::app()->db->schema->commandBuilder;

        $offersArr = [];
        $clientPricesArr = [];
        $supplPricesArr = [];

        $clientPriceTaxesArr = [];
        $supplPriceTaxesArr = [];

        $RoomServices = [];

        foreach ($this->roomOffers as $roomOffer) {
//            $offersArr[] = $roomOffer->toCache($token, $this->hotelId);

//            $clientPricesArr[] = $roomOffer->getClientPrice();
//            $supplPricesArr[] = $roomOffer->getSuppPrice();
//
//            $clientPriceTaxes = $roomOffer->getClientPriceTax();
//            foreach (StdLib::nvl($clientPriceTaxes, []) as $clientPriceTax) {
//                $clientPriceTaxesArr[] = $clientPriceTax;
//            }
//
//            $supplierPriceTaxes = $roomOffer->getSuppPriceTax();
//            foreach (StdLib::nvl($supplierPriceTaxes, []) as $supplierPriceTax) {
//                $supplPriceTaxesArr[] = $supplierPriceTax;
//            }

            $roomOffer->toCache($token, $this->hotelId);
            $RoomServices = array_merge($roomOffer->getRoomServices(), $RoomServices);
        }

//        $success = false;
//        while (!$success) {
//            $success = $builder->createMultipleInsertCommand('ho_offers', $offersArr)->execute();
//        }
//
//        $success = false;
//        while (!$success) {
//            $success = $builder->createMultipleInsertCommand('ho_priceOffer', $clientPricesArr)->execute();
//        }
//
//        $success = false;
//        while (!$success) {
//            $success = $builder->createMultipleInsertCommand('ho_priceOffer', $supplPricesArr)->execute();
//        }

//        if (!empty($clientPriceTaxesArr)) {
//            $success = false;
//            while (!$success) {
//                $success = $builder->createMultipleInsertCommand('ho_taxOffer', $clientPriceTaxesArr)->execute();
//            }
//        }
//
//        if (!empty($supplPriceTaxesArr)) {
//            $success = false;
//            while (!$success) {
//                $success = $builder->createMultipleInsertCommand('ho_taxOffer', $supplPriceTaxesArr)->execute();
//            }
//        }

        foreach ($RoomServices as $RoomService) {
            $RoomService->save(false);
        }


        return true;
    }

    /**
     * Получить информацию об указанных предложениях
     * @param $token
     * @return mixed
     *
     * @todo на самом деле это получение информации не о предложении, а об отеле,
     * надо найти все вхождения и переделать (см. ниже)
     */
    public function fromCache($token, $lang = 'ru')
    {
        $querymap = [
            'hotelId' => 'hi.hotelId',
            'hotelName' => $lang == 'ru' ? 'hi.hotelNameRU' : 'hi.hotelNameEN',
            'address' => $lang == 'ru' ? 'hi.addressRU' : 'hi.addressEN',
            'category' => 'hi.category',
            'mainImageUrl' => 'hi.mainImageUrl'
        ];

        $select = implode(',', array_map(function ($alias, $field) {
            return $field . ' as ' . $alias;
        }, array_keys($querymap), $querymap));

        $command = Yii::app()->db->createCommand()
            ->selectDistinct($select)
            ->from('ho_hotelInfo as hi')
            ->join('ho_offers as of', 'of.hotelID = hi.hotelId')
            ->where('of.token = :token', [':token' => $token]);
        /*
      $command->select(
          'hotelInfo.hotelID_KT hotelId, hotelNameRU hotelNameRu, hotelNameEN hotelNameEn, address,
          star category, IFNULL(NULL, \'\') services, URL mainImageUrl'
      );

      $command->from('ho_offers offers');
      $command->leftJoin('ho_searchRequest request', 'offers.token = request.token');
      $command->Join('hotels_kt hotelInfo', 'hotelInfo.hotelID_KT = offers.hotelID');
      $command->leftJoin('kt_ref_suppliers suppliers', 'request.SupplierCode = suppliers.SupplierID');
      $command->Join('ho_priceOffer priceOffer', 'priceOffer.offerKey = offers.offerKey');
      $command->where('offers.token = :token', [':token' => $token]);
      $command->group('offers.hotelID');
      */

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
     * Получение информации по отелям найденных оферов
     * @param string $token токен поиска
     * @param string $lang запрашиваемый язык
     * @return array информация по отелям [ 'ID отеля' => 'информация' ]
     */
    public static function getOffersHotelsInfo($token, $lang = 'ru')
    {
        $hotelsInfo = [];

        $querymap = [
            'hotelId' => 'hi.hotelId',
            'hotelName' => ($lang == 'ru' ? 'hi.hotelNameRU' : 'hi.hotelNameEN'),
            'address' => ($lang == 'ru' ? 'hi.addressRU' : 'hi.addressEN'),
            'category' => 'hi.category',
            'mainImageUrl' => 'hi.mainImageUrl'
        ];

        $slct = implode(',', array_map(function ($alias, $field) {
            return $field . ' as ' . $alias;
        }, array_keys($querymap), $querymap));

        $command = Yii::app()->db->createCommand()
            ->selectDistinct($slct)
            ->from('ho_hotelInfo as hi')
            ->join('ho_offers as of', 'of.hotelID = hi.hotelId')
            ->where('of.token = :token', [':token' => $token]);

        try {
            $result = $command->query();

            foreach ($result as $hotel) {
                $hotelsInfo[$hotel['hotelId']] = $hotel;
            }

            $result->close();
        } catch (Exception $e) {

            throw new KmpException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_OFFER,
                $e->getMessage() . ' ' . $command->getText(),
                $e
            );
        }

        return $hotelsInfo;
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

        $command->where('(SupplierID_GPTS = :supplierCode or SupplierID_UTK = :supplierCode) and GatewayID =:gatewayId',
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
            case 'hotelId':
                return $this->hotelId;
                break;
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

    public function toArray($lang, $currency, &$currencyForm)
    {
        /*
        $currency = new CurrencyForm();
        $currencyId = $currency->getCurrencyIdByCode($currencyCode);
        */
        $props = [];
        $props['hotel'] = $this->getHotelInfoShort($lang);
        $props['offers'] = $this->getHotelOffers($currency, $currencyForm);

        return $props;
    }

    /**
     * Получение структуры данных hotelInfoShort
     * @return array
     */
    protected function getHotelInfoShort($lang)
    {
        return [
            'hotelId' => $this->hotelId,
            'name' => $this->hotelName, // ($lang == LangForm::LANG_EN) ? $this->hotelNameEng : $this->hotelNameRu,
            'address' => $this->address,
            'category' => $this->category,
            'services' => [],
            'mainImageUrl' => $this->imageUrl,
        ];
    }

    /**
     * Получить предложения отеля
     * @return array
     */
    protected function getHotelOffers($currency, &$currencyForm)
    {
        $roomOffersInfo = [];

        //$currencyForm = new CurrencyForm();

        foreach ($this->roomOffers as $roomOffer) {
            $offer = $roomOffer->getHotelOffer($currency, $currencyForm);

            $roomOffersInfo[] = $offer;
        }

        return $roomOffersInfo;
    }

}
