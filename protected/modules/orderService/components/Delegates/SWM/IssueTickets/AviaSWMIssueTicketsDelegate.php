<?php

/**
 *  Выписать билеты функцией IssueTickets Сервиса поставщиков. Выписываются все билеты по услуге.
 */
class AviaSWMIssueTicketsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        // заворачиваем весь код в авиа
        if ($OrdersServices->getServiceType() == 2) {
            // пошел старый код
            $issueTicketsParams['usertoken'] = $params['usertoken'];
            $issueTicketsParams['serviceType'] = 2;

            // загрузим оффер
            $offer = new FlightOffer();
            $offer->load($OrdersServices->getOffer()->getOfferId());

            // загрузим pnr
            $pnr = new ServiceFlPnr();
            $pnr->loadByOfferId($offer->offerId);

            // делаем запрос IssueTickets
            $issueTicketsParams['bookData']['pnrData'] = [
                'engine' => [
                    'type' => $pnr->gateId,
                    'GPTS_service_ref' => $pnr->serviceRef,
                    'GPTS_order_ref' => $pnr->orderRef
                ],
                'supplierCode' => $pnr->supplierCode,
                'PNR' => $pnr->pnr
            ];

//            var_dump(json_encode($issueTicketsParams));
//            exit;

            $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
            $response = json_decode($apiClient->makeRestRequest('supplierService', 'IssueTickets', $issueTicketsParams), true);

//            var_dump($response);
//            exit;

            // если ошибка запроса
            if (RestException::isArrayRestException($response) && $response['errorCode'] == 814) {
                $params['comment'] = StdLib::nvl($response['body']['errorDescription']);
                $this->addResponse('comment', StdLib::nvl($response['body']['errorDescription']));
                return null;
            } elseif (RestException::isArrayRestException($response)) {
                $this->setError(OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR);
                $this->addLog('IssueTickets не прошел', 'error', $response);
                return null;
            } else {
                $response = $response['body'][0];
            }

            $touristsInfo = TouristForm::getServiceTourists($params['serviceId']);
            $tourists = [];
            if ($touristsInfo) {
                foreach ($touristsInfo as $touristInfo) {
                    $tourist = new TouristForm('system.orderservice');
                    $tourist->loadTouristByID($touristInfo['TouristID']);
                    $tourists[] = $tourist;
                }
            } else {
                $this->setError(OrdersErrors::INCORRECT_TICKETS_DATA);
                return null;
            }

            if (empty($response['tickets'])) {
                $this->setError(OrdersErrors::INCORRECT_TICKETS_DATA);
                $this->addLog('Нет билетов', 'error');
                return null;
            }

            foreach ($response['tickets'] as $ticketInfo) {
                if (empty($ticketInfo) || empty($ticketInfo['ticket'])) {
                    $this->setError(OrdersErrors::INCORRECT_TICKETS_DATA);
                    $this->addLog('Некорректные данные билетов', 'error', $ticketInfo);
                    return null;
                }
                $ticket = $ticketInfo['ticket'];

                $traveler = [
                    'maleFemale' => isset($ticket['traveler']['maleFemale']) ? $ticket['traveler']['maleFemale'] : '',
                    'dateOfBirth' => isset($ticket['traveler']['dateOfBirth'])
                        ? preg_replace('/\s\d\d:\d\d/', '', $ticket['traveler']['dateOfBirth'])
                        : '',
                    'passportNumber' => isset($ticket['traveler']['passport']['number'])
                        ? $ticket['traveler']['passport']['number']
                        : '',
                    'lastName' => isset($ticket['traveler']['lastName']) ? $ticket['traveler']['lastName'] : ''
                ];

                $touristId = false;
                foreach ($tourists as $tourist) {
                    $passportNum = $tourist->touristDoc->docSerial . $tourist->touristDoc->docNumber;

                    if (intval($tourist->touristBase->sex) == intval($traveler['maleFemale']) &&
                        strcmp(trim($tourist->touristBase->birthDate), trim($traveler['dateOfBirth'])) == 0 &&
                        strcmp(trim($passportNum), trim($traveler['passportNumber'])) == 0 &&
                        strcmp(trim($tourist->touristDoc->surname), trim($traveler['lastName']) == 0)
                    ) {
                        $touristId = $tourist->touristId;
                    } else {
                        continue;
                    }
                }

                if ($touristId != false) {
                    $flTicket = new ServiceFlTicket();
                    $flTicket->setAttributes([
                            'pnr' => $response['pnrData']['PNR'],
                            'ticketNumber' => $ticket['ticketNumber'],
                            'attachedFormId' => '',
                            'serviceId' => $params['serviceId'],
                            'touristId' => $touristId,
                            'status' => ServiceFlTicket::STATUS_ISSUED
                        ]
                    );

                    $flTicket->save();
                }
            }
        }
    }
}