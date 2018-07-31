<?php

/** Модель структуры Offer для услуги авиаперелета */
class FlightOffer extends KFormModel
{

    /** @var mixed[] структура ответа из GPTS */
    private $fields;

    /** @var mixed[] структура оффера */
    private $outStructure;

    /**
     * @param mixed[] $params параметры для формирования структуры
     */
    public function __construct($params)
    {
        $this->fields = $params;
        $this->mapData();
    }

    /**
     * Получение структуры оффера
     * @return mixed[] структура оффера
     */
    public function getStructure()
    {
        return $this->outStructure;
    }

    /** Преобразование структуры данных */
    private function mapData()
    {
        $fields = $this->fields;

        $currency = CurrencyRates::getInstance();

//        $priceBrutto = !empty($fields['extra']['price']['local']['amountBrutto'])
//            ? $fields['extra']['price']['local']['amountBrutto'] /
//            $currency->getCurrencyRate($currency->getIdByCode('EUR'))
//            : '0';

        $this->outStructure = [
            'requestData' => [
                'adult' => hashtableval($fields['extra']['requestData']['adult'], null),
                'children' => hashtableval($fields['extra']['requestData']['children'], null),
                'infants' => hashtableval($fields['extra']['requestData']['infants'], null)
            ],
            'supplierCode' => hashtableval($fields['extra']['supplierCode'], null),
            'supplierId' => SupplierFactory::GPTS_ENGINE, /** @todo hardcode GPTS id, change& */
            'offerKey' => hashtableval($fields['supplierOfferData']['offerKey'], null),
            'price' => [
                'currency' => hashtableval($fields['extra']['price']['nativeSupplier']['supplierCurrency'], null),
                'saleCurrency' => 'EUR', //hashtableval( $fields['extra']['price']['currency'], null ),
                'amountNet' => hashtableval($fields['extra']['price']['nativeSupplier']['amountNetto'], null),
                'amountGross' => hashtableval($priceBrutto, null),
            ],
            'lastTicketingDate' => hashtableval($fields['extra']['lastTicketingDate'], null),
            'flightTariff' => hashtableval($fields['extra']['flightTariff'], null),
            'itinerary' => []
        ];

        foreach ($fields['itinerary'] as $trip) {

            $tripinfo = [
                'routeName' => hashtableval($trip['routeName'], null),
                'duration' => 0,
                'segments' => []
            ];

            for ($i = 0, $len = count($trip['segments']); $i < $len; $i++) {
                $segment = $trip['segments'][$i];

                $seginfo = [
                    'supplierCodeSegment' => null,
                    'segment' => hashtableval($segment['flightSegmentName'], null),
                    'validatingAirline' => hashtableval($segment['validatingAirline'], null),
                    'marketingAirline' => hashtableval($segment['marketingAirline'], null),
                    'operatingAirline' => hashtableval($segment['operatingAirline'], null),
                    'flightNumber' => hashtableval($segment['flightNumber'], null),
                    'aircraft' => hashtableval($segment['aircraftName'], null), // aircraftCode ??
                    'categoryClassType' => hashtableval($segment['categoryClass']['classType'], null),
                    'duration' => hashtableval($segment['duration'], null),
                    'departureAirportCode' => hashtableval($segment['departureAirportCode'], null),
                    'departureDate' => hashtableval($segment['departureDate'], null),
                    'departureTerminal' => hashtableval($segment['departureTerminal'], null),
                    'arrivalAirportCode' => hashtableval($segment['arrivalAirportCode'], null),
                    'arrivalDate' => hashtableval($segment['arrivalDate'], null),
                    'arrivalTerminal' => hashtableval($segment['arrivalTerminal'], null),
                    'mealCode' => hashtableval($segment['mealCode'], null),
                    'mealName' => null,
                    'baggageMeasureCode' => null,
                    'baggageMeasureQuantity' => null,
                    'stopQuantity' => hashtableval($segment['stopQuantity'], null),
                    'stopLocations' => [],
                    'segmentNum' => $i
                ];

                if (isset($segment['duration'])) {
                    $tripinfo['duration'] += (int)$segment['duration'];
                }

                $tripinfo['segments'][] = $seginfo;
            }

            $this->outStructure['itinerary'][] = $tripinfo;
        }
    }

}