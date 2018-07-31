<?php

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Модель оффера
 *
 * @property $travelPolicyValue HotelOfferTravelPolicyValue
 * @property $HotelInfo HotelInfo
 * @property $roomServices HotelRoomService []
 * @property $priceOffers HotelPriceOffer[]
 * @property $taxOffers HotelTaxOffer[]
 * @property HotelReservation $hotelReservation
 * @property HotelEngineData $hotelEngineData
 * @property HotelCancelPenalty[] $hotelCancelPenalties
 * @property HotelAddOffer[] $addOffers
 * @property $timeLimitBookingDate
 */
class HotelOffer extends AbstractHotelOffer implements ServiceOfferInterface
{
    /**
     * Зашит ID сервиса
     * @var int
     */
    public $offerId;
    public $hotelId;

    public function relations()
    {
        return array(
            'HotelInfo' => array(self::BELONGS_TO, 'HotelInfo', 'hotelId'),
            'roomServices' => array(self::HAS_MANY, 'HotelRoomService', 'offerId'),
            'priceOffers' => array(self::HAS_MANY, 'HotelPriceOffer', 'offerId'),
            'taxOffers' => array(self::HAS_MANY, 'HotelTaxOffer', 'offerId'),
            'hotelReservation' => array(self::HAS_ONE, 'HotelReservation', 'offerId'),
            'hotelEngineData' => array(self::HAS_ONE, 'HotelEngineData', 'offerId'),
            'hotelCancelPenalties' => array(self::HAS_MANY, 'HotelCancelPenalty', 'offerId'),
            'offerValue' => array(self::HAS_ONE, 'HotelOfferTravelPolicyValue', 'kt_service_ho_offers_offerId'),
            'addOffers' => array(self::HAS_MANY, 'HotelAddOffer', 'offerId'),
        );
    }

