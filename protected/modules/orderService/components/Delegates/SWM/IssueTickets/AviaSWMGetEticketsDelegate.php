<?php

/**
 * создать маршрутные квитанции для услугиАвиа
 */
class AviaSWMGetEticketsDelegate extends AbstractDelegate
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
            $module = Yii::app()->getModule('orderService');

            $getETicketsParams['usertoken'] = $params['usertoken'];

            $ticketsInfo = ServiceFlTicket::getTicketsByServiceId($OrdersServices->getServiceID());

            if (count($ticketsInfo)) {
                foreach ($ticketsInfo as $ticketInfo) {
                    $pnr = new ServiceFlPnr();
                    $pnr->load($ticketInfo['pnr']);

                    $getETicketsParams['tickets'][] = [
                        'ticket' => [
                            'number' => $ticketInfo['ticketNumber'],
                            'pnrData' => [
                                'engine' => [
                                    'type' => $pnr->gateId,
                                    'GPTS_service_ref' => $pnr->serviceRef,
                                    'GPTS_order_ref' => $pnr->orderRef
                                ],
                                'supplierCode' => $pnr->supplierCode,
                                'PNR' => $pnr->pnr
                            ],
                        ]
                    ];
                }
            } else {
                $this->setError(OrdersErrors::CANNOT_GET_TICKET);
                return null;
            }

            $getETicketsParams['serviceType'] = 2;

            $apiClient = new ApiClient($module);
            $response = json_decode($apiClient->makeRestRequest('supplierService', 'GetEtickets', $getETicketsParams), true);

//            var_dump($response);
//            exit;

            if (RestException::isArrayRestException($response)) {
                $this->setError(SupplierServiceHelper::translateSupplierSvcErrorId($response['errorCode']));
                return null;
            } else {
                $eTicketsInfo = $response['body'];
            }

            $receiptName = EntitiesHelper::getEntityName($module, BusinessEntityTypes::BUSINESS_ENTITY_RECEIPT);

            if (empty($receiptName)) {
                $this->setError(OrdersErrors::CANNOT_GET_TICKET);
                return null;
            }

            foreach ($eTicketsInfo as $eTicketInfo) {
                $actionParams = $this->makeUploadFileParams(
                    $receiptName,
                    $params['serviceId'],
                    $eTicketInfo['eticketData']['ticket']['number'],
                    $eTicketInfo['receiptUrl']['downloadLink'],
                    $params['usertoken']
                );

                if (empty($actionParams)) {
                    $this->setError(OrdersErrors::ORDER_NOT_FOUND);
                    return null;
                }

//                var_dump(json_encode($actionParams));
//                exit;

//                var_dump($eTicketInfo['receiptUrl']['downloadLink']);
//                exit;

                $apiClient = new ApiClient($module);
                $response = json_decode($apiClient->makeRestRequest('systemService', 'UploadFile', $actionParams), true);

//                var_dump($response);
//                exit;

                if (RestException::isArrayRestException($response)) {
                    $this->setError(OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR);
                    return null;
                } else {
                    $documentId = $response['body']['documentId'];
                }

                $flTicket = new ServiceFlTicket();

                if (!$flTicket->load($eTicketInfo['eticketData']['ticket']['number'])) {
                    $this->setError(OrdersErrors::CANNOT_GET_TICKET);
                    return null;
                } else {
                    $flTicket->attachedFormId = $documentId;
                    $flTicket->save();
                }
            }
        }
    }

    /**
     * Формирование параметров запроса для загрузки файла
     * @param $receiptName string Наименование квитанции
     * @param $serviceId int идентифкатор услуги выписки билетов
     * @param $ticketId string номер билета
     * @param $url string адрес расположения файла
     * @param $usertoken string ключ пользовательской сессии
     * @return array
     */
    private function makeUploadFileParams($receiptName, $serviceId, $ticketId, $url, $usertoken)
    {
        $service = new Service();
        $service->load($serviceId);

        $orderInfo = OrderForm::getOrderByServiceId($serviceId);

        if (empty($orderInfo)) {
            return [];
        }

        $actionParams = [
            "presentationFileName" => $receiptName . ' ' . $service->serviceName,
            "comment" => '',
            'orderId' => $orderInfo['OrderID'],
            "objectType" => BusinessEntityTypes::BUSINESS_ENTITY_TICKET,
            "objectId" => $ticketId,
            "url" => $url,
            "usertoken" => $usertoken
        ];

        return $actionParams;
    }
}