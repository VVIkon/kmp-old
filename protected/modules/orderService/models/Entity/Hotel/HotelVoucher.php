<?php

/**
 * Модель отельного ваучера
 * @property $voucherId
 * @property $reservationId
// * @property $serviceId
 * @property $documentId
 * @property $receiptUrl
 */
class HotelVoucher extends CActiveRecord
{

    const STATUS_ISSUED   = 1;
    const STATUS_VOIDED   = 2;
    const STATUS_RETURNED = 3;
    const STATUS_CHANGED  = 4;


    public function tableName()
    {
        return 'kt_service_ho_hotelVoucher';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return mixed
     */
    public function getVoucherId()
    {
        return $this->voucherId;
    }

    /**
     * @return mixed
     */
    public function getReservationId()
    {
        return $this->reservationId;
    }

    /**
     * @param mixed $reservationId
     */
    public function setReservationId($reservationId)
    {
        $this->reservationId = $reservationId;
    }

    /**
     * @param mixed $status
     */
    public function setVoucherStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @param mixed $status
     * статус ваучера 1- ISSUED; 2-VOIDED; 3-RETURNED; 4-CHANGED
     */
    public function getVoucherStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * @param mixed $documentId
     */
    public function setDocumentId($documentId)
    {
        $OrderDocument = OrderDocumentRepository::getOrderDocumentByDocumentId($this->documentId);

        if ($OrderDocument) {
            $this->receiptUrl = $OrderDocument->getFileURL();
        }

        $this->documentId = $documentId;
    }

    /**
     * @return mixed
     */
    public function getReceiptUrl()
    {
        return $this->receiptUrl;
    }

    /**
     * @param mixed $receiptUrl
     */
    public function setReceiptUrl($receiptUrl)
    {
        $this->receiptUrl = $receiptUrl;
    }

    public function toArray()
    {
        return [
            'voucherId' => $this->voucherId,             // Идентификатор ваучера
            'reservationId' => $this->reservationId,     // Идентификатор брони
            'serviceId' => $this->serviceId,             // Идентификатор услуги
            'documentId' => $this->documentId,           // Идентификатор документа заявки, хранящего ваучер
            'receiptUrl' => $this->getReceiptUrl(),      //kmp.travel/files/dvPRCZGYCdYc6hJO // ссылка на файл ваучера, денормализ
            'status' => $this->getVoucherStatus()        // статус ваучера 1- ISSUED; 2-VOIDED; 3-RETURNED; 4-CHANGED
        ];
    }
}