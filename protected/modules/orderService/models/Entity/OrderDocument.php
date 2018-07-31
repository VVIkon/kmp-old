<?php

/**
 * Модель документа в заявке
 * @property $documentID
 * @property $orderID
 * @property $documentSource
 * @property $mimtype
 * @property $fileName
 * @property $fileSize
 * @property $fileURL
 * @property $fileComment
 * @property $objectType
 * @property $objectId
 */
class OrderDocument extends CActiveRecord
{
    const ORDER_FILE = 1;
    const INVOICE_FILE = 2;
    const PAYMENT_FILE = 3;
    const TICKET_FILE = 4;
    const RECEIPT_FILE = 5;

    public function tableName()
    {
        return 'kt_orders_doc';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return mixed
     */
    public function getDocumentID()
    {
        return $this->documentID;
    }

    /**
     * @return mixed
     */
    public function getFileURL()
    {
        return $this->fileURL;
    }

    public function setOrderId($orderId)
    {
        $this->orderID = $orderId;
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

//    public function relations()
//    {
//        return array(
//            'OrdersServices' => array(self::HAS_MANY, 'OrdersServices', 'OrderID'),
//            'OrderTourists' => array(self::HAS_MANY, 'OrderTourist', 'OrderID'),
//        );
//    }

    /**
     *
     * @param $fileURL
     * @param $fileName
     * @throws OrderDocumentException
     * @return bool
     */
    public function setFile($fileName, $fileURL)
    {
        $this->fileName = $fileName;

//        set_error_handler(function ($errno, $errstr) {
//            restore_error_handler();
//            throw new OrderDocumentException("$errno - $errstr");
//        }, E_WARNING);

//        var_dump($fileURL);
//        exit;

//        $arrContextOptions = array(
//            "ssl" => array(
//                "verify_peer" => false,
//                "verify_peer_name" => false,
//            ),
//        );

//        if (!$fileURL || false === fopen($fileURL, 'r', false, stream_context_create($arrContextOptions))) {
//            throw new OrderDocumentException("File '{$fileURL}' not found");
//        }

//        $this->mimtype = mime_content_type($fileURL);
//        $this->fileSize = filesize($fileURL);
        $this->fileURL = $fileURL;
    }

    /**
     * Установка объекта
     * @param $id
     * @param $type
     */
    public function setObject($id, $type = self::ORDER_FILE)
    {
        $this->objectId = $id;
        $this->objectType = $type;
    }

    public function save($runValidation = true, $attributes = null)
    {
        return parent::save(false);
    }
}