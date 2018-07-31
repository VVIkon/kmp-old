<?php

/**
 * Class GptsConfigValidator
 * Класс для проверки корректности значений конфигурации поискового сервиса GPTS
 */
class GptsConfigValidator extends Validator
{

    /**
     * Проверка общих параметров конфигурации
     * @param $params
     * @return bool
     */
    public function checkConfigParams($params) {

        $this->validateComplex($params,[
            ['provider', 'required', 'message' => SearcherErrors::INCORRECT_PROVIDER_SETTINGS],
            ['provider', 'checkProviderSectionConfig', 'message' => SearcherErrors::INCORRECT_PROVIDER_SETTINGS],
            ['suppliers', 'checkSuppliersSectionConfig', 'message' => SearcherErrors::INCORRECT_SUPPLIER_SETTINGS],
        ]);

        return true;
    }

    /**
     * Проверка конфигурационной секции соединения со шлюзом
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkProviderSectionConfig($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        $this->validateComplex($values[$attribute],[
            ['test_api', 'required','message' => SearcherErrors::TEST_API_SECTION_NOT_SET],
            ['prod_api', 'required','message' => SearcherErrors::PROD_API_SECTION_NOT_SET],

            ['test_api', 'checkApiSettings','message' => SearcherErrors::INCORRECT_TEST_API_SETTINGS],
            ['prod_api', 'checkApiSettings','message' => SearcherErrors::INCORRECT_PROD_API_SETTINGS],
        ]);

        return true;
    }

    /**
     * Проверка конфигурационной секции поставщиков по типу предложений
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkSuppliersSectionConfig($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute]) || !is_array($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        foreach ($values[$attribute] as $serviceType => $serviceSuppliers) {
            if (!is_array($serviceSuppliers)) {
                throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'],
                    [
                        'serviceType' => $serviceType
                    ]
                );
            }
        }

        return true;
    }

    /**
     * Проверка корректности структуры и параметров конфигурационной секции API провайдера
     * @param $values проверяемые значения
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkApiSettings($values, $attribute, $params) {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        $values = $values[$attribute];

        if (empty($values['authInfo']['key'])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'],
                ["['authInfo']['key']"]);
        }

        if (empty($values['authInfo']['login'])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'],
                ["['authInfo']['login']"]);
        }

        if (empty($values['authInfo']['password'])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'],
                ["['authInfo']['password']"]);
        }

        if (empty($values['url'])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], ["['url']"]);
        }

        if (empty($values['actions']) || count($values['actions']) == 0) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], ["['actions']"]);
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
    public function checkScheduleParams($values, $attribute, $params) {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',SearcherErrors::INCORRECT_VALIDATION_RULES);
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
    public function checkFlightClassValue($values, $attribute, $params) {

        if (empty($values['flightClass']) || !FlightClass::checkClassExists($values['flightClass'])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }
        return true;
    }

    public function checkRouteSectionParam($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        foreach ($values[$attribute] as $route) {
            $this->validateComplex($route, [
                ['from', 'required', 'message' => SearcherErrors::INCORRECT_ROUTE_PARAMS],
                ['to', 'required', 'message' => SearcherErrors::INCORRECT_ROUTE_PARAMS],
                ['date', 'required', 'message' => SearcherErrors::INCORRECT_ROUTE_PARAMS],
            ]);
        }

        return true;
    }

    /**
     * Проверка входных параметров
     * @param $params
     * @return bool
     */
    public function checkInputScheduleParams($params) {

        $this->validateComplex($params,[
            ['route', 'required', 'message' => SearcherErrors::INCORRECT_ROUTE_PARAMS],
            ['route', 'checkRouteSectionParam', 'message' => SearcherErrors::INCORRECT_ROUTE_PARAMS],
        ]);

        return true;
    }

}