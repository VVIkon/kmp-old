<?php

/**
 * Class GetEticketsDelegate
 * Делегат для установки статуса услуги заявки
 */
class GetEticketsDelegate extends WorkflowDelegate
{
    /**
     * Запуск метода выполнения действия делегата
     * @param $params
     * @return mixed
     */
    public function run($params, $module) {

        $getETicketsParams['usertoken'] = $params['usertoken'];

        $ticketsInfo = ServiceFlTicket::getTicketsByServiceId($params['serviceId']);

        foreach ($ticketsInfo as $ticketInfo) {

            $pnr = new ServiceFlPnr();
            $pnr->load($ticketInfo['pnr']);

            $getETicketsParams['tickets'][] = [
                'ticket' => [
                    'number' => $ticketInfo['ticketNumber'],
                    'pnrData' =>  [
                        'engine' => [
                            'type' => $pnr->gateId,
                            'GPTS_service_ref' => $pnr->serviceRef,
                            'GPTS_order_ref' =>  $pnr->orderRef
                        ],
                        'supplierCode' => $pnr->supplierCode,
                        'PNR' => $pnr->pnr
                    ],
                ]
            ];
        }

        $getETicketsParams['serviceType'] = 2;
        $apiClient = new ApiClient($module);
        $response = json_decode($apiClient->makeRestRequest('supplierService', 'GetEtickets', $getETicketsParams), true);

        if (RestException::isArrayRestException($response)) {

            $error = SupplierServiceHelper::translateSupplierSvcErrorId($response['errorCode']);
            return $error;

        } else {
            $eTicketsInfo = $response['body'];
        }

        $receiptName = $this->getReceiptName($module);

        foreach ($eTicketsInfo as $eTicketInfo) {

            $actionParams = $this->makeUploadFileParams(
                $receiptName,
                $params['serviceId'],
                $eTicketInfo['eticketData']['ticket']['number'],
                $eTicketInfo['receiptUrl']['downloadLink'],
                $params['usertoken']
            );

            $apiClient = new ApiClient($module);
            $response = json_decode($apiClient->makeRestRequest('systemService', 'UploadFile', $actionParams), true);

            if (RestException::isArrayRestException($response)) {

                $error = SupplierServiceHelper::translateSupplierSvcErrorId($response['errorCode']);
                return $error;

            } else {
                $documentId = $response['body']['documentId'];
            }

            $flTicket = new ServiceFlTicket();

            if (!$flTicket->load($eTicketInfo['eticketData']['ticket']['number'])) {
                throw new KmpException(
                    get_class(), __FUNCTION__,
                    OrdersErrors::CANNOT_GET_TICKET,
                    ['ticketId' => $eTicketInfo['eticketData']['ticket']['number']]
                );
            } else {
                $flTicket->attachedFormId = $documentId;
                $flTicket->save();
            }
        }

        return true;
    }

    /**
     * Получение наименования сущности квитанции
     */
    private function getReceiptName($module)
    {
        $entityName = EntitiesHelper::getEntityName($module, BusinessEntityTypes::BUSINESS_ENTITY_RECEIPT);

        if (empty($entityName)) {
            throw new KmpException(
                get_class(),__FUNCTION__,
                OrdersErrors::INCORRECT_FILE_PRESENTATION_NAMES_SETTINGS,
                [
                    'configSection' => 'attachedFilesNames',
                    'sectionParam' => 'receipt'
                ]
            );
        }

        return $entityName;
    }

    /**
     * Формирование параметров запроса для загрузки файла
     * @param $receiptName Наименование квитанции
     * @param $serviceId идентифкатор услуги выписки билетов
     * @param $ticketId номер билета
     * @param $url адрес расположения файла
     * @param $usertoken ключ пользовательской сессии
     * @return array
     */
    private function makeUploadFileParams($receiptName, $serviceId, $ticketId, $url, $usertoken) {

        $service = new Service();
        $service->load($serviceId);

        $orderInfo = OrderForm::getOrderByServiceId($serviceId);

        if (empty($orderInfo)) {
            throw new KmpException(
                get_class(),__FUNCTION__,
                OrdersErrors::ORDER_NOT_FOUND,
                [
                    'serviceId' => $serviceId
                ]
            );
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

