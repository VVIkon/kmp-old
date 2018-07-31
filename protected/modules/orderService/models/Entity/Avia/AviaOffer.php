<?php

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Модель авиа оффера
 * @property $offerID
 * @property $offerKey
 * @property $routeName
 * @property $currencyNetto
 * @property $amountNetto
 * @property $currencyBrutto
 * @property $amountBrutto
 * @property $available
 * @property $LastPayDate
 * @property $fareType
 * @property $OfferDateTime
 * @property $infantWithPlace
 * @property $supplierCode
 * @property $flightTariff
 * @property $timeLimitBookingDate
 *
 * @property AviaCancelPenalty[] $AviaCancelPenalties
 * @property AviaOfferPNR[] $PNRs
 * @property AviaFareRules[]$ FareRules
 * @property AviaOfferSegment [] $AviaOfferSegments
 * @property AviaTrip [] $AviaTrips
 * @property AviaOfferPrice [] $priceOffers
 * @property AviaCancelPenalty[] $aviaCancelPenalties
 */
class AviaOffer extends AbstractAviaOffer implements ServiceOfferInterface
{
    protected $lastTIcketingDate;
    protected $OrdersServices;

    /**
     * @var string почему-то AR не хочет определять это свойство автоматически
     * не работает магия (
     */
    public $flightTariff;

