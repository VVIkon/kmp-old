<?php

/**
 * Class OfferValidator
 * Класс для проверки корректности значений при работе с данными предложений
 */
class OfferValidator extends Validator
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
     * Проверка общих параметров запроса поиска предложений
     * @param $params array
     * @return bool
     */
    public function checkFindOfferParams($params)
    {

        if (empty($params['serviceType'])) {
            throw new KmpInvalidArgumentException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::SERVICE_TYPE_NOT_SET,
                $params
            );

            return false;
        }

        if (!OfferValidatorsFactory::createOfferValidator($params['serviceType'], $this->module)) {

            throw new KmpInvalidArgumentException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::SERVICE_TYPE_INCORRECT,
                $params
            );
        }

    }

    /**
     * Проверка параметров запроса на получение доп услуг отеля
     * @param $params
     */
    public function checkGetHotelAdditionalService($params)
    {
        $this->validateComplex($params, [
            ['offerId', 'required', 'message' => SearcherErrors::OFFER_ID_NOT_SET],
            ['lang', 'required', 'message' => SearcherErrors::LANGUAGE_NOT_SET],
            ['lang', 'checkLang', 'message' => SearcherErrors::LANGUAGE_NOT_SET],
            ['viewCurrency', 'required', 'message' => SearcherErrors::RESPONSE_CURRENCY_NOT_SET],
            ['viewCurrency', 'checkCurrency', 'message' => SearcherErrors::RESPONSE_CURRENCY_NOT_SET],
        ]);
    }

    /**
     * Проверка параметров запроса на получение найденных предложений
     * @param $params
     */
    public function checkGetSearchResultParams($params)
    {
        $this->validateComplex($params, [
            ['searchToken', 'required', 'message' => SearcherErrors::REQUEST_TOKEN_NOT_SET],
            ['currency', 'required', 'message' => SearcherErrors::RESPONSE_CURRENCY_NOT_SET],
            ['lang', 'required', 'message' => SearcherErrors::RESPONSE_LANG_NOT_SET],
            ['currency', 'checkCurrency', 'message' => SearcherErrors::INCORRECT_RESPONSE_CURRENCY],
            ['lang', 'checkLang', 'message' => SearcherErrors::INCORRECT_RESPONSE_LANG],
            ['searchToken',
                'length',
                'min' => 16,
                'max' => 16,
                'tooShort' => SearcherErrors::INCORRECT_REQUEST_TOKEN,
                'tooLong' => SearcherErrors::INCORRECT_REQUEST_TOKEN
            ],
            ['searchToken', 'checkTokenExists', 'message' => SearcherErrors::INCORRECT_REQUEST_TOKEN],
            ['startOfferId', 'required', 'message' => SearcherErrors::ORDERNUM_NOT_SET],
            ['offerLimit', 'required', 'message' => SearcherErrors::OFFER_LIMIT_NOT_SET]
        ]);
    }

    /**
     * Проверка существования типа услуги
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkServiceTypeExists($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute])) {
            return false;
        }

        if (!ServicesFactory::isServiceTypeExist($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }


    /**
     * Проверка существования указанного поставщика услуг
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkSupplierExists($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute]) || !ProvidersFactory::isProviderExists($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка корректности кода валюты
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkCurrency($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        $CurrencyRates = CurrencyRates::getInstance();

        if (!$CurrencyRates->getIdByCode($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка корректности кода языка
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkLang($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (!LangForm::GetLanguageCodeByName($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка существования предложений
     * по указанному токену запроса
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkTokenExists($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', SearcherErrors::INCORRECT_VALIDATION_RULES);
        }

        if (!OfferFinder::getOfferTypeByToken($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка параметров поиска предложений
     * @param $params
     * @return bool
     */
    public function checkRequestParams($params)
    {
        return false;
    }

}