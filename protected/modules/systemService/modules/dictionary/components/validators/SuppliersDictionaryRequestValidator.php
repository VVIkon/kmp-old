<?php

/**
 * Class SuppliersDictionaryRequestValidator
 * Класс для проверки корректности параметров запросов
 * к справочным данным поставщиков
 */
class SuppliersDictionaryRequestValidator extends Validator
{
    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        $this->module = $module;
    }

    /**
     * Проверка параметров для загрузки файла в хранилище КТ
     * @param $params
     * @return bool
     */
    public function checkDictionaryFilterParams($params)
    {

        $this->validateComplex($params, [
            //['serviceId', 'required', 'message' => SysSvcErrors::SERVICE_TYPE_NOT_SET],
            ['serviceId', 'checkServiceType', 'message' => SysSvcErrors::INCORRECT_SERVICE_TYPE],
            [
                'maxRowCount',
                'numerical',
                'integerOnly' => true,
                'message' => SysSvcErrors::INCORRECT_ROWS_COUNT
            ],
            ['fieldsFilter', 'checkFilterFields', 'message' => SysSvcErrors::INCORRECT_FILTER_FIELD_VALUE],
            ['lang', 'required', 'message' => SysSvcErrors::LANGUAGE_NOT_SET],
            ['lang', 'checkLang', 'message' => SysSvcErrors::INCORRECT_LANGUAGE_CODE],
        ]);

        return true;
    }

    /**
     * Проверка существования типа услуги
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkServiceType($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class(), __FUNCTION__, SysSvcErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (empty($values[$attribute])) {
            return true;
        }

        if (!ServiceTypeForm::isServiceTypeExist($values[$attribute])) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                $params['message'],
                $values
            );
        }

        return true;
    }

    /**
     * Проверка существования полей фильтрации
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkFilterFields($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class(), __FUNCTION__, SysSvcErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (empty($values[$attribute])) {
            return true;
        }

        if (!is_array($values[$attribute])) {

            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                $params['message'],
                $values
            );
        }

        $fields = SuppliersForm::getSupplierFields(false);

        foreach ($values[$attribute] as $fieldName) {
            if (!in_array($fieldName, $fields)) {
                throw new KmpException(get_class(), __FUNCTION__, $params['message'], ['fieldName' => $fieldName]);
            }
        }

        return true;
    }
}