    public function tableName()
    {
        return 'kt_service_ho_offers';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function getCityId()
    {
        if ($this->HotelInfo) {
            return $this->HotelInfo->getCityId();
        } else {
            return null;
        }
    }

    public function getCountryId()
    {
        if ($this->HotelInfo) {
            return $this->HotelInfo->getCity()->getCountryId();
        } else {
            return null;
        }
    }

    /**
     * Получение ID оффера
     * потому что в разных таблицах разное название поля
     * @return mixed
     */
    public function getOfferId()
    {
        return $this->offerId;
    }

    public function getTaxOffers()
    {
        return $this->taxOffers;
    }

    protected function getRoomServices()
    {
        return $this->roomServices;
    }

    public function getPriceOffers()
    {
        return $this->priceOffers;
    }

    protected function getPriceOfferByDays()
    {
        return [];
    }

    protected function getHotelId()
    {
        return $this->hotelId;
    }


    /**
     * Сохранение данных бронирования в оффер
     * @param array $bookData
     */
    public function setBookData(array $bookData)
    {
        // сохраним бронь
        if (isset($bookData['hotelReservation'])) {
            $HotelReservation = $this->getHotelReservation();

            if (is_null($HotelReservation)) {
                $HotelReservation = new HotelReservation();
                $HotelReservation->setOfferId($this->offerId);
                $HotelReservation->setStatus(HotelReservation::STATUS_ACTIVE);
            }

            $HotelReservation->setReservationNumber($bookData['hotelReservation']['reservationNumber']);
            $HotelReservation->save(false);
        }

        // сохраним engineData
        if (isset($bookData['hotelReservation']['engine'])) {
            $hotelEngineData = $this->hotelEngineData;

            if (is_null($hotelEngineData)) {
                $hotelEngineData = new HotelEngineData();
                $hotelEngineData->bindHotelOffer($this);
                $hotelEngineData->setGateId($bookData['hotelReservation']['gateId']);
                if (isset($HotelReservation) && $HotelReservation->getReservationId()) {
                    $hotelEngineData->setReservationId($HotelReservation->getReservationId());
                }
            }

            $hotelEngineData->setData($bookData['hotelReservation']['engine']);
            $hotelEngineData->save(false);
        }

        // обновим данные отмены
        if (!empty($bookData['cancelPenalties']) && count($bookData['cancelPenalties'])) {
            $this->clearCancelPenalties();

            foreach ($bookData['cancelPenalties'] as $cancelPenaltyArr) {
                $CancelPenalty = new HotelCancelPenalty();
                $CancelPenalty->setType('client');
                $CancelPenalty->fromArray($cancelPenaltyArr);
                $CancelPenalty->setOfferId($this->offerId);
                $CancelPenalty->save(false);
            }
        }
    }

//    /**
//     * Обновление данных оффера при условии появления новой цены
//     * Новая цена может появиться пока только в отельном оффере
//     * @param array $salesTerms
//     * @return mixed
//     */
//    public function updateSalesTerms(array $salesTerms)
//    {
//        if (is_array($salesTerms)) {
//            foreach ($salesTerms as $type => $salesTerm) {
//                $PriceOffer = HotelPriceOfferRepository::getPriceOfferByOfferIdAndType($this->offerId, $type);
//
//                if ($PriceOffer) {
//                    $PriceOffer->updatePrice($salesTerm);
//                    $PriceOffer->save(false);
//                }
//            }
//        }
//    }

    /**
     * Обновление данных оффера из ss_salesTerms
     * Новая цена может появиться пока только в отельном оффере
     * @param array $salesTerms
     * @return mixed
     */
    public function updateFromSSSalesTerms(array $salesTerms)
    {
        if (is_array($salesTerms)) {
            foreach ($salesTerms as $type => $salesTerm) {
                $PriceOffer = HotelPriceOfferRepository::getPriceOfferByOfferIdAndType($this->offerId, $type);

                if ($PriceOffer) {
                    $PriceOffer->updateFromSSSalesTerm($salesTerm);
                    $PriceOffer->save(false);
                }
            }
        }
    }

    /**
     * @param string $type
     * @param $salesTerm
     * @return mixed
     */
    public function updatePrices($type, $salesTerm)
    {
        $PriceOffer = HotelPriceOfferRepository::getPriceOfferByOfferIdAndType($this->offerId, $type);

        if ($PriceOffer) {
            $PriceOffer->updateFromSSSalesTerm($salesTerm);
            $PriceOffer->save(false);
        }
    }


    /**
     * Получение отельных данных
     * @return HotelInfo
     */
    public function getHotelInfo()
    {
        return $this->HotelInfo;
    }

    /**
     * Получение данных оффера для бронирования
     * @return array
     */
    public function getOfferDataForBooking()
    {
        return parent::toArray();
    }

    /**
     *
     * @return mixed
     */
    public function clearCancelPenalties()
    {
        $existingCancelPenalties = $this->hotelCancelPenalties;
        foreach ($existingCancelPenalties as $existingCancelPenalty) {
            $existingCancelPenalty->delete();
        }
    }


//    public function updateCancelPenalties(array $cancelPenalties)
//    {
//        $existingCancelPenalties = $this->hotelCancelPenalties;
//        $clientPenalty = new HotelCancelPenalty();
//        $clientPenalty->setType('client');
//        $clientPenalty->setOfferId($this->getOfferId());
//        $supplierPenalty = new HotelCancelPenalty();
//        $supplierPenalty->setType('supplier');
//        $supplierPenalty->setOfferId($this->getOfferId());
//
//        if (count($existingCancelPenalties)) {
//            foreach ($existingCancelPenalties as $existingCancelPenalty) {
//                if ($existingCancelPenalty->isClient()) {
//                    $clientPenalty = $existingCancelPenalty;
//                } elseif ($existingCancelPenalty->isSupplier()) {
//                    $supplierPenalty = $existingCancelPenalty;
//                }
//            }
//        }
//
//        foreach ($cancelPenalties as $type => $cancelPenalty) {
//            if(!(isset($cancelPenalty['amount']) && isset($cancelPenalty['currency']))){
//                throw new InvalidArgumentException('Invalid cancelPenalty structure');
//            }
//
//            if($type == 'client'){
//                $clientPenalty->setCurrencyCode(CurrencyStorage::findByString($cancelPenalty['currency'])->getCode());
//                $clientPenalty->setAmount($cancelPenalty['amount']);
//                $clientPenalty->save(false);
//            } elseif($type == 'supplier'){
//                if(!is_null($supplierPenalty->getCurrencyCode())){
//                    if(CurrencyStorage::findByString($supplierPenalty->getCurrencyCode())->getId() != CurrencyStorage::findByString($cancelPenalty['currency'])->getId()){
//                        throw new InvalidArgumentException('Валюты поставщика в штрафах должны совпадать', OrdersErrors::CANNOT_CHANGE_SUPPLIER_PENALTY_CURRENCY);
//                    }
//                }
//
//                $supplierPenalty->setCurrencyCode(CurrencyStorage::findByString($cancelPenalty['currency'])->getId());
//                $supplierPenalty->setAmount($cancelPenalty['amount']);
//                $supplierPenalty->save(false);
//            }
//        }
//    }

    /**
     * Сохранение cancelPenalties в валюте поставщика (supplierCurrency)
     *
     * @param array $cancelPenalty
     */
    public function addCancelPenalty(array $cancelPenalty)
    {
        $supplierCurrency = StdLib::nvl($cancelPenalty['supplierCurrency']);
        if (!is_null($supplierCurrency)) {
            if (isset($supplierCurrency['client'])) {
                $clients = $supplierCurrency['client'];
                foreach ($clients as $client) {
                    $HotelCancelPenalty = new HotelCancelPenalty();
                    $HotelCancelPenalty->fromArray($client);
                    $HotelCancelPenalty->setOfferId($this->getOfferId());
                    $HotelCancelPenalty->setType('client');
                    $HotelCancelPenalty->save(false);
                }
            }
            if (isset($supplierCurrency['supplier'])) {
                $suppliers = $supplierCurrency['supplier'];
                foreach ($suppliers as $supplier) {
                    $HotelCancelPenalty = new HotelCancelPenalty();
                    $HotelCancelPenalty->fromArray($supplier);
                    $HotelCancelPenalty->setOfferId($this->getOfferId());
                    $HotelCancelPenalty->setType('supplier');
                    $HotelCancelPenalty->save(false);
                }
            }
        }
    }

    /**
     * Сохранение cancelPenalties в валюте поставщика (supplierCurrency)
     *
     * @param array $cancelPenalty
     */
    public function importCancelPenalties(array $cancelPenalty)
    {
        $offerId=$this->getOfferId();
        $clients = StdLib::nvl($cancelPenalty['client'], []);
        foreach ($clients as $client) {
            $HotelCancelPenalty = new HotelCancelPenalty();
            $HotelCancelPenalty->fromArray($client);
            $HotelCancelPenalty->setOfferId($offerId);
            $HotelCancelPenalty->setType('client');
            if (!$HotelCancelPenalty->save(false)) {
                LogHelper::logExt(
                    get_class($this), __METHOD__,
                    '', 'Ошибка записи штрафов в оффер № ' . $offerId,
                    [
                        'client' => $client
                    ],
                    'error', 'system.orderservice.error'
                );
            }
        }
        
        $suppliers = StdLib::nvl($cancelPenalty['supplier'], []);
        foreach ($suppliers as $supplier) {
            $HotelCancelPenalty = new HotelCancelPenalty();
            $HotelCancelPenalty->fromArray($supplier);
            $HotelCancelPenalty->setOfferId($offerId);
            $HotelCancelPenalty->setType('supplier');
            if (!$HotelCancelPenalty->save(false)) {
                LogHelper::logExt(
                    get_class($this), __METHOD__,
                    '', 'Ошибка записи штрафов в оффер № ' . $offerId,
                    [
                        'supplier' => $supplier
                    ],
                    'error', 'system.orderservice.error'
                );
            }
        }

        LogHelper::logExt(
            get_class($this), __METHOD__,
            '', 'Добавлены штрафы для предложения № ' . $offerId,
            [
                'supplier' => $suppliers,
                'client' => $clients
            ],
            'info', 'system.orderservice.info'
        );
    }

    /**
     *
     * @return HotelCancelPenalty
     */
    public function getActiveCancelPenalty()
    {
        foreach ($this->hotelCancelPenalties as $hotelCancelPenalty) {
            if ($hotelCancelPenalty->isClient() && $hotelCancelPenalty->isActual()) {
                return $hotelCancelPenalty;
            }
        }

        return null;
    }

    /**
     * Отельная бронь
     * @return HotelReservation|null
     */
    public function getHotelReservation()
    {
        return $this->hotelReservation;
    }

    /**
     * Создание оффера из параметров
     * @param array $offerData
     * @return null
     * @throws DomainException
     */
    public function fromArray(array $offerData)
    {
        unset($offerData['offerId']);

        $this->setAttributes($offerData, false);

        // данные поставщика
        $RefSupplier = $this->getSupplier();

        if (is_null($RefSupplier)) {
            throw new DomainException('Invalid Supplier');
        }

        $this->bindSupplier($RefSupplier);
    }

    /**
     * @return mixed
     */
    public function getCancelPenalties()
    {
        return $this->hotelCancelPenalties;
    }

    /**
     * Получение оффера отеля с ваучерами, резервациями и остальным
     * @param $serviceId int
     * @return array
     */
    public function toArray($serviceId = null)
    {
        $mainOffer = parent::toArray();
        $HotelReservation = null;
        $CancelPenalties = null;
        if ($this->hotelReservation) {
            $HotelReservation = $this->hotelReservation->toArray();
        }

        // Штрафы
        $cancelPenalties = $this->getCancelPenalties();
        if (count($cancelPenalties)) {
            $cancelPenaltiesInfo = new CancelPenaltiesInfo();
            foreach ($cancelPenalties as $cancelPenalty) {
                $cancelPenaltiesInfo->addCancelPenalties($cancelPenalty);
            }
            if (count($this->currencies)) {
                foreach ($this->currencies as $name => $currency) {
                    if ($name == 'view' || $name == 'local' || $name == 'client') {  // штрафа в локальной валюте и в валюте просмотра
                        $cancelPenaltiesInfo->addCurrency($name, $currency);
                    }
                }
            } else {
                throw new DomainException('Не заданы валюты для показа цен');
            }
            $CancelPenalties = $cancelPenaltiesInfo->getArray();
        } else {
            $CancelPenalties = null;
        }

        $mainOffer['offerId'] = $this->getOfferId();
        $mainOffer['hotelReservations'] = $HotelReservation;
        $mainOffer['cancelPenalties'] = $CancelPenalties;

        // добавим инфу по отелю
        $HotelInfo = $this->getHotelInfo();
        $hotelInfoArr = null;

        if ($HotelInfo) {
            $HotelInfo->setLang($this->lang);
            $hotelInfoArr = $HotelInfo->toSSHotelInfo();
        }

        $mainOffer['hotelInfo'] = $hotelInfoArr;

        return $mainOffer;
    }

    public function getBookData()
    {
        $bookData = [];

        if ($this->getHotelReservation()) {
            $bookData['hotelReservation'] = $this->getHotelReservation()->toArray();
        }

        $bookData['cancelPenalties'] = $this->getSSCancelPenalties();

        return $bookData;
    }

    /**
     * @return mixed
     */
    public function createTpValueClass()
    {
        return new HotelOfferTravelPolicyValue();
    }

    /**
     * Добавление документа в данные бронирования
     * @param OrderDocument $OrderDocument
     * @return bool
     */
    public function addVoucher(OrderDocument $OrderDocument)
    {
        $HotelReservation = $this->getHotelReservation();

        if ($HotelReservation) {
            return $HotelReservation->addVoucher($OrderDocument);
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getEngineData()
    {
        $HotelReservation = $this->getHotelReservation();

        if (is_null($HotelReservation)) {
            return [];
        }

        $HotelEngineDatas = $HotelReservation->getHotelEngineDatas();
        return $HotelEngineDatas[0]->toArray();
    }

    /**
     * Установка данных брони
     * @param $reservationDatas
     * @return mixed
     * @throws InvalidArgumentException
     * @throws ReservationDataException
     * @throws Exception
     */
    public function setReservationData($reservationDatas)
    {
        foreach ($reservationDatas as $reservationData) {
            // проверка параметров
            if (!isset($reservationData['reservationNumber']) || !isset($reservationData['supplierCode'])) {
                throw new InvalidArgumentException("Неверная структура данных брони", OrdersErrors::INPUT_PARAMS_ERROR);
            }

            $RefSupplier = SupplierRepository::getByEngName($reservationData['supplierCode']);
            if (is_null($RefSupplier)) {
                throw new ReservationDataException("Поставщик {$reservationData['supplierCode']} не найден", OrdersErrors::INPUT_PARAMS_ERROR);
            }

            $HotelReservation = $this->getHotelReservation();

            if (is_null($HotelReservation)) {
                throw new Exception('Нет отельной брони в оффере');
            }

            $HotelReservation->setReservationNumber($reservationData['reservationNumber']);
            $HotelReservation->save(false);

            $this->supplierCode = $RefSupplier->getEngName();
        }
    }

    /**
     * @return mixed
     */
    public function getReservationNumber()
    {
        if ($this->getHotelReservation()) {
            return $this->getHotelReservation()->getReservationNumber();
        }
        return '';
    }

    /**
     *
     * @return array
     */
    public function getSSCancelPenalties()
    {
        $SSCancelPenalties = [];

        foreach ($this->hotelCancelPenalties as $hotelCancelPenalty) {
            $SSCancelPenalties[$hotelCancelPenalty->getType()][] = $hotelCancelPenalty->getArray();
        }

        return $SSCancelPenalties;
    }

    /**
     * Получение данных оффера для УТК
     * @return mixed
     */
    public function getUtkServiceDetails()
    {
        $offerDetails = [];
        $cityIdUTK = '';
        $cityIdGPTS = '';
        $hotelIdUTK = '';
        $hotelIdGPTS = '';

        $HotelInfosByUTK = $this->getHotelInfo()->getHotelMatchesUTK();
        $HotelInfosByGPTS = $this->getHotelInfo()->getHotelMatchesGPTS();

        // найдем информацию по версии УТК
        if (count($HotelInfosByUTK)) {
            // возьмем первый попавшицся отель из УТК
            $HotelInfoByUTK = $HotelInfosByUTK[0];

            $countryIdUTK = $HotelInfoByUTK->getCountryID();
            $hotelIdUTK = $HotelInfoByUTK->getHotelId();
        }

        // найдем информацию по версии ГПТС
        if (count($HotelInfosByGPTS)) {
            // возьмем первый попавшицся отель из GPTS
            $HotelInfoByGPTS = $HotelInfosByGPTS[0];

            $hotelIdGPTS = $HotelInfoByGPTS->getGatewayHotelId();
        }

        // найдем инфу по городу
        $CityMatches = $this->getHotelInfo()->getCity()->getCityMatches();
        if (count($CityMatches)) {
            foreach ($CityMatches as $CityMatch) {
                if ($CityMatch->isUtkCity()) {
                    $cityIdUTK = $CityMatch->getSupplierCityID();
                } elseif ($CityMatch->isGptsCity()) {
                    $cityIdGPTS = $CityMatch->getSupplierCityID();
                }
            }
        }

        // город
        $offerDetails['city_Id'] = $cityIdUTK;
        $offerDetails['city_IdKT'] = $this->getHotelInfo()->getCity()->getCityId();
        $offerDetails['city_IdGPTS'] = $cityIdGPTS;
        $offerDetails['city_Name'] = $this->getHotelInfo()->getCity()->getName();

        // Отель
        $offerDetails['hotel_Id'] = $hotelIdUTK;
        $offerDetails['hotel_Name'] = $this->getHotelInfo()->getHotelName();
        $offerDetails['hotel_IdKT'] = $this->getHotelId();
        $offerDetails['hotel_IdGPTS'] = $hotelIdGPTS;

        // Тип размещения
        $offerDetails['accomodationType_Id'] = '';
        $offerDetails['accomodationType_Name'] = '';

        // Тип питания
        $offerDetails['meal_Id'] = '';
        $offerDetails['meal_Name'] = $this->getMealType();

        // Тип комнаты
        $offerDetails['roomType_Id'] = '';
        $offerDetails['roomType_Name'] = $this->getRoomType();

        return $offerDetails;
    }

    /**
     *
     * @return HotelAddOffer[]
     */
    public function getAddOffers()
    {
        return $this->addOffers;
    }

    /**
     * Проверка наличия доп услуг в оффере
     * @return bool
     */
    public function hasAddOffers()
    {
        return count($this->addOffers) > 0;
    }

    /**
     * Если в отеле нет структуры брони или нет номера брони, то можно бронировать
     * @return mixed
     */
    public function canBeBooked()
    {
        $reservation = $this->getHotelReservation();
        return is_null($reservation) || !$reservation->getReservationNumber();
    }

    /**
     * @return mixed
     */
    public function getTimeLimitBookingDate()
    {
        return $this->timeLimitBookingDate;
    }

//    public function getBookNumber()
//    {
//        $reservation = $this->getHotelReservation();
//
//        if ($reservation) {
//            return $reservation->getReservationNumber();
//        }
//
//        return '';
//    }
}