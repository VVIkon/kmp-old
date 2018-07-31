<?php

/**
 * Class ServiceFlTicket
 * Класс для работы с данными о билете туриста для услуги авиаперелёта
 */
class ServiceFlTicket extends KFormModel
{

    const STATUS_ISSUED = 1;
    const STATUS_VOIDED = 2;
    const STATUS_RETURNED = 3;
    const STATUS_CHANGED = 4;

    /**
     * идентификатор Passenger Name Record (PNR)
     * @var string
     */
    public $pnr;

    /**
     * номер билета туриста
     * @var string
     */
    public $ticketNumber;

    /**
     * Ид привязанного документа печатной формы
     * @var string
     */
    public $attachedFormId;

    /**
     * идентификатор услуги содержащей предложение к которому привязан билет
     * @var string
     */
    public $serviceId;

    /**
     * идентификатор туриста на которого выписан билет
     * @var string
     */
    public $touristId;

    /**
     * статус билета
     * @var int
     */
    public $status;

    public $newTicket;

    /**
     * Конструктор объекта
     * @param array $values
     */
    public function __construct()
    {
    }

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules()
    {
        return [
            ['pnr, ticketNumber, attachedFormId, serviceId, touristId, status, newTicket', 'safe']
        ];
    }

    public function save()
    {

        $ticket = new self();
        if ($ticket->load($this->ticketNumber)) {
            $this->update();
        } else {
            $this->create();
        }

    }

    /**
     * Создание информации об объекте в БД
     * @return mixed
     */
    public function create()
    {

        $command = Yii::app()->db->createCommand();

        try {
            $res = $command->insert('kt_service_fl_ticket', [
                'ticketNumber' => $this->ticketNumber,
                'PNR' => $this->pnr,
                'documentID' => $this->attachedFormId,
                'ServiceID' => $this->serviceId,
                'TouristID' => $this->touristId,
                'status' => $this->status,
            ]);

        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(), __FUNCTION__,
                OrdersErrors::CANNOT_CREATE_TICKET,
                $command->getText(),
                $e
            );
        }

        return true;
    }

    /**
     * Обновление данных об объекте в БД
     */
    public function update()
    {

        $command = Yii::app()->db->createCommand();

        try {
            $res = $command->update('kt_service_fl_ticket', [
                'PNR' => $this->pnr,
                'documentID' => $this->attachedFormId,
                'ServiceID' => $this->serviceId,
                'TouristID' => $this->touristId,
                'status' => $this->status
            ], 'ticketNumber = :ticketNumber',
                [
                    ':ticketNumber' => $this->ticketNumber
                ]
            );
        } catch (Exception $e) {

            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                OrdersErrors::CANNOT_UPDATE_TICKET,
                $command->getText(),
                $e
            );

        }
    }

    /**
     * Инициализация объекта по данным из БД
     * @param $ticket string номер билета
     * @return CDbDataReader|mixed
     */
    public function load($ticket)
    {

        $command = Yii::app()->db->createCommand();

        $command->select('ticketNumber ticketNumber, PNR pnr, documentID attachedFormId,
							ServiceID serviceId, TouristID touristId, status status');
        $command->from('kt_service_fl_ticket');
        $command->where('ticketNumber = :ticketNumber',
            [':ticketNumber' => $ticket]
        );

        try {
            $ticketInfo = $command->queryRow();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                OrdersErrors::CANNOT_GET_TICKET,
                $command->getText(),
                $e
            );
        }

        if (empty($ticketInfo)) {
            return false;
        }

        $this->setAttributes($ticketInfo);

        return true;
    }

    /**
     * Получить выписанные билеты по указанной услуге
     * @param $serviceId
     */
    public static function getTicketsByServiceId($serviceId)
    {
        $command = Yii::app()->db->createCommand();

        $command->select('ticketNumber ticketNumber, PNR pnr, documentID attachedFormId,
							ServiceID serviceId, TouristID touristId, status status, newTicket newTicket');
        $command->from('kt_service_fl_ticket');
        $command->where('ServiceID = :serviceId', [':serviceId' => $serviceId]);

        try {
            $ticketsInfo = $command->queryAll();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                OrdersErrors::CANNOT_GET_TICKET,
                $command->getText(),
                $e
            );
        }
        $tickets = [];
        foreach ($ticketsInfo as $ticketInfo) {
            $ticket = new ServiceFlTicket();

            if (!empty($ticketInfo)) {
                $ticket->setAttributes($ticketInfo);
            }
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    /**
     * Получить все билеты с указанным ид маршрутной квитанции
     * @param $receipt
     * @return array
     */
    public static function getTicketsByReceiptId($receiptId)
    {
        $command = Yii::app()->db->createCommand();

        $command->select('ticketNumber ticketNumber, PNR pnr, documentID attachedFormId,
							ServiceID serviceId, TouristID touristId, status status');
        $command->from('kt_service_fl_ticket');
        $command->where('documentID = :documentId', [':documentId' => $receiptId]);

        try {
            $ticketsInfo = $command->queryAll();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                OrdersErrors::CANNOT_GET_TICKET,
                $command->getText(),
                $e
            );
        }

        $tickets = [];
        foreach ($ticketsInfo as $ticketInfo) {
            $ticket = new ServiceFlTicket();

            if (!empty($ticketInfo)) {
                $ticket->setAttributes($ticketInfo);
            }
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    /**
     * Получить номера билетов по идентифкатору маршрутной квитанции
     */
    public static function getReceiptTicketNumbers($receiptId)
    {
        if (!$receiptId) {
            return false;
        }

        $tickets = self::getTicketsByReceiptId($receiptId);
        $ticketNumbers = [];
        foreach ($tickets as $ticket) {
            $ticketNumbers[] = $ticket->ticketNumber;
        }
        return $ticketNumbers;
    }

    /**
     * Получение свойств объекта в виде массива
     * @return array
     */
    public function getData()
    {
        return [
            'pnr' => $this->pnr,
            'ticketNumber' => $this->ticketNumber,
            'attachedFormId' => $this->attachedFormId,
            'serviceId' => $this->serviceId,
            'touristId' => $this->touristId,
            'status' => $this->status,
            'newTicket' => $this->newTicket
		];
    }
}
