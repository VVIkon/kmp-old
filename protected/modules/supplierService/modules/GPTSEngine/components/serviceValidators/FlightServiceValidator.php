<?php

/** Класс-валидатор методов работы с услугой перелета */
class FlightServiceValidator extends ServiceValidator
{

    /**
     * Проверка параметров структуры supplierOfferData для команды GetOffer для сервиса авиа
     * @param mixed[] $params параметры команды
     * @return bool результат проверки
     * @throws KmpInvalidArgumentException ошибка валидации
     */
    public function checkGetOffer($params)
    {
        if (empty($params['offerKey'])) {
            throw new KmpInvalidArgumentException(
                get_class(), __FUNCTION__,
                SupplierErrors::OFFER_KEY_NOT_SET,
                ['offerKey' => isset($params['offerKey']) ? $params['offerKey'] : 'not set']
            );
            return false;
        } else {
            return true;
        }
    }

    /**
     * Проверка параметров структуры supplierOfferData для команды ServiceBooking для сервиса авиа
     * @param mixed[] $params параметры команды
     * @return bool результат проверки
     * @throws KmpInvalidArgumentException ошибка валидации
     */
    public function checkServiceBooking($params)
    {
        $this->validateComplex($params, [
            ['offerKey', 'required', 'message' => SupplierErrors::OFFER_KEY_NOT_SET],
            ['tourists', 'checkTouristsData', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
        ]);

        return true;
    }

    /**
     * Проверка корректности параметров данных туристов
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     * @throws KmpInvalidArgumentException
     */
    public function checkTouristsData($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(
                get_class($this), __FUNCTION__,
                SupplierErrors::INCORRECT_VALIDATION_RULES,
                []
            );
        }


        if (empty($values[$attribute]) || !is_array($values[$attribute])) {
            throw new KmpInvalidArgumentException(
                get_class($this), __FUNCTION__,
                $params['message'],
                $values
            );
        }


        foreach ($values[$attribute] as $tourist) {
            $this->validateComplex($tourist, [
                ['citizenshipId', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['email', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['phone', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['sex', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['firstName', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['lastName', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['birthdate', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
            ]);
        }

        return true;
    }

    /**
     * Проверка параметров для выполнения запроса выписки билетов
     * @param $params
     * @return bool
     */
    public function checkIssueTickets($params)
    {
        $this->validateComplex($params, [
            ['pnrData', 'required', 'message' => SupplierErrors::INCORRECT_PNR_DATA],
//            todo добавить проверку параметров из секции segments после реализации мультисегментного перелёта
        ]);

        return true;
    }

    /**
     * Проверка структуры полученной в ответ от поставщика услуги авиаперелёта
     * @param $params
     * @return bool
     */
    public function checkGetOrdersResult($orderInfo)
    {
        if (empty($orderInfo[0]['services'])) {
            throw new KmpException(
                get_class(),
                __FUNCTION__,
                SupplierErrors::NO_SERVICES_IN_ORDER,
                ['orderInfo' => $orderInfo]
            );
        }

        foreach ($orderInfo[0]['services'] as $service) {

            switch ($service['serviceType']) {
                case 'FLIGHT' :
                    $this->validateComplex($service, [
                        ['travelers', 'required', 'message' => SupplierErrors::NO_TOURISTS_IN_SERVICE],
//            todo добавить проверку параметров из секции segments после реализации мультисегментного перелёта
                    ]);
                    break;
                default :
                    break;
            }

        }
        return true;
    }

    /**
     * Проверка параметров туриста в ответе GPTS
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkTravelersGPTS($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(
                get_class($this),
                __FUNCTION__,
                SupplierErrors::INCORRECT_VALIDATION_RULES,
                []
            );
        }

        if (empty($values[$attribute])) {
            throw new KmpException(
                get_class(),
                __FUNCTION__,
                SupplierErrors::NO_TOURISTS_IN_SERVICE,
                ['serviceInfo' => $values]
            );
        }

        foreach ($values[$attribute] as $traveler) {

            $this->validateComplex($traveler, [
                ['isTourLead', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['isChild', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['citizenshipId', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['prefix', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['firstName', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['middleName', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['lastName', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['dateOfBirth', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['email', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['phone', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['customFields', 'included', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['passports', 'required', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['passports', 'checkPassportParams', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS],
                ['bonusCards', 'included', 'message' => SupplierErrors::INCORRECT_TOURISTS_PARAMS]
            ]);
        }
        return true;
    }

    /**
     * Проверка параметров паспорта туриста в ответе GPTS
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkPassportParamsGPTS($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(
                get_class($this),
                __FUNCTION__,
                SupplierErrors::INCORRECT_VALIDATION_RULES,
                []
            );
        }

        if (empty($values[$attribute])) {

            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                SupplierErrors::INCORRECT_TOURIST_PASSPORT_PARAMS,
                ['touristInfo ' => $values]
            );
        }

        $this->validateComplex($values[$attribute], [
            ['number', 'required', 'message' => SupplierErrors::INCORRECT_TOURIST_PASSPORT_PARAMS],
            ['issueDate', 'required', 'message' => SupplierErrors::INCORRECT_TOURIST_PASSPORT_PARAMS],
            ['expiryDate', 'required', 'message' => SupplierErrors::INCORRECT_TOURIST_PASSPORT_PARAMS],
        ]);

        return true;
    }
}

?>