    public function tableName()
    {
        return 'kt_service_fl_Offer';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'AviaCancelPenalties' => array(self::HAS_MANY, 'AviaCancelPenalty', 'offerId'),
            'PNRs' => array(self::HAS_MANY, 'AviaOfferPNR', 'offerID'),
            'FareRules' => array(self::HAS_MANY, 'AviaFareRules', 'offerId'),
            'AviaOfferSegments' => array(self::HAS_MANY, 'AviaOfferSegment', 'offerID'),
            'AviaTrips' => array(self::HAS_MANY, 'AviaTrip', 'offerID', 'order' => 'AviaTrips.TripID ASC'),
            'priceOffers' => array(self::HAS_MANY, 'AviaOfferPrice', 'offerId'),
            'offerValue' => array(self::HAS_ONE, 'AviaOfferTravelPolicyValue', 'kt_service_fl_Offer_offerID'),
            'aviaCancelPenalties' => array(self::HAS_MANY, 'AviaCancelPenalty', 'offerId'),
        );
    }

    public function getOfferId()
    {
        return $this->offerID;
    }

    /**
     * @param array $offerData
     * @return mixed
     */
    public function fromArray(array $offerData)
    {
        $this->offerKey = $offerData['offerKey'];
        $this->supplierCode = $offerData['supplierCode'];

        $RefSupplier = $this->getSupplier();

        if (is_null($RefSupplier)) {
            throw new DomainException('Invalid Supplier');
        }

        $this->bindSupplier($RefSupplier);

        $this->flightTariff = $offerData['flightTariff'];
        $this->fareType = $offerData['fareType'];
        $this->adult = $offerData['touristsAges']['adult'];
        $this->child = $offerData['touristsAges']['child'];
        $this->infant = $offerData['touristsAges']['infant'];
        $this->infantWithPlace = $offerData['touristsAges']['InfantWithPlace'];
        $this->lastTIcketingDate = $offerData['lastTicketingDate'];
        $this->LastPayDate = $offerData['lastPayDate'];
        $this->available = 1;

        $this->OfferDateTime = (new DateTime())->format('Y-m-d H:i:s');
        $this->timeLimitBookingDate = $offerData['timeLimitBookingDate'];
    }

    public function setSupplierSaleTerms(array $saleTerms)
    {
        $this->currencyNetto = $saleTerms['currency'];
        $this->amountNetto = $saleTerms['amountBrutto'];
    }

    public function setClientSaleTerms(array $saleTerms)
    {
        $this->currencyBrutto = $saleTerms['currency'];
        $this->amountBrutto = $saleTerms['amountBrutto'];
    }


    /**
     * Получение правил тарифов
     * @return array
     */
    protected function getFareRule()
    {
        $out = [];
        $offerId = StdLib::nvl($this->getOfferID(), 0);
        $fareRules = AviaFareRules::model()->findAll('offerId =' . $offerId);

        if (isset($fareRules)) {
            foreach ($fareRules as $fareRule) {
                $flightSegmentName = $fareRule->flightSegmentName;
                $seg['segment']['flightSegmentName'] = $flightSegmentName;
                $seg['aviaFareRule']['shortRules'] = $fareRule->toArray();

                $rts = AviaTextRules::model()->findAll('offerId =' . $offerId . ' and flightSegmentName ="' . $flightSegmentName . '"');
                if (isset($rts)) {
                    foreach ($rts as $rulesText) {
                        $seg['aviaFareRule']['rules'][] = $rulesText->toArray();
                    }
                }
                $out[] = $seg;
                unset($seg);
            }
        }
        return $out;
    }

    /**
     * @return mixed
     */
    public function getCancelPenalties()
    {
        return $this->AviaCancelPenalties;
    }

    /**
     * Кусок старого кода!
     * Бооольшооой костыль
     * @param $serviceId int
     * @return bool
     */
    public function toArray($serviceId = null)
    {
        $module = Yii::app()->getModule('orderService');

        $offer = new FlightOffer();
        $offer->load($this->getOfferId());

        $offerLangId = LangForm::GetLanguageCodeByName($this->getLang());
        $offer->setSegmentsMealName($offerLangId);
        $offer->setSegmentsAirportCityName($offerLangId);
        $offer->setSegmentsAirportName($offerLangId);
        $offer->setPriceInCurrency($this->getCurrency('view')->getId());

        $serviceInfo = $offer->offerData;

        try {
            $pnr = new ServiceFlPnr();
            $pnr->loadByOfferId($offer->offerId);
        } catch (KmpDbException $kde) {
            LogExceptionsHelper::logExceptionEr($kde, $module, 'system.orderservice.errors');
        }

        $serviceInfo['pnr'] = !empty($pnr->pnr) ? ['pnrNumber' => $pnr->pnr] : null;

        $serviceTickets = [];
        $serviceReceipts = [];

        $ticketForm = TicketsFactory::createTicket(2);
        try {
            $tickets = $ticketForm->getTicketsByServiceId($serviceId);
        } catch (KmpDbException $kde) {
            LogExceptionsHelper::logExceptionEr($kde, $module, 'system.orderservice.errors');
            $this->errorCode = $kde->getCode();
            return false;
        }
//        $docMgr = new DocumentsMgr($module->getModule('order'));

        if (!empty($tickets)) {
            foreach ($tickets as $ticket) {
                $attachedDoc = new AttachedDocument();
                try {
                    $attachedDoc->load($ticket->attachedFormId);
                } catch (KmpDbException $kde) {
                    LogExceptionsHelper::logExceptionEr($kde, $module, 'system.orderservice.errors');
                    $this->errorCode = $kde->getCode();
                    return false;
                }

                $ssAviaTicket = new SSAviaTicket($module);
                $ssAviaTicket->init($ticket->getData());
                $serviceTickets[] = $ssAviaTicket->getView();

                $ssAviaTicketReceipt = new SSAviaTicketReceipt($module);

                $ssAviaTicketReceipt->init([
                    'ticketNumbers' => $ticket->getReceiptTicketNumbers($ticket->attachedFormId),
                    'serviceId' => $ticket->serviceId,
                    'documentId' => $ticket->attachedFormId,
                    'receiptUrl' => $attachedDoc->fileURL
                ]);

                $serviceReceipts[] = $ssAviaTicketReceipt->getView();
            }
        }

        if ($serviceTickets) {
            $serviceInfo['pnr']['tickets'] = $serviceTickets;
        }
        if ($serviceTickets) {
            $serviceInfo['pnr']['receipts'] = $serviceReceipts;
        }

        if ($pnr->getBaggageData()) {
            $serviceInfo['pnr']['baggage'] = $pnr->getBaggageData();
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
        $serviceInfo['cancelPenalties'] = $CancelPenalties;

        // fareRules
        $serviceInfo['fareRules'] = $this->getFareRule();

        // travelPolicy
        $travelPolicyArr = AbstractTravelPolicyValue::getEmptyStructure();
        $travelPolicy = $this->getOfferValue();
        if (!is_null($travelPolicy)) {
            $travelPolicyArr = $travelPolicy->getSSTPOfferValue();
        }
        $serviceInfo['travelPolicy'] = $travelPolicyArr;

        return $serviceInfo;
    }

    /**
     * Сохранение данных брони
     * @param array $bookData данные брони в некоем виде
     * т.к. все равно переделывать, рассчитываем только на одну бронь.
     * Все равно в БД pnr первичным ключом стоит...
     */
    public function importBookData(array $bookData)
    {
        $PNRs = $this->getPNRs();
        $PNR = empty($PNRs) ? new AviaOfferPNR() : $PNRs[0];

        $Supplier = SupplierRepository::getByEngName($bookData['supplierCode']);

        $PNR->PNR = $bookData['PNR'];
        $PNR->supplierCode = $Supplier->SupplierID;
        $PNR->status = $bookData['status'];
        $PNR->offerKey = $bookData['offerKey'];
        $PNR->gateId = $bookData['gateId'];
        $PNR->service_ref = $bookData['service_ref'];
        $PNR->order_ref = $bookData['order_ref'];
        $PNR->offerID = $this->offerID;
        $PNR->save(false);
    }

    /**
     * @param array $bookData
     * @return mixed
     */
    public function setBookData(array $bookData)
    {
        $pnr = new ServiceFlPnr();

        $pnr->setAttributes([
            'pnr' => $bookData['pnrData']['PNR'],
            'offerId' => $this->offerID,
            'supplierCode' => 0,
            'offerKey' => $this->offerKey,
            'gateId' => $bookData['pnrData']['engine']['type'],
            'serviceRef' => $bookData['pnrData']['engine']['GPTS_service_ref'],
            'orderRef' => $bookData['pnrData']['engine']['GPTS_order_ref'],
            'baggageData' => $bookData['pnrData']['baggage'],
            'status' => 1,
        ]);

        $pnr->save(false);

        if (!empty($bookData['pnrData']['lastTicketingDate'])) {
            $this->lastTIcketingDate = $bookData['pnrData']['lastTicketingDate'];
        }
    }

    /**
     *
     * @return mixed
     */
    public function clearCancelPenalties()
    {
        $existingCancelPenalties = $this->aviaCancelPenalties;
        foreach ($existingCancelPenalties as $existingCancelPenalty) {
            $existingCancelPenalty->delete();
        }
    }

//    public function updateCancelPenalties(array $cancelPenalties)
//    {
//        $existingCancelPenalties = $this->aviaCancelPenalties;
//        $clientPenalty = new AviaCancelPenalty();
//        $clientPenalty->setType('client');
//        $clientPenalty->setOfferId($this->getOfferId());
//        $supplierPenalty = new AviaCancelPenalty();
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
//            if (!(isset($cancelPenalty['amount']) && isset($cancelPenalty['currency']))) {
//                throw new InvalidArgumentException('Invalid cancelPenalty structure');
//            }
//
//            if ($type == 'client') {
//                $clientPenalty->setCurrencyCode(CurrencyStorage::findByString($cancelPenalty['currency'])->getCode());
//                $clientPenalty->setAmount($cancelPenalty['amount']);
//                $clientPenalty->save(false);
//            } elseif ($type == 'supplier') {
//                if (!is_null($supplierPenalty->getCurrencyCode())) {
//                    if (CurrencyStorage::findByString($supplierPenalty->getCurrencyCode())->getId() != CurrencyStorage::findByString($cancelPenalty['currency'])->getId()) {
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
     * @return array
     */
    public function getBookData()
    {
        $segments = [];
        $segments['segments'] = [];

        if ($this->PNRs && count($this->PNRs)) {
            foreach ($this->PNRs as $PNR) {
                $segments['segments']['segments'][] = "DME-DXB";
                $segments['segments']['pnrData'][] = [
                    'engine' => (array)$PNR->getEngineData(),
                    'PNR' => $PNR->getPNR()
                ];
            }
        }

        return $segments;
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
                    $AviaCancelPenalty = new AviaCancelPenalty();
                    $AviaCancelPenalty->fromArray($client);
                    $AviaCancelPenalty->setOfferId($this->getOfferId());
                    $AviaCancelPenalty->setType('client');
                    if ($AviaCancelPenalty->save(false)) {
                        LogHelper::logExt(get_class($this), __METHOD__, '', 'Добавлены правилам отмены брони № ' . $AviaCancelPenalty->offerId, $client, 'info', 'system.orderservice.info');
                    }
                }
            }
            if (isset($supplierCurrency['supplier'])) {
                $suppliers = $supplierCurrency['supplier'];
                foreach ($suppliers as $supplier) {
                    $AviaCancelPenalty = new AviaCancelPenalty();
                    $AviaCancelPenalty->fromArray($supplier);
                    $AviaCancelPenalty->setOfferId($this->getOfferId());
                    $AviaCancelPenalty->setType('supplier');
                    if ($AviaCancelPenalty->save(false)) {
                        LogHelper::logExt(get_class($this), __METHOD__, '', 'Добавлены правилам отмены брони № ' . $AviaCancelPenalty->offerId, $supplier, 'info', 'system.orderservice.info');
                    }
                }
            }
        }
    }

    /**
     * Сохранение cancelPenalties из импорта
     *
     * @param array $cancelPenalty
     */
    public function importCancelPenalties(array $cancelPenalty)
    {
        $offerId=$this->getOfferId();
        $clients = StdLib::nvl($cancelPenalty['client'], []);
        foreach ($clients as $client) {
            $AviaCancelPenalty = new AviaCancelPenalty();
            $AviaCancelPenalty->fromArray($client);
            $AviaCancelPenalty->setOfferId($offerId);
            $AviaCancelPenalty->setType('client');
            if (!$AviaCancelPenalty->save(false)) {
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
            $AviaCancelPenalty = new AviaCancelPenalty();
            $AviaCancelPenalty->fromArray($supplier);
            $AviaCancelPenalty->setOfferId($offerId);
            $AviaCancelPenalty->setType('supplier');
            if (!$AviaCancelPenalty->save(false)) {
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
     * @return mixed
     */
    public function getEngineData()
    {
        $pnrs = $this->getPNRs();

        $engineData = [];

        if (count($pnrs)) {
            $pnr = $pnrs[0];
            $engineData = (array)$pnr->getEngineData();
        }

        return $engineData;
    }


    /**
     * Получение ценовых данных оффера
     * @return SalesTermsInfo
     */
    public function getSalesTerms()
    {
        return null;
    }

    public function setDateFrom($dateFrom)
    {

    }

    public function setDateTo($dateTo)
    {
    }

    /**
     * @param OrderDocument $OrderDocument
     * @return mixed
     */
    public function addVoucher(OrderDocument $OrderDocument)
    {
        // TODO: Implement addVoucher() method.
    }

    /**
     *
     * @return AviaOfferPNR []
     */
    public function getPNRs()
    {
        return $this->PNRs;
    }

    /**
     * Установка данных брони из ручного режима
     * @param $reservationDatas
     * @return mixed
     * @throws ReservationDataException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function setReservationData($reservationDatas)
    {
        $PNRs = $this->getPNRs();

        if (empty($PNRs)) {
            throw new ReservationDataException("Не найдены PNR");
        }

        foreach ($reservationDatas as $reservationData) {
            // проверка параметров
            if (!isset($reservationData['reservationAction'])
                || !isset($reservationData['PNR'])
                || !isset($reservationData['aviaReservation'])
                || !isset($reservationData['flightTariff'])
                || !isset($reservationData['lastTicketingDate'])
                || !isset($reservationData['aviaReservation']['PNR'])
                || !isset($reservationData['aviaReservation']['supplierCode'])
                || !isset($reservationData['aviaReservation']['status'])
            ) {
                throw new InvalidArgumentException("Неверная структура данных брони", OrdersErrors::INPUT_PARAMS_ERROR);
            }

            if ($reservationData['aviaReservation']['PNR'] != $reservationData['PNR']) {
                throw new InvalidArgumentException('PNR в параметрах должны быть одинаковыми', OrdersErrors::INPUT_PARAMS_ERROR);
            }

            $RefSupplier = SupplierRepository::getByEngName($reservationData['aviaReservation']['supplierCode']);
            if (is_null($RefSupplier)) {
                throw new ReservationDataException("Поставщик {$reservationData['aviaReservation']['supplierCode']} не найден", OrdersErrors::INPUT_PARAMS_ERROR);
            }

            $validator = Validation::createValidatorBuilder()
                ->addMethodMapping('loadValidatorMetadata')
                ->getValidator();

            // проверим есть ли такой PNR в базе
            $FoundAviaOfferPNR = AviaOfferPNRRepository::getByPNR($reservationData['aviaReservation']['PNR']);

            switch ($reservationData['reservationAction']) {
                case 'update':
                    // сейчас у нас только 1 PNR на весь перелет
                    foreach ($PNRs as $PNR) {
                        // здесь пытаемся обновить PNR на тот, который уже существует в базе
                        if (!is_null($FoundAviaOfferPNR) && $FoundAviaOfferPNR->getPNR() != $PNR->getPNR()) {
                            throw new InvalidArgumentException("Бронь с таким PNR уже существует", OrdersErrors::PNR_NUMBER_ALREADY_EXISTS);
                        }

                        $PNR->setPNR($reservationData['aviaReservation']['PNR']);

                        if ($reservationData['aviaReservation']['status'] == 1) {
                            $PNR->enable();
                        } else {
                            $PNR->disable();
                        }

                        $PNR->setSupplierCode($RefSupplier->getSupplierID());
                        $this->supplierCode = $RefSupplier->getSupplierID();

                        $violations = $validator->validate($PNR);
                        if (count($violations) > 0) {
                            foreach ($violations as $violation) {
                                throw new InvalidArgumentException($violation->getMessage());
                            }
                        }

                        if (!$PNR->save(false)) {
                            throw new DomainException("Не удалось обновить PNR", OrdersErrors::DB_ERROR);
                        }
                        break;
                    }
                    break;
                case 'add':
                case 'replace':
                    // если запись с таким PNR уже существует
                    if (!is_null($FoundAviaOfferPNR)) {
                        throw new InvalidArgumentException("Бронь с таким PNR уже существует", OrdersErrors::PNR_NUMBER_ALREADY_EXISTS);
                    }

                    // сейчас у нас только 1 PNR на весь перелет
                    foreach ($PNRs as $PNR) {
                        // Старая бронь помечается как отменённая.
                        $PNR->disable();
                        $PNR->save(false);

                        // Создаётся новая бронь с привязкой к сегментам как у старой.
                        $NewPNR = new AviaOfferPNR();
                        $NewPNR->setOfferID($this->offerID);
                        $NewPNR->setSupplierCode($RefSupplier->getSupplierID());
                        $NewPNR->setPNR($reservationData['aviaReservation']['PNR']);
                        $NewPNR->enable();

                        $violations = $validator->validate($PNR);
                        if (count($violations) > 0) {
                            foreach ($violations as $violation) {
                                throw new InvalidArgumentException($violation->getMessage());
                            }
                        }

                        if (!$NewPNR->save(false)) {
                            throw new DomainException("Не удалось создать PNR", OrdersErrors::DB_ERROR);
                        }
                        break;
                    }
                    break;
                default:
                    throw new ReservationDataException("Неизвестное действие {$reservationData['reservationAction']}");
            }

            if (isset($reservationData['flightTariff']) && $reservationData['flightTariff']) {
                $this->flightTariff = $reservationData['flightTariff'];
            }

            if (isset($reservationData['lastTicketingDate']) && $reservationData['lastTicketingDate']) {
                $this->lastTIcketingDate = $reservationData['lastTicketingDate'];
            }
        }
    }

    /**
     * Возвращает все сегменты перелета
     * @return AviaOfferSegment []
     */
    public function getAviaOfferSegments()
    {
        return $this->AviaOfferSegments;
    }

    /**
     * Проверка, что дата билета меньше даты вылета
     * @return bool
     * @throws Exception
     */
    public function isLastTicketingDateValid()
    {
        $LastTicketingDate = new DateTime($this->lastTIcketingDate);
        $AviaOfferSegments = $this->getAviaOfferSegments();

        if (is_null($AviaOfferSegments)) {
            throw new Exception("Не найдены сегенты перелета");
        }

        $nearestDepartureTimestamp = 0;

        // найдем ближайшую дату вылета
        foreach ($AviaOfferSegments as $AviaOfferSegment) {
            $departureDate = new DateTime($AviaOfferSegment->getDepartureDate());
            $departureTimestamp = $departureDate->getTimestamp();

            // для 1го раза просто запишем значение
            if (!$nearestDepartureTimestamp || $departureTimestamp < $nearestDepartureTimestamp) {
                $nearestDepartureTimestamp = $departureTimestamp;
            }
        }

        return $LastTicketingDate->getTimestamp() < $nearestDepartureTimestamp;
    }

    /**
     * Установка сервиса
     * @param OrdersServices $OrdersServices
     */
    public function setService(OrdersServices $OrdersServices)
    {
        $this->OrdersServices = $OrdersServices;
    }

    /**
     * Добавление билета в бронь
     * @param array $ticketData
     * @throws InvalidArgumentException
     * @throws Exception
     * @return bool
     */
    public function setTicket(array $ticketData)
    {
        $PNRs = $this->getPNRs();

        if (count($PNRs)) {
            $ssAviaTicket = $ticketData['ticketData'];

            foreach ($PNRs as $PNR) {
                if ($PNR->getPNR() == $ssAviaTicket['pnr']) {
                    $PNR->setService($this->OrdersServices);

                    switch ($ticketData['ticketAction']) {
                        case 'add':
                            $PNR->addTicket($ssAviaTicket);
                            break;
                        case 'update':
                            $PNR->updateTicket($ticketData['ticketNumber'], $ssAviaTicket);
                            break;
                        default:
                            throw new DomainException("Действие с билетом не поддерживается");
                    }
                    return true;
                }

                throw new InvalidArgumentException("Брони с таким PNR не существует", OrdersErrors::INCORRECT_PNR_NUMBER);
            }
        } else {
            throw new DomainException("В оффере нет броней");
        }
    }

    /**
     * Обертка над старым кодом для возвращения авиационных деталей оффера для УТК
     * @return mixed
     */
    public function getUtkServiceDetails()
    {
        $offer = new FlightOffer();
        $offer->load($this->getOfferId());
        $offer->setSegmentsAirportCityName(LangForm::LANG_RU);
        return $offer->getOfferDetails();
    }

    /**
     * Валидация
     * @param ClassMetadata $metadata
     */
    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('flightTariff', new Assert\NotBlank(array('message' => OrdersErrors::INVALID_FLIGHT_TARIF)));
        $metadata->addPropertyConstraint('lastTIcketingDate', new Assert\LessThan(array('value' => 'today', 'message' => OrdersErrors::INVALID_LAST_TICKETING_DATE)));
        $metadata->addGetterConstraint('lastTicketingDateValid', new Assert\IsTrue(array('message' => OrdersErrors::INVALID_LAST_TICKETING_DATE)));
    }

    /**
     * @return AbstractTripOffer[]
     */
    public function getTrips()
    {
        return $this->AviaTrips;
    }

    /**
     * @return mixed
     */
    public function getDateFrom()
    {
        return $this->getFirstTrip()->getFirstSegment()->getDepartureDate();
    }

    /**
     * @return mixed
     */
    public function getDateTo()
    {
        return $this->getLastTrip()->getLastSegment()->getArrivalDate();
    }

    /**
     * @return mixed
     */
    public function getCityId()
    {
        $cityId = null;

        $lastSegment = $this->getLastSegment();

        if ($lastSegment) {
            $locdata = AirportsForm::getAirportInfoByIataCodeEn($lastSegment->getArrivalAirportCode());
            $cityId = $locdata['cityId'];
        }

        return $cityId;
    }

    /**
     * @return mixed
     */
    public function getCountryId()
    {
        return $this->getFirstTrip()->getLastSegment()->getArrivalAirport()->getCountryId();
    }

    /**
     * @return AviaOfferSegment|null
     */
    protected function getLastSegment()
    {
        $segments = $this->getAviaOfferSegments();

        if (count($segments)) {
            return $segments[count($segments) - 1];
        }

        return null;
    }

    /**
     * @return AviaOfferPrice[]
     */
    public function getPriceOffers()
    {
        return $this->priceOffers;
    }

    /**
     * @return mixed
     */
    public function createTpValueClass()
    {
        return new AviaOfferTravelPolicyValue();
    }

    /**
     * @return mixed
     */
    public function getReservationNumber()
    {
        $PNRs = $this->getPNRs();

        if (count($PNRs)) {
            return $PNRs[0]->getPNR();
        }
        return '';
    }

    /**
     * В офере присутствует сегмент с посадкой в иностранном порту
     * @param
     * @returm
     */
    public function hasSegmentForeigenPort()
    {
        $sng = [1]; // Страны считаемые НЕ иностранными
        $segments = StdLib::nvl($this->getAviaOfferSegments(), []);
        foreach ($segments as $segment) {
            if (isset($segment)) {
                $airPort = AirportsForm::getAirportInfoByIataCodeEn($segment->getArrivalAirportCode());
                $airPortCountryID = StdLib::nvl($airPort['countryId'], 0);
                if (in_array($airPortCountryID, $sng)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     *
     * @param string $type
     * @param $salesTerm
     * @return mixed
     */
    public function updatePrices($type, $salesTerm)
    {
        if ($type == 'client') {
            $this->amountBrutto = $salesTerm['amountBrutto'];
            $this->currencyBrutto = $salesTerm['currency'];
        } else {
            $this->amountNetto = $salesTerm['amountNetto'];
            $this->currencyNetto = $salesTerm['currency'];
        }

        $PriceOffer = AviaPriceOfferRepository::getPriceOfferByOfferIdAndType($this->offerID, $type);

        if ($PriceOffer) {
            $PriceOffer->updateFromSSSalesTerm($salesTerm);
            $PriceOffer->save(false);
        }
    }

    /**
     * У авиа оффера нет активных штрафов за отмену
     * @return null
     */
    public function getActiveCancelPenalty()
    {
        return null;
    }

    /**
     * В авиа пока нет доп услуг
     * @return mixed
     */
    public function getAddOffers()
    {
        return [];
    }

    /**
     * Проверим есть ли билеты в оффере
     * @return bool
     */
    public function hasIssuedTickets()
    {
        $pnrs = $this->getPNRs();
        if (!count($pnrs)) {
            return false;
        }

        foreach ($pnrs as $pnr) {
            if ($pnr->hasIssuedTickets()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Если перелет международный
     * @return bool
     */
    public function isInternationalFlight()
    {
        $hasDepartureRF = false;
        $hasArrivalRF = false;

        $trips = $this->getTrips();

        foreach ($trips as $trip) {
            if ($trip->getFirstSegment()->getDepartureAirport()->getCountry()->isRF()) {
                $hasDepartureRF = true;
            }
            if ($trip->getLastSegment()->getArrivalAirport()->getCountry()->isRF()) {
                $hasArrivalRF = true;
            }
        }

        return !$hasDepartureRF || !$hasArrivalRF;
    }

    /**
     * Если в оффере есть PNR, то бронировать нельзя
     * @return bool
     */
    public function canBeBooked()
    {
        return count($this->getPNRs()) == 0;
    }

    public function getFullDescription()
    {
        $tripDescriptions = [];

        $trips = $this->getTrips();
        foreach ($trips as $trip) {
            $tripDescriptions[] = $trip->getFullTripDescription();
        }

        return implode('; ', $tripDescriptions);
    }

    /**
     * @return mixed
     */
    public function getTimeLimitBookingDate()
    {
        return $this->timeLimitBookingDate;
    }
}