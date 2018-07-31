<?php

/**
 * Class AccomodationOfferValidator
 * Класс для проверки корректности значений при поиске предложения размещения
 */
class AccomodationOfferValidator extends OfferValidator
{

    /**
     * Проверка параметров поиска предложений по авиабилетам
     * @param $params
     * @return bool
     */
    public function checkRequestParams($params)
    {

        $this->validateComplex($params, [
            ['supplierCode', 'included', 'message' => SearcherErrors::SUPPLIER_CODE_NOT_SET],
            //['clientId', 'included','message' => SearcherErrors::CLIENT_COMPANY_NOT_SET],
            ['route', 'required', 'message' => SearcherErrors::ROUTE_NOT_SET],
            ['route', 'checkRouteParams', 'message' => SearcherErrors::INCORRECT_ACCOMODATION_PLACE_STRUCT],
            ['flexibleDates', 'included', 'message' => SearcherErrors::FLEXIBLE_DAYS_NOT_SET],
            ['freeOnly', 'included', 'message' => SearcherErrors::FREE_ONLY_PARAM_NOT_SET],
            ['freeOnly', 'boolean', 'message' => SearcherErrors::INCORRECT_FREE_ONLY_PARAM],
            ['rooms', 'required', 'message' => SearcherErrors::ROOMS_PARAM_NOT_SET],
            ['rooms', 'checkRoomsStructure', 'message' => SearcherErrors::INCORRECT_ROOMS_STRUCT],

            ['hotelCode', 'included', 'message' => SearcherErrors::HOTEL_CODE_NOT_SET],
            ['hotelSupplier', 'included', 'message' => SearcherErrors::HOTEL_SUPPLIER_NOT_SET],
            ['hotelIdKt', 'checkHotelIdKT', 'message' => SearcherErrors::HOTEL_ID_KT_INCORRECT],
            [
                'category',
                'numerical',
                'integerOnly' => true,
                'min' => 1,
                'max' => 5,
                'tooSmall' => SearcherErrors::INCORRECT_ACCOMODATION_CATEGORY,
                'tooBig' => SearcherErrors::INCORRECT_ACCOMODATION_CATEGORY
            ],
            ['hotelChains', 'isArray', 'allowEmpty' => true, 'message' => SearcherErrors::HOTELS_CHAIN_NOT_SET],
            ['mealType', 
                'type' => 'string',
                'allowEmpty' => true,
                'message' => SearcherErrors::MEAL_TYPE_NOT_SET
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

        $this->validateComplex($values[$attribute], [
            ['city', 'required', 'message' => $params['message']],
            ['city', 'checkCityId', 'message' => SearcherErrors::INCORRECT_CITY_ID],
            ['dateStart', 'included', 'message' => $params['message']],
            ['dateFinish', 'required', 'message' => $params['message']],
        ]);

        if (isset($values['route']) && isset($values['route']['dateStart']) && isset($values['route']['dateFinish'])) {
            if ($this->isDateAfter($values['route']['dateStart'], $values['route']['dateFinish'])) {
                throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, SearcherErrors::DATE_START_GREATER_THAN_FINISH, $values);
            }
        }

        return true;
    }

    /**
     * Проверка корректност структуры и параметров номеров проживания
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkRoomsStructure($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        foreach ($values[$attribute] as $room) {
            if (empty($room)) {
                throw new KmpInvalidArgumentException(
                    get_class($this),
                    __FUNCTION__,
                    SearcherErrors::INCORRECT_ACCOMMODATION_ROOMS_STRUCT,
                    $params['message']
                );
            }

            $this->validateComplex($room, [
                ['adults', 'required', 'message' => $params['message']],
//                ['children', 'included', 'message' => $params['message']],
                ['childrenAges', 'isArray', 'allowEmpty' => true, 'message' => $params['message']],
            ]);
        }

        return true;
    }

    /**
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkHotelIdKT($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (!isset($values[$attribute]) || empty($values[$attribute])) {
            return true;
        }

        $hotelInfo = HotelsForm::getHotelInfo($values[$attribute]);

        if (empty($hotelInfo)) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values[$attribute]);
        }

        return true;
    }

    /**
     * Проверка существования указанного идентифкатора города
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkCityId($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        try {
            $cityInfo = CityForm::getCityInfoById($values[$attribute]);
        } catch (KmpDbException $kde) {
            throw new KmpException(
                get_class(),
                __FUNCTION__,
                $params['message'],
                [
                    'command' => $kde->params,
                    'message' => $kde->getDbMessage()
                ]
            );
        }
        if (empty($cityInfo)) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }


}
