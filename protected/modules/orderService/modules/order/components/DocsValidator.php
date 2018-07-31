<?php

/**
 * Class DocsValidator
 * Класс для проверки корректности значений при работе с приложенными документами
 */
class DocsValidator extends Validator
{
    /**
     * Код ошибки
     * @var int
     */
    private $errorCode;


    public function checkGetOrderDocumentsParams($params)
    {
        $this->validateComplex($params,[
            ['orderId', 'required', 'message' => OrdersErrors::ORDER_ID_NOT_SET],
            ['orderId', 'checkOrderExists', 'message' => OrdersErrors::ORDER_NOT_FOUND]
        ]);

        return true;
    }

    /**
     * Проверка параметров добавления документа
     * @param $params
     * @return bool
     */
    public function checkAddDocumentParams($params)
    {
        $this->validateComplex($params,[
            ['orderId', 'required', 'message' => OrdersErrors::ORDER_ID_NOT_SET],
            ['orderId', 'checkOrderExists', 'message' => OrdersErrors::ORDER_NOT_FOUND],
            ['mimeType', 'required', 'message' => OrdersErrors::MIME_TYPE_NOT_SET],
            ['presentationFileName', 'required', 'message' => OrdersErrors::FILE_NAME_NOT_SET],
            ['fileSize', 'required', 'message' => OrdersErrors::FILE_SIZE_NOT_SET],
            ['url', 'required', 'message' => OrdersErrors::FILE_URL_NOT_SET],
            ['comment', 'included', 'message' => OrdersErrors::FILE_COMMENT_NOT_SET],
            ['objectType', 'required', 'message' => OrdersErrors::OBJECT_TYPE_NOT_SET],
            ['objectId', 'required', 'message' => OrdersErrors::OBJECT_ID_NOT_SET],
        ]);

        return true;
    }


    /**
     * Проверка существования указанной заявки
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkOrderExists($values, $attribute, $params) {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES,[]);
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($values[$attribute]);

        if (empty($orderInfo)) {
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
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError() {
        return $this->errorCode;
    }

}