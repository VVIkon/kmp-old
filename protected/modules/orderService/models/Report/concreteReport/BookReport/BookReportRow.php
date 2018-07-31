<?php

/**
 * Class BookReportRow
 * Строка для реестрового отчета
 */
class BookReportRow
{
    private $row = [
        'companyName' => '',
        'companyManager' => '',
        'KMPManager' => '',
        'creator' => '',
        'fio' => '',    //
//        'addFields' => '', //
        'online' => '',
        'serviceType' => '',
        'men' => '',
        'international' => '',
        'classType' => '',
        'classCode' => '',
        'flightType' => '',
        'serviceStatus' => '',
        'trip' => '',   //
        'departureTime' => '',
        'arrivalTime' => '',
        'duration' => '',
        'validatingAirline' => '',
        'departurePlace' => '',
        'arrivalPlace' => '',
        'ticketNumber' => '',
        'hotelName' => '',
        'hotelCategory' => '',
        'hotelRoomType' => '',
        'nights' => '',
        'priceInSupplierCurrency' => '',
        'priceInClientCurrency' => ''
    ];

    /**
     * @var AdditionalFieldType[]
     */
    private $addFieldTypes = [];
    private $addFields = [];

    public function __construct($addFieldTypes)
    {
        $this->addFieldTypes = $addFieldTypes;
    }

    /**
     * @param OrderModel $order
     */
    public function setOrderData(OrderModel $order)
    {
        $this->row['companyName'] = $order->getCompany()->getName();

        $companyManager = $order->getCompanyManager();
        if ($companyManager) {
            $this->row['companyManager'] = $companyManager->getShortFIO();
        }

        $KMPManager = $order->getKMPManager();
        if ($KMPManager) {
            $this->row['KMPManager'] = $KMPManager->getShortFIO();
        }

        $creator = $order->getCreator();
        if ($creator) {
            $this->row['creator'] = $creator->getShortFIO();
        }
    }

    /**
     * @param OrdersServices $service
     */
    public function setServiceData(OrdersServices $service)
    {
        $this->row['online'] = ($service->isOffline()) ? 'Оффлайн' : 'Онлайн';
        $this->row['serviceType'] = $service->getRefService()->getName();
        $this->row['serviceStatus'] = $service->getServiceStatusName();

        $supplierCurrency = $service->getSupplierCurrency();
        if($supplierCurrency){
            $this->row['priceInSupplierCurrency'] = (string)$service->getMoney($supplierCurrency);
        }
        $this->row['priceInClientCurrency'] = (string)$service->getMoney(CurrencyStorage::getById(643));

        $departureTime = new DateTime($service->getDateStart());
        $arrivalTime = new DateTime($service->getDateEnd());

        $this->row['departureTime'] = $departureTime->format('d/m/Y');
        $this->row['arrivalTime'] = $arrivalTime->format('d/m/Y');

        // установка информации по доп полям
        foreach ($this->addFieldTypes as $addFieldType) {
            if ($addFieldType->isServiceField()) {
                $serviceAddField = OrderAdditionalFieldRepository::getServiceFieldWithId($service, $addFieldType->getId());
                $this->addFields[] = ($serviceAddField) ? $serviceAddField->getValue() : '';
            }
        }
    }

    /**
     *
     * @param AviaOffer $offer
     */
    public function setAviaOffer(AviaOffer $offer)
    {
        $this->row['international'] = ($offer->isInternationalFlight()) ? 'международная' : 'внутренняя';
        $this->row['classType'] = $offer->getClassType();
        $this->row['classCode'] = $offer->getClassCode();
        $this->row['flightType'] = $offer->getFlightType();
        $this->row['duration'] = $offer->getTotalDuration();
        $this->row['validatingAirline'] = $offer->getValidatingAirline();

        $departureCity = $offer->getFirstTrip()->getFirstSegment()->getDepartureAirport()->getCity();
        $departureCountry = $offer->getFirstTrip()->getFirstSegment()->getDepartureAirport()->getCountry();
        $this->row['departurePlace'] = implode(', ', [$departureCity->getName(), $departureCountry->getName()]);

        $arrivalCity = $offer->getFirstTrip()->getFirstSegment()->getArrivalAirport()->getCity();
        $arrivalCountry = $offer->getFirstTrip()->getFirstSegment()->getArrivalAirport()->getCountry();
        $this->row['arrivalPlace'] = implode(', ', [$arrivalCity->getName(), $arrivalCountry->getName()]);

        $this->row['trip'] = $offer->getFullDescription();
    }

    /**
     *
     * @param HotelOffer $offer
     */
    public function setHotelOffer(HotelOffer $offer)
    {
        $this->row['hotelName'] = $offer->getHotelInfo()->getHotelName();
        $this->row['hotelCategory'] = $offer->getHotelInfo()->getStars();
        $this->row['hotelRoomType'] = $offer->getRoomType();
        $this->row['nights'] = $offer->getNights();

        $cityName = $offer->getHotelInfo()->getCity()->getName();
        $countryName = $offer->getHotelInfo()->getCity()->getCountry()->getName();

        $this->row['arrivalPlace'] = implode(', ', [$cityName, $countryName]);
    }

    public function setTicket(AviaTicket $ticket)
    {
        $this->row['ticketNumber'] = $ticket->getTicketNumber();
        $this->row['men'] = 1;

        $orderTourist = $ticket->getOrderTourist();
        $this->row['fio'] = (string)$orderTourist->getTourist();

        foreach ($this->addFieldTypes as $addFieldType) {
            if ($addFieldType->isAccountField()) {
                $touristAddField = OrderAdditionalFieldRepository::getTouristFieldWithId($orderTourist, $addFieldType->getId());
                $this->addFields[] = [($touristAddField && $touristAddField->getValue()) ? $touristAddField->getValue() : '', 'tag' => 'additionalFields'];
            }
        }
    }

    /**
     * @param OrdersServicesTourists [] $serviceTourists
     */
    public function setTouristFIOs(array $serviceTourists)
    {
        $fios = [];
        $addFieldsValues = [];

        // переберем всех туристов с тем, чтобы сформировать поле ФИО и найти значения доп полей для них
        foreach ($serviceTourists as $serviceTourist) {
            $fios[] = (string)$serviceTourist->getOrderTourist()->getTourist();
            foreach ($this->addFieldTypes as $addFieldTypeNum => $addFieldType) {
                if ($addFieldType->isAccountField()) {
                    $touristAddField = OrderAdditionalFieldRepository::getTouristFieldWithId($serviceTourist->getOrderTourist(), $addFieldType->getId());
                    $addFieldsValues[$addFieldTypeNum][] = ($touristAddField && $touristAddField->getValue()) ? "{$serviceTourist->getOrderTourist()->getTourist()}: {$touristAddField->getValue()}" : '';
                }
            }
        }

        $this->row['fio'] = implode(', ', $fios);
        $this->row['men'] = count($fios);

        foreach ($addFieldsValues as $addFieldsValue) {
            $cellVal = '';
            foreach ($addFieldsValue as $oneTouristAddFieldValue) {
                if ($oneTouristAddFieldValue) {
                    $cellVal .= "{$oneTouristAddFieldValue}, ";
                }
            }
            $this->addFields[] = [$cellVal, 'tag' => 'additionalFields'];
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $answerRows = array_values($this->row);

        array_splice($answerRows, 5, 0, $this->addFields);
        return $answerRows;
    }
}