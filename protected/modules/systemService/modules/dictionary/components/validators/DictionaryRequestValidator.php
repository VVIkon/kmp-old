<?php

/**
 * Class DictionaryRequestValidator
 * Класс для проверки корректности параметров запросов
 * к справочным данным
 */
class DictionaryRequestValidator extends Validator
{
    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module) {
        $this->module = $module;
    }

    /**
     * Проверка параметров для загрузки файла в хранилище КТ
     * @param $params
     * @return bool
     */
    public function checkGetDictionaryParams($params) {

        $this->validateComplex($params, [
            ['dictionaryType', 'required', 'message' => SysSvcErrors::DICTIONARY_TYPE_NOT_SET],
            ['dictionaryType', 'checkDictionaryType', 'message' => SysSvcErrors::INCORRECT_DICTIONARY_TYPE],
            ['dictionaryFilter', 
                'isArray', 
                'allowEmpty' => true,
                'message' => SysSvcErrors::DICTIONARY_FILTER_NOT_SET
            ]
        ]);

        return true;
    }

    /**
     * Проверка типа бизнес объекта
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkDictionaryType($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class(), __FUNCTION__, SysSvcErrors::INCORRECT_VALIDATION_RULES,[]);
        }

        if (!DictionariesFactory::isDictionaryHandlerExists($values[$attribute])) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                $params['message'],
                $values
            );
        }

        return true;
    }
}