<?php

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Модель авиабилета
 *
 * @property $PNR
 * @property $documentID
 * @property $ServiceID
 * @property $TouristID
 * @property $status
 * @property $newTicket
 *
 * @property OrderTourist $orderTourist
 */
class AviaTicket extends CActiveRecord
{
    protected $ticketNumber;
    protected $newTicket;

    const STATUS_ISSUED = 1;
    const STATUS_VOIDED = 2;
    const STATUS_RETURNED = 3;
    const STATUS_CHANGED = 4;

    protected $statuses = [
        1 => AviaTicket::STATUS_ISSUED,
        2 => AviaTicket::STATUS_VOIDED,
        3 => AviaTicket::STATUS_RETURNED,
        4 => AviaTicket::STATUS_CHANGED,
    ];

    public function tableName()
    {
        return 'kt_service_fl_ticket';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'orderTourist' => array(self::BELONGS_TO, 'OrderTourist', 'TouristID'),
        );
    }

    /**
     * @return OrderTourist
     */
    public function getOrderTourist()
    {
        return $this->orderTourist;
    }

    /**
     * @param mixed $PNR
     */
    public function setPNR($PNR)
    {
        $this->PNR = $PNR;
    }

    /**
     * @param mixed $ServiceID
     */
    public function setServiceID($ServiceID)
    {
        $this->ServiceID = $ServiceID;
    }

    /**
     * @param mixed $TouristID
     */
    public function setTouristID($TouristID)
    {
        $this->TouristID = $TouristID;
    }

    /**
     * @param mixed $status
     * @return bool
     */
    public function setStatus($status)
    {
        if (in_array($status, $this->statuses)) {
            $this->status = $status;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param mixed $ticketNumber
     */
    public function setTicketNumber($ticketNumber)
    {
        $this->ticketNumber = $ticketNumber;
    }

    /**
     * Меняем на другой билет
     * @param $ticketNumber
     */
    public function changeByTicketNumber($ticketNumber)
    {
        $this->status = self::STATUS_CHANGED;
        $this->newTicket = $ticketNumber;
    }

    /**
     * @return mixed
     */
    public function getTicketNumber()
    {
        return $this->ticketNumber;
    }

    /**
     * @return mixed
     */
    public function getDocumentId()
    {
        return $this->documentID;
    }

    /**
     * @return mixed
     */
    public function isIssued()
    {
        return $this->status == AviaTicket::STATUS_ISSUED;
    }

    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('ticketNumber', new Assert\Regex(array('pattern' => '/^[0-9,A-Z,a-z,\-]{5,50}$/', 'message' => OrdersErrors::INVALID_TICKET_NUMBER)));
        $metadata->addPropertyConstraint('newTicket', new Assert\Regex(array('pattern' => '/^[0-9,A-Z,a-z,\-]{5,50}$/', 'message' => OrdersErrors::INVALID_TICKET_NUMBER)));
    }
}