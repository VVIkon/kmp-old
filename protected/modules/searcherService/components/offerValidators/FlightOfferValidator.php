<?php

/**
 * Class FlightOfferValidator
 * Класс для проверки корректности значений при поиске предложения авиаперелёта
 */
class FlightOfferValidator extends OfferValidator
{

    /**
     * Проверка параметров поиска предложений по авиабилетам
     * @param $params
     * @return bool
     */
    public function checkRequestParams($params)
    {

        $this->validateComplex($params, [
            ['route', 'required', 'message' => SearcherErrors::ROUTE_NOT_SET],
            ['flightClass', 'included', 'message' => SearcherErrors::FLIGHT_CLASS_NOT_SET],
            ['flexibleDays', 'included', 'message' => SearcherErrors::FLEXIBLE_DAYS_NOT_SET],
            ['adult', 'required', 'message' => SearcherErrors::ADULT_NUM_NOT_SET],
            ['children', 'required', 'message' => SearcherErrors::CHILDREN_NUM_NOT_SET],
            ['infants', 'required', 'message' => SearcherErrors::INFANTS_NUM_NOT_SET],
            ['childrenAges', 'included', 'message' => SearcherErrors::CHILDREN_AGES_NOT_SET],
            ['route', 'checkRouteParams', 'message' => SearcherErrors::INCORRECT_ROUTE_PARAMS],
            ['searchBySchedule', 'checkScheduleParams', 'message' => SearcherErrors::INCORRECT_SCHEDULE_PARAMS],
            ['flightClass', 'checkFlightClassValue', 'message' => SearcherErrors::INCORRECT_FLIGHT_CLASS],
            ['charter', 'boolean', 'message' => SearcherErrors::INCORRECT_CHARTER_VALUE],
            ['regular', 'boolean', 'message' => SearcherErrors::INCORRECT_REGULAR_VALUE],
            ['directFlight', 'required', 'message' => SearcherErrors::DIRECT_FLIGHT_VALUE_NOT_SET],
            ['directFlight', 'boolean', 'message' => SearcherErrors::INCORRECT_DIRECT_FLIGHT_VALUE],
            ['flightNumber', 'included', 'message' => SearcherErrors::FLIGHT_NUMBER_NOT_SET],
            ['supplierCode', 'included', 'message' => SearcherErrors::SUPPLIER_CODE_NOT_SET],
            ['airlineCode', 'checkAirlineCodes', 'message' => SearcherErrors::INCORRECT_AIRLINE_CODES],
            ['uniteOffers', 'included', 'message' => SearcherErrors::UNITE_OFFERS_NOT_SET],
            ['uniteOffers', 'boolean', 'message' => SearcherErrors::INCORRECT_UNITE_OFFERS_VALUE],
            [
                'flexibleDays',
                'numerical',
                'integerOnly' => true,
                'min' => 0,
                'tooSmall' => SearcherErrors::INCORRECT_FLEXIBLE_DAYS_VALUE
            ],
            [
                'adult',
                'numerical',
                'integerOnly' => true,
                'min' => 0,
                'tooSmall' => SearcherErrors::INCORRECT_ADULTS_NUM_VALUE
            ],
            [
                'children',
                'numerical',
                'integerOnly' => true,
                'min' => 0,
                'tooSmall' => SearcherErrors::INCORRECT_CHILDREN_NUM_VALUE
            ],
            [
                'infants',
                'numerical',
                'integerOnly' => true,
                'min' => 0,
                'tooSmall' => SearcherErrors::INCORRECT_INFANTS_NUM_VALUE
            ],
            [
                'offerLimit',
                'numerical',
                'integerOnly' => true,
                'min' => 1,
                'tooSmall' => SearcherErrors::INCORRECT_LIMIT_COUNT_VALUE
            ],
            [
                'adult',
                'checkAges',
                'message' => SearcherErrors::INCORRECT_INPUT_PARAMS
            ]
        ]);

        return true;
    }

    /**
     * Проверка корректности структуры и параметров маршрута
     * @param $values проверяемые значения
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkRouteParams($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        $values = $values['route'];

        if (empty($values['trips']) || count($values['trips']) == 0) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        foreach ($values['trips'] as $trip) {

            if (empty($trip['from']) || !AirportsForm::getAirportByIata($trip['from'])) {
                throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $trip);
            }

            if (empty($trip['to']) || !AirportsForm::getAirportByIata($trip['to'])) {
                throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $trip);
            }

            if (empty($trip['date']) || !$this->validateDate($trip['date'])) {
                throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $trip);
            }
        }

        if (empty($values['triptype']) || !TripType::checkTypeExists($values['triptype'])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        if ($values['triptype'] == TripType::ROUND_TRIP && count($values['trips']) != 2) {
            throw new KmpInvalidArgumentException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::INCORRECT_TRIPS_NUMBER,
                $values
            );
        }

        return true;
    }

    /**
     * Проверка на возраста на старте поиска
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkAges($values, $attribute, $params)
    {
        if(!($values['adult'] || $values['children'] || $values['infants'])){
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка параметров расписания поиска
     * @param $values проверяемые значения
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkScheduleParams($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values['searchBySchedule'])) {
            return true;
        }

        $values = $values['searchBySchedule'];

        if (empty($values['dateFrom']) || !$this->validateDate($values['dateFrom'])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        if (empty($values['dateTo']) || !$this->validateDate($values['dateTo'])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка значения класса полёта
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkFlightClassValue($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (!empty($values['flightClass']) && !FlightClass::checkClassExists($values['flightClass'])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка типа поля airlineCode
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkAirlineCodes($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (!isset($values[$attribute])) {
            return true;
        } else {
            $this->isArray($values, $attribute, $params);
        }
        return true;
    }


}