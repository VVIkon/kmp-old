<?php

/**
 * Class FileRequestValidator
 * Класс для проверки корректности параметров запросов
 * к файловому менеджеру
 */
class FileRequestValidator extends Validator
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
    public function checkFileUploadParams($params) {

        $this->validateComplex($params, [
            ['presentationFileName', 'required', 'message' => SysSvcErrors::PRESENTATION_FILE_NAME_NOT_SET],
            ['comment', 'included', 'message' => SysSvcErrors::FILE_COMMENT_NOT_SET],
            ['orderId', 'required', 'message' => SysSvcErrors::ORDER_ID_NOT_SET],
            ['objectType', 'required', 'message' => SysSvcErrors::OBJECT_TYPE_NOT_SET],
            ['objectType', 'checkObjectType', 'message' => SysSvcErrors::INCORRECT_OBJECT_TYPE],
            ['objectId', 'required', 'message' => SysSvcErrors::OBJECT_ID_NOT_SET],
            ['url', 'required', 'message' => SysSvcErrors::URL_NOT_SET],
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
    public function checkObjectType($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class(), __FUNCTION__, SysSvcErrors::INCORRECT_VALIDATION_RULES,[]);
        }

        if (!BusinessEntityTypes::checkTypeExists($values[$attribute])) {
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