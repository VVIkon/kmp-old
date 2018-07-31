<?php

/**
 * Class RoomOffer
 * Реализует функциональность для работы с данными
 * номера для предложения размещения
 */
class RoomOffer extends KFormModel
{
    /** @var string Идентифкатор предложения номера */
    private $offerKey;
    /**  @var string Токен поиска */
    private $token;
    /** @var string код поставщика */
    private $supplierCode;
    /** @var int Идентифкатор отеля в КТ */
    private $hotelId;
    /** @var bool Признак доступности предложения */
    private $available;
    /** @var string дата начала предложения */
    private $dateFrom;
    /** @var string дата окончания предложения */
    private $dateTo;
    /** @var bool Признак специального предложения */
    private $specialOffer;
    /** @var string Тип комнаты */
    private $roomType;
    /** @var string Описание типа комнаты */
    private $roomTypeDescription;
    /** @var string Тип питания */
    private $mealType;
    /**  @var array Данные стоимости предложения для поставщика */
    private $supplierPrice;
    /** @var array Данные стоимости предложения для клиента */
    private $clientPrice;
    /** @var string название тарифа */
    private $fareName;
    /** @var string описание тарифа */
    private $fareDescription;
    /** @var bool флаг, означающий возможность заказа дополнительного питания */
    private $mealOptionsAvailable;
    /** @var int количество номеров по данному офферу */
    private $availableRooms;
    /** @var int количество взрослых, указанных в запросе для этой комнаты */
    private $adults;
    /** @var int количество детей, указанных в запросе для этой комнаты */
    private $children;
    /** @var */
    public $cancelAbility;
    /** @var */
    public $modifyAbility;

    /**
     * @var HotelResponseRoomService[]
     */
    protected $RoomServices = [];

    /**
     * Конструктор класса
     * @param $params object
     */
    public function __construct($params)
    {
        $this->initParams($params);
    }

    /**
     * Инициализация параметров объектов
     * @param $params
     */
    public function initParams($params)
    {
        $this->offerKey = $params['offerKey'];
        $this->token = $params['token'];
        $this->supplierCode = $params['supplierCode'];
        $this->hotelId = $params['hotelId'];
        $this->available = $params['available'];
        $this->dateFrom = $params['dateFrom'];
        $this->dateTo = $params['dateTo'];
        $this->specialOffer = $params['specialOffer'];
        $this->roomType = $params['roomType'];
        $this->roomTypeDescription = $params['roomTypeDescription'];
        $this->mealType = $params['mealTypes'];
        $this->fareName = $params['fareName'];
        $this->fareDescription = $params['fareDescription'];
        $this->mealOptionsAvailable = $params['mealOptionsAvailable'];
        $this->availableRooms = $params['availableRooms'];
        $this->adults = $params['adults'];
        $this->children = $params['children'];

        $this->setOfferPrice($params['salesTerms']);

        $this->setRoomServices($params['roomServices']);
    }

