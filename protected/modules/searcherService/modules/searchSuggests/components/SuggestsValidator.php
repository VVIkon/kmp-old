<?php

/**
 * Class SuggestsValidator
 * Класс для проверки корректности значений при работе с данными подсказок ввода
 */
class SuggestsValidator extends Validator
{
    /**
     * namespace для записи логов
     * @var string
     */
    private $namespace;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        parent::__construct($module);

        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Проверка параметров для поиска подсказок для выбора аэропорта
     * @param $params array
     * @return bool
     */
    public function checkSuggestCommonParams($params)
    {

        $this->validateComplex($params,[
//            ['location', 'required','message' => SearcherErrors::SEARCH_TEXT_NOT_SET],
            ['lang', 'required','message' => SearcherErrors::LANGUAGE_NOT_SET],
//            ['location', 'length','min' => 2, 'tooShort' => SearcherErrors::INCORRECT_SEARCH_TEXT],
            ['lang', 'isLanguageSupported','message' => SearcherErrors::UNSUPPORTED_LANGUAGE],
            ['serviceType', 'required','message' => SearcherErrors::SERVICE_TYPE_NOT_SET],
            ['serviceType', 'checkServiceType','message' => SearcherErrors::SERVICE_TYPE_INCORRECT]
        ]);

        return true;
    }

    /**
     * Проверка параметров для поиска подсказок по отелю
     * @param $params
     * @return bool
     */
    public function checkSuggestHotelParams($params)
    {
        $this->validateComplex($params,[
            ['hotelName', 'required','message' => SearcherErrors::SEARCH_TEXT_NOT_SET],
            ['hotelName', 'length','min' => 2, 'tooShort' => SearcherErrors::INCORRECT_SEARCH_TEXT],
            ['lang', 'required','message' => SearcherErrors::LANGUAGE_NOT_SET],
            ['lang', 'isLanguageSupported','message' => SearcherErrors::UNSUPPORTED_LANGUAGE],
            ['hotelFilters', 'checkHotelFilterParams','message' => SearcherErrors::INCORRECT_HOTEL_FILTERS_PARAMS],
        ]);

        return true;
    }

    /**
     * Проверка поддержки указанного в параметрах языка
     * @param $values параметры для валидации
     * @param $attribute проверяемый атрибут
     * @param $params дополнительные параметры с кодом ошибки
     * @return bool
     */
    public function isLanguageSupported($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        $langId = LangForm::GetLanguageCodeByName($values[$attribute]);

        if (empty($langId)) {
            $this->addError($attribute, $params['message']);
        }

        if ($this->hasErrors()) {
            $error = ((new ArrayIterator($this->getErrors()))->current());
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $error[0], $values);
        }

        return true;
    }

    /**
     * Порверка корректности указанного типа услуги
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkServiceType($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        $suggestClass = SuggestsFactory::createSuggestClass($values[$attribute]);

        if (empty($suggestClass)) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка параметров фильтрации предложений отелей
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkHotelFilterParams($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute])) {
            return true;
        }


        $this->validateComplex($values[$attribute],[
            ['cityId', 'included','message' => SearcherErrors::CITY_ID_NOT_SET],
        ]);
        if (!empty($values[$attribute]['cityId'])) {

            $this->validateComplex($values[$attribute],[
                ['cityId', 'checkCityIdExist', 'message' => SearcherErrors::INCORRECT_CITY_ID]
            ]);
        }

        return true;
    }

    /**
     * Проверка существования указанного идентификатора города
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkCityIdExist($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('',SearcherErrors::INCORRECT_VALIDATION_RULES);
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