<?php

/**
 * Используется для формирования ответа от GPTS при выписке билета
 * Class IssueTicketsResponse
 */
class IssueTicketsResponse extends Response
{

    /**
     * Массив объектов класса GPOrders
     * @var array
     */
    private $orders;

    /**
     * Данные о Passenger Name Record (PNR)
     * @var array
     */
    private $pnrData;

    /**
     * Необработанный массив данных заявки поставщика
     * @var array
     */
    private $ordersInfo;

    /**
     * status= 1 -ошибка переданная supplier'ом
     * @var int
     */
    protected $status;
    /**
     * errorCode - код ошибки переданный supplier'ом
     * @var string
     */
    protected $errorCode;
    /**
     * errorDescription - описание ошибки переданное supplier'ом
     * @var string
     */
    protected $errorDescription;

    /**
     * @param string $jsdata - ответ команды в json
     */
    public function __construct($ordersInfo)
    {
        $this->status = 0;
        $this->errorCode = '';
        $this->errorDescription = '';

        $this->ordersInfo = $ordersInfo;
        $this->orders = [];
        foreach ($ordersInfo as $orderInfo) {
            $this->orders[] = new GPOrders([$orderInfo]);
        }

        $pnrData = '';
    }

    /**
     * Получить форматированный ответ
     */
    public function getResponse()
    {
        $response = [];
        if ($this->status == 0) {
            foreach ($this->orders as $order) {

                $response[] = [
                    'tickets' => $this->getTicketData($order, $this->pnrData['pnrData']['PNR']),
                    'pnrData' => $this->pnrData['pnrData'],
                    'orderInfo' => $this->ordersInfo
                ];
            }
        }else{
            $response = [
                'status' => $this->status,
                'errorCode' =>$this->errorCode,
                'errorDescription' => $this->errorDescription
            ];
        }

        return $response;
    }

    /**
     * Задать параметр объекта pnrData
     * @param $pnrData array
     */
    public function setPnrData($pnrData)
    {
           $this->pnrData = $pnrData;
    }

    /**
     * Получить представление информации по билету
     * @param $order
     * @return array
     */
    private function getTicketData($order, $pnr)
    {
        $serviceId = $order->getServiceIdByPnr($pnr);

        if (empty($serviceId)) {
            throw new KmpException(
                get_class(),
                __FUNCTION__,
                SupplierErrors::NO_SERVICE_IN_ORDER_WITH_PNR_DATA,
                ['pnr' => $pnr]
            );
        }

        $tourists = $order->getServiceTourists($serviceId);
        $tickets = $order->getServiceTickets($serviceId);

        $ticketInfo=[];

        foreach ($tickets as $ticket) {
            foreach ($tourists as $tourist) {

                if ($tourist['travelerId'] != $ticket['travelerId']) {
                    continue;
                }

                $firstName= !empty($tourist['firstName'])
                  ? $tourist['firstName']
                  : hashtableval($tourist['name'][0]['firstName'],'');
                $middleName= !empty($tourist['middleName'])
                  ? $tourist['middleName']
                  : hashtableval($tourist['name'][0]['middleName'],'');
                $lastName= !empty($tourist['lastName'])
                  ? $tourist['lastName']
                  : hashtableval($tourist['name'][0]['lastName'],'');

                $ticketInfo[] = [
                    'engine' => GPTSSupplierEngine::ENGINE_ID,
                    'ticket' => [
                        'ticketNumber' => $ticket['ticketNumber'],
                        'traveler' => [
                            'isChild' => hashtableval($tourist['isChild'],false),
                            'citizenshipId' => ParamsTranslator::getKTCountry($tourist['citizenshipId']),
                            'maleFemale' => ParamsTranslator::getKtSex($tourist['prefix']),
                            'firstName' => $firstName,
                            'middleName' => $middleName,
                            'lastName' => $lastName,
                            'dateOfBirth' => ParamsTranslator::getKtDateOfBirth($tourist['dateOfBirth']),
                            'email' => hashtableval($tourist['email'],''),
                            'phone' => hashtableval($tourist['phone'],''),
                            'passport' => [
                                'number' => hashtableval($tourist['passports'][0]['number'],''),
                                'expiryDate' => hashtableval($tourist['passports'][0]['expiryDate'],'')
                            ]
                        ]
                    ]
                ];
            }
        }

        return $ticketInfo;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @param string $errorCode
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
    }

    /**
     * @param string $errorDescription
     */
    public function setErrorDescription($errorDescription)
    {
        $this->errorDescription = $errorDescription;
    }
}

?>
