<?php

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * Модель PNR
 * @property $PNR
 * @property $supplierCode
 * @property $offerKey
 * @property $gateId
 * @property $service_ref
 * @property $order_ref
 * @property $status
 * @property $offerID
 *
 * @property AviaTicket [] $AviaTickets
 */
class AviaOfferPNR extends CActiveRecord
{
    public $PNR;
    /**
     * @var OrdersServices
     */
    protected $OrdersServices;

    public function tableName()
    {
        return 'kt_service_fl_pnr';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'AviaTickets' => array(self::HAS_MANY, 'AviaTicket', ['PNR' => 'PNR']),
        );
    }

    /**
     * Получение engineData
     * @return EngineData
     */
    public function getEngineData()
    {
        $EngineData = new EngineData();
        $EngineData->reservationId = $this->PNR;
        $EngineData->gateId = $this->gateId;
        $EngineData->offerId = $this->offerID;
        $EngineData->offerKey = $this->offerKey;
        $EngineData->data = [
            'GPTS_service_ref' => $this->service_ref,
            'GPTS_order_ref' => $this->order_ref
        ];

        return $EngineData;
    }

    /**
     * @return mixed
     */
    public function getPNR()
    {
        return $this->PNR;
    }


    public function setPNR($pnr)
    {
        $this->PNR = $pnr;
    }

    public function enable()
    {
        $this->status = 1;
    }

    public function disable()
    {
        $this->status = 2;
    }

    /**
     * @return mixed
     */
    public function getSupplierCode()
    {
        return $this->supplierCode;
    }

    /**
     * @param mixed $supplierCode
     */
    public function setSupplierCode($supplierCode)
    {
        $this->supplierCode = $supplierCode;
    }

    /**
     * @return mixed
     */
    public function getOfferID()
    {
        return $this->offerID;
    }

    /**
     * @param mixed $offerID
     */
    public function setOfferID($offerID)
    {
        $this->offerID = $offerID;
    }

    /**
     *
     * @return AviaTicket []|null
     */
    public function getAviaTickets()
    {
        return $this->AviaTickets;
    }

    /**
     * @return bool
     */
    public function hasIssuedTickets()
    {
        $tickets = $this->getAviaTickets();

        if(empty($tickets)){
            return false;
        }

        foreach ($tickets as $ticket) {
            if($ticket->isIssued()){
                return true;
            }
        }

        return false;
    }

    /**
     * Устновка сервиса
     * @param OrdersServices $OrdersServices
     */
    public function setService(OrdersServices $OrdersServices)
    {
        $this->OrdersServices = $OrdersServices;
    }

    /**
     * Добавление нового билета в бронь
     * @param array $ssAviaTicket
     */
    public function addTicket(array $ssAviaTicket)
    {
        // найдем всех туристов заявки
        $ServiceTourists = $this->OrdersServices->getServiceTourists();

        // выберем чтобы проверить правильного ли нам туриста дали
        $serviceTouristsId = [];
        if (count($ServiceTourists)) {
            foreach ($ServiceTourists as $ServiceTourist) {
                $serviceTouristsId[] = $ServiceTourist->getTouristID();
            }
        } else {
            throw new LogicException("В забронированной услуге не найдены туристы");
        }

        // проверяем ID туриста
        if (!in_array($ssAviaTicket['touristId'], $serviceTouristsId)) {
            throw new InvalidArgumentException("Турист с ID {$ssAviaTicket['touristId']} не найден в услуге", OrdersErrors::INCORRECT_TOURIST_ID);
        }

        $AviaTicket = AviaTicketRepository::getAllByTicketNumber($ssAviaTicket['ticketNumber']);

        if (count($AviaTicket)) {
            throw new InvalidArgumentException("Билет с таким номером уже существует", OrdersErrors::TICKET_NUMBER_ALREADY_EXISTS);
        }

        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();

        // создаем новый билетик
        $AviaTicket = new AviaTicket();
        $AviaTicket->setPNR($this->PNR);
        $AviaTicket->setTicketNumber($ssAviaTicket['ticketNumber']);
        $AviaTicket->setServiceID($this->OrdersServices->getServiceID());
        $AviaTicket->setTouristID($ssAviaTicket['touristId']);

        if (!$AviaTicket->setStatus($ssAviaTicket['ticketStatus'])) {
            throw new InvalidArgumentException("Некорректный статус билета {$ssAviaTicket['ticketStatus']}", OrdersErrors::INVALID_TICKET_STATUS);
        }

        $violations = $validator->validate($AviaTicket);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                throw new InvalidArgumentException("Ошибка валидации при создании авиабилета", $violation->getMessage());
            }
        }

        if (!$AviaTicket->save(false)) {
            throw new DomainException('Не удалось сохранить Авиабилет');
        }
    }

    /**
     * Обновление существующего билета в брони
     * @param array $ssAviaTicket
     * @param $oldTicketNumber
     */
    public function updateTicket($oldTicketNumber, array $ssAviaTicket)
    {
        // выберем все существующие билеты
        $AviaTickets = $this->getAviaTickets();

        if (empty($AviaTickets)) {
            throw new LogicException('В броне нет билетов');
        }

        // проверим, что такого билета нет
        $AviaTicket = AviaTicketRepository::getAllByTicketNumber($ssAviaTicket['ticketNumber']);

        if (!empty($AviaTicket)) {
            throw new InvalidArgumentException("Билет с номером {$ssAviaTicket['ticketNumber']} уже существует", OrdersErrors::TICKET_NUMBER_ALREADY_EXISTS);
        }

        unset($AviaTicket);

        // создадим новый билет
        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();

        $NewAviaTicket = new AviaTicket();
        $NewAviaTicket->setPNR($this->PNR);
        $NewAviaTicket->setTicketNumber($ssAviaTicket['ticketNumber']);
        $NewAviaTicket->setServiceID($this->OrdersServices->getServiceID());
        $NewAviaTicket->setTouristID($ssAviaTicket['touristId']);

        if (!$NewAviaTicket->setStatus($ssAviaTicket['ticketStatus'])) {
            throw new InvalidArgumentException("Некорректный статус билета {$ssAviaTicket['ticketStatus']}", OrdersErrors::INVALID_TICKET_STATUS);
        }

        // валидируем данные нового билета
        $violations = $validator->validate($NewAviaTicket);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                throw new InvalidArgumentException("Ошибка валидации при создании авиабилета", $violation->getMessage());
            }
        }

        // выберем старый билет и запишем туда спец статус
        foreach ($AviaTickets as $AviaTicket) {
            if ($oldTicketNumber == $AviaTicket->getTicketNumber()) {
                if ($AviaTicket->isIssued()) {

                    // В старый документ билета вписываем [ОБМЕНЯН]
                    $documentId=$AviaTicket->getDocumentId();
                    if (isset($documentId) && $documentId >0){
                        $orderDocument=OrderDocumentRepository::getOrderDocumentByDocumentId($documentId);
                        if (isset($orderDocument)) {
                            $orderDocument->setFileName("[ОБМЕНЯН] " . $orderDocument->getFileName());     //Если к билету привязан документ, то вставляем префикс "[ОБМЕНЯН]" в название файла
                            if (!$orderDocument->save()) {
                                throw new DomainException('Не удалось сохранить Документ авиабилет');
                            }
                        }
                    }
                    $AviaTicket->changeByTicketNumber($ssAviaTicket['ticketNumber']);

                    if (!$AviaTicket->save(false)) {
                        throw new DomainException('Не удалось сохранить Авиабилет');
                    }

                    if (!$NewAviaTicket->save(false)) {
                        throw new DomainException('Не удалось сохранить Авиабилет');
                    }

                    return;
                } else {
                    throw new InvalidArgumentException('Можно заменить только выписанный билет', OrdersErrors::ONLY_ISSUED_TICKET_CAN_BE_CHANGED);
                }

                break;
            }
        }

        throw new InvalidArgumentException("Не найден билет с номером $oldTicketNumber при замене билета", OrdersErrors::INCORRECT_OLD_TICKET_NUMBER);
    }

    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('PNR', new Assert\Regex(array('pattern' => '/^[0-9,A-Z,a-z,\-]{5,50}$/', 'message' => OrdersErrors::INCORRECT_PNR_NUMBER)));
    }
}