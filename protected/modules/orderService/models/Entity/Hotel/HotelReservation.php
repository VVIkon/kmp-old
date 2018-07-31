<?php

/**
 * Модель отельной брони
 * @property $status
 *
 * @property HotelEngineData[] $HotelEngineDatas
 */
class HotelReservation extends CActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_DISABLED = 2;

    public $reservationId;
    protected $offerId;
    protected $reservationNumber;
    protected $status;

    public function tableName()
    {
        return 'kt_service_ho_hotelReservation';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'HotelVouchers' => array(self::HAS_MANY, 'HotelVoucher', 'reservationId')
            , 'ReservationTourists' => array(self::HAS_MANY, 'HotelReservationTourist', 'reservationId')
            , 'HotelEngineDatas' => array(self::HAS_MANY, 'HotelEngineData', 'reservationId')
        );
    }

    /**
     * @param mixed $offerId
     */
    public function setOfferId($offerId)
    {
        $this->offerId = $offerId;
    }

    /**
     * @param mixed $reservationNumber
     */
    public function setReservationNumber($reservationNumber)
    {
        $this->reservationNumber = $reservationNumber;
    }

    public function getReservationNumber()
    {
        return $this->reservationNumber;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function isActive()
    {
        return $this->status == HotelReservation::STATUS_ACTIVE;
    }

    public function hasHotelVoucher()
    {
        return (bool) $this->HotelVouchers;
    }

    public function getHotelVouchers()
    {
        return $this->HotelVouchers;
    }

    /**
     * @return mixed
     */
    public function getReservationId()
    {
        return $this->reservationId;
    }

    /**
     * @return HotelEngineData []
     */
    public function getHotelEngineDatas()
    {
        return $this->HotelEngineDatas;
    }

    /**
     * Добавление документа
     * @param OrderDocument $OrderDocument
     * @return bool
     */
    public function addVoucher(OrderDocument $OrderDocument)
    {
        $HotelVoucher = new HotelVoucher();
        $HotelVoucher->setReservationId($this->getReservationId());
        $HotelVoucher->setDocumentId($OrderDocument->getDocumentID());
        $HotelVoucher->setReceiptUrl($OrderDocument->getFileURL());
        $HotelVoucher->setVoucherStatus($HotelVoucher::STATUS_ISSUED);

        return $HotelVoucher->save(false);
    }

    public function toArray()
    {
        $hotelVouchers = null;
        $engineDataArray = null;
        $tourists = null;

        if ($this->HotelVouchers) {
            foreach ($this->HotelVouchers as $HotelVoucher) {
                $hotelVouchers[] = $HotelVoucher->toArray();
            }
        }

        if ($this->ReservationTourists) {
            foreach ($this->ReservationTourists as $ReservationTourist) {
                $tourists[] = $ReservationTourist->getTouristId();
            }
        }

        if ($this->HotelEngineDatas) {
            foreach ($this->HotelEngineDatas as $HotelEngineData) {
                $engineDataArray[] = $HotelEngineData->toArray();
            }
        }

        return [
            'reservationId' => $this->reservationId,                    // Идентификатор брони
            'tourists' => $tourists,                                    // Массив идентификаторов туристов, для которых создана бронь
            'reservationNumber' => $this->reservationNumber,            // Номер брони от поставщика
            'supplierCode' => $this->supplierCode,                      // идентификатор поставщика
            'status' => $this->status,                                  // статус брони, 1 = Действует, 2 = Отменена
            'cancelAbility' => $this->cancelAbility,                    // возможность отменить бронь
            'modifyAbility' => $this->modifyAbility,                    // возможность изменить данные брони
            'hotelVouchers' => $hotelVouchers,
            'engineData' => $engineDataArray
        ];
    }
}