    /**
     * Получение данных по предложениям номеров из кэша
     * @param $offerKeys
     * @return array|CDbDataReader
     * @deprecated ?
     */
    public static function fromCache($token)
    {
        $command = Yii::app()->db->createCommand();

        $command->select(
            'offerKey, token, supplierCode, hotelID, available, dateFrom, dateTo, specialOffer,
            roomType, roomTypeDescription, mealType mealTypes, fareName, fareDescription,
            mealOptionsAvailable, availableRooms, adult as adults, child as children'
        );

        $command->from('ho_offers');
        $command->where('token = :token', [':token' => $token]);

        try {
            $roomsInfo = $command->queryAll();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_ROOM_OFFER,
                $command->getText(),
                $e
            );
        }
        return $roomsInfo;
    }

    /**
     * Получение спиcка найденных офферов отелей
     * @param string $token токен поиска
     * @return array список офферов [ 'ID отеля' => [ список офферов отеля ]]
     */
    public static function getHotelsOffers($token)
    {
        $hotelsOffers = [];

        $command = Yii::app()->db->createCommand()
            ->select('offerKey, token, supplierCode, hotelID, available, dateFrom, dateTo, specialOffer,
                roomType, roomTypeDescription, mealType mealTypes, fareName, fareDescription,
                mealOptionsAvailable, availableRooms, adult as adults, child as children')
            ->from('ho_offers')
            ->where('token = :token', [':token' => $token]);

        try {
            $result = $command->query();

            foreach ($result as $o) {
                if (!isset($hotelsOffers[$o['hotelID']])) {
                    $hotelsOffers[$o['hotelID']] = [];
                }
                $hotelsOffers[$o['hotelID']][] = $o;
            }

            $result->close();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_ROOM_OFFER,
                $command->getText(),
                $e
            );
        }

        return $hotelsOffers;
    }

    public function getClientPrice()
    {
        return $this->clientPrice->toCache($this->offerKey);
    }

    public function getSuppPrice()
    {
        return $this->supplierPrice->toCache($this->offerKey);
    }

    /**
     * $priceTax RoomOfferPriceTax
     * @return array
     */
    public function getClientPriceTax()
    {
        $taxes = [];
        $priceTaxes = $this->clientPrice->getTaxes();
        foreach ($priceTaxes as $priceTax) {
            $taxes[] = $priceTax->toCache($this->offerKey);
        }
        return $taxes;
    }

    /**
     * $priceTax RoomOfferPriceTax
     * @return array
     */
    public function getSuppPriceTax()
    {
        if (empty($this->supplierPrice)) {
            return [];
        }
        $priceTaxes = $this->supplierPrice->getTaxes();
        $taxes = [];
        if (empty($priceTaxes)) {
            return [];
        } else {
            foreach ($priceTaxes as $priceTax) {
                $taxes[] = $priceTax->toCache($this->offerKey);
            }
            return $taxes;
        }
    }


    public function getRoomServices()
    {
        return $this->RoomServices;
    }

    /**
     * Сохранение объекта в БД
     */
    public function toCache($token, $hotelId)
    {
//        $this->clientPrice->toCache($this->offerKey);
//        $this->supplierPrice->toCache($this->offerKey);

//        return [
//            'offerKey' => $this->offerKey,
//            'token' => $token,
//            'supplierCode' => $this->supplierCode,
//            'hotelID' => $hotelId,
//            'available' => $this->available,
//            'dateFrom' => $this->dateFrom,
//            'dateTo' => $this->dateTo,
//            'specialOffer' => $this->specialOffer,
//            'roomType' => $this->roomType,
//            'roomTypeDescription' => $this->roomTypeDescription,
//            'mealType' => $this->mealType,
//            'fareName' => $this->fareName,
//            'fareDescription' => $this->fareDescription,
//            'mealOptionsAvailable' => $this->mealOptionsAvailable,
//            'availableRooms' => $this->availableRooms,
//            'adult' => $this->adults,
//            'child' => $this->children,
//            'cancelAbility' => $this->cancelAbility,
//            'modifyAbility' => $this->modifyAbility
//        ];

        $command = Yii::app()->db->createCommand();

//        try {
        $transaction = Yii::app()->db->beginTransaction();

            $command->insert('ho_offers', [
                'offerKey' => $this->offerKey,
                'token' => $token,
                'supplierCode' => $this->supplierCode,
                'hotelID' => $hotelId,
                'available' => $this->available,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'specialOffer' => $this->specialOffer,
                'roomType' => $this->roomType,
                'roomTypeDescription' => $this->roomTypeDescription,
                'mealType' => $this->mealType,
                'fareName' => $this->fareName,
                'fareDescription' => $this->fareDescription,
                'mealOptionsAvailable' => $this->mealOptionsAvailable,
                'availableRooms' => $this->availableRooms,
                'adult' => $this->adults,
                'child' => $this->children,
                'cancelAbility' => $this->cancelAbility,
                'modifyAbility' => $this->modifyAbility
            ]);

//        } catch (Exception $e) {
//            throw new KmpDbException(
//                get_class(),
//                __FUNCTION__,
//                SearcherErrors::CANNOT_CREATE_ROOM_OFFER,
//                $command->getText(),
//                $e
//            );
//        }

        $this->clientPrice->toCache($this->offerKey);
        $this->supplierPrice->toCache($this->offerKey);

        $transaction->commit();
    }

    /**
     * Установить ценовые параметры предложения
     * @param $params
     * @return bool
     */
    private function setOfferPriceClient($params)
    {
        if (empty($params) && is_array($params)) {
            return false;
        }
        $roomOfferPrice = new RoomOfferPrice($params);
        $this->clientPrice = $roomOfferPrice;
    }

    /**
     * Установить ценовые параметры предложения
     * @param $params
     * @return bool
     */
    private function setOfferPriceSupplier($params)
    {
        if (empty($params) && is_array($params)) {
            return false;
        }
        $roomOfferPrice = new RoomOfferPrice($params);
        $this->supplierPrice = $roomOfferPrice;
    }

    /**
     * Установить ценовые параметры предложения
     * @param $params
     * @return bool
     */
    private function setOfferPrice($params)
    {
        if (empty($params) && is_array($params)) {
            return false;
        }

        foreach ($params as $priceParams) {
            $roomOfferPrice = new RoomOfferPrice($priceParams);

            if ($roomOfferPrice->type == $roomOfferPrice::CLIENT_PRICE_TYPE) {
                $this->clientPrice = $roomOfferPrice;
            } else {
                $this->supplierPrice = $roomOfferPrice;
            }
        }
    }

    /**
     * Задать услуги в номере
     * @param $roomServices
     */
    public function setRoomServices($roomServices)
    {
        foreach ($roomServices as $roomService) {
            $HotelResponseRoomService = new HotelResponseRoomService();
            $HotelResponseRoomService->setNameService($roomService);
            $HotelResponseRoomService->setOfferKey($this->offerKey);

            $this->RoomServices[] = $HotelResponseRoomService;
        }
    }

    /**
     * Сравнение сегмента поездки с указанным сегментом поездки
     * @deprecated ?
     *
     * @param $segment
     * @return bool
     */
    public function isEqual($segment)
    {
        /*if (empty($segment)) {
            return false;
        }

        if ($this->flightSegmentName != $segment->flightSegmentName
            || $this->validatingAirline != $segment->validatingAirline
            || $this->marketingAirline != $segment->marketingAirline
            || $this->operatingAirline != $segment->operatingAirline
            || $this->flightNumber != $segment->flightNumber
            || $this->aircraftCode != $segment->aircraftCode
            || $this->aircraftName != $segment->aircraftName
            || $this->classType != $segment->classType
            || $this->code != $segment->code
            || $this->departureAirportCode != $segment->departureAirportCode
            || $this->departureDate != $segment->departureDate
            || $this->departureTerminal != $segment->departureTerminal
            || $this->arrivalAirportCode != $segment->arrivalAirportCode
            || $this->arrivalDate != $segment->arrivalDate
            || $this->arrivalTerminal != $segment->arrivalTerminal
            || $this->mealCode != $segment->mealCode
            || $this->stopQuantity != $segment->stopQuantity
            || $this->stopAirports != $segment->stopAirports
        ) {
            return false;
        }

        return true;*/
    }

    /**
     * Получить представление предложения номеров в отеле в виде массива
     * @param $currency
     * @param $curForm
     * @return array
     */
    public function getHotelOffer($currency, &$currencyForm)
    {
        return [
            'offerId' => $this->offerKey,
            'supplierCode' => $this->supplierCode,
            'salesTermsInfo' => [
                "supplierCurrency" => $this->getSaleTermInCurrency($this->supplierPrice->currency, $currencyForm),
                "localCurrency" => $this->getSaleTermInCurrency(CurrencyRates::RUSSIAN_RUBLE_CURRENCY_ID, $currencyForm),
                "viewCurrency" => $this->getSaleTermInCurrency($currency, $currencyForm)
            ],
            'available' => !empty($this->available),
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'specialOffer' => !empty($this->specialOffer),
            'roomType' => $this->roomType,
            'roomTypeDescription' => $this->roomTypeDescription,
            'mealType' => $this->mealType,
            'roomServices' => [],
            'fareName' => $this->fareName,
            'fareDescription' => $this->fareDescription,
            'mealOptionsAvailable' => (bool)$this->mealOptionsAvailable,
            'availableRooms' => $this->availableRooms,
            'adults' => $this->adults,
            'children' => $this->children
        ];
    }

    /**
     * Получить структуру цены предложения в указанной валюте
     * @param $currency
     * @return array
     */
    private function getSaleTermInCurrency($currency, &$currencyForm)
    {
        return [
            'supplier' => $this->supplierPrice->toArray($currency, $currencyForm),
            'client' => $this->clientPrice->toArray($currency, $currencyForm)
        ];
    }


}
