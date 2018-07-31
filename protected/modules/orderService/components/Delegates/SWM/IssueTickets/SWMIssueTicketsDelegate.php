<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/10/16
 * Time: 1:33 PM
 */
class SWMIssueTicketsDelegate extends AbstractDelegate
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

        // заворачиваем весь код НЕ для АВИА
        if ($OrdersServices->getServiceType() != 2) {
            $engineData = $OrdersServices->getEngineData();

            if (empty($engineData)) {
                $this->setError(OrdersErrors::BOOK_DATA_NOT_FOUND);
                return null;
            }

            // создадим параметры для вызова
            $getEticketParams = [
                'usertoken' => $params['usertoken'],
                'tickets' => [
                    $engineData
                ],
                'serviceType' => $OrdersServices->getServiceType()
            ];

//            var_dump($getEticketParams);
//            exit;

            $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
            $response = json_decode($apiClient->makeRestRequest('supplierService', 'GetEtickets', $getEticketParams), true);

            if (isset($response['status']) && $response['status']) {
                $this->setError(OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR);

                $OrdersServicesHistory = new OrdersServicesHistory();
                $OrdersServicesHistory->setObjectData($OrdersServices);
                $OrdersServicesHistory->setOrderData($OrderModel);
                $OrdersServicesHistory->setCommentTpl("{{144}}");
                $OrdersServicesHistory->setCommentParams([]);
                $OrdersServicesHistory->setActionResult(1);

                $this->addOrderAudit($OrdersServicesHistory);
                return null;
            }

            if (isset($response['body'][0]['receiptUrl']['downloadLink'])) {
                $voucherLink = $response['body'][0]['receiptUrl']['downloadLink'];
            } else {
                $this->setError(OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR);

                $OrdersServicesHistory = new OrdersServicesHistory();
                $OrdersServicesHistory->setObjectData($OrdersServices);
                $OrdersServicesHistory->setOrderData($OrderModel);
                $OrdersServicesHistory->setCommentTpl("{{144}}");
                $OrdersServicesHistory->setCommentParams([]);
                $OrdersServicesHistory->setActionResult(1);

                $this->addOrderAudit($OrdersServicesHistory);
                return null;
            }

            $actionParams = [
                'presentationFileName' => "Voucher {$OrdersServices->getServiceName()}_".mt_rand(1000, 10000), // Презентационное имя файла (то, что будет показано в UI пользователю)
                'comment' => '', // Комментарий к файлу.
                'orderId' => $OrderModel->getOrderId(), //  Идентификатор заявки
                'objectType' => BusinessEntityTypes::BUSINESS_ENTITY_ORDER, //  Тип бизнес-объекта, к которому следует привязать файл. (Возможные значения см в AddDocumentToOrder)
                'objectId' => $OrderModel->getOrderId(), //  ID бизнес - объекта, к которому следует привязать файл.
                'url' => $voucherLink, //  адрес sft
                "usertoken" => $params['usertoken']
            ];

//            var_dump($actionParams);
//            exit;

            $uploadFileResponse = json_decode($apiClient->makeRestRequest('systemService', 'UploadFile', $actionParams), true);

            if (RestException::isArrayRestException($uploadFileResponse)) {
                $this->setError(OrdersErrors::CANNOT_CREATE_TICKET);

                $OrdersServicesHistory = new OrdersServicesHistory();
                $OrdersServicesHistory->setObjectData($OrdersServices);
                $OrdersServicesHistory->setOrderData($OrderModel);
                $OrdersServicesHistory->setCommentTpl("{{144}}");
                $OrdersServicesHistory->setCommentParams([]);
                $OrdersServicesHistory->setActionResult(1);

                $this->addOrderAudit($OrdersServicesHistory);
                return null;
            } else {
                $documentId = $uploadFileResponse['body']['documentId'];
            }

            $OrderDocument = OrderDocumentRepository::getOrderDocumentByDocumentId($documentId);

            if (!is_null($OrderDocument)) {
                // сохраним ваучер для отеля
                if (!$OrdersServices->getOffer()->addVoucher($OrderDocument)) {
                    $OrdersServicesHistory = new OrdersServicesHistory();
                    $OrdersServicesHistory->setObjectData($OrdersServices);
                    $OrdersServicesHistory->setOrderData($OrderModel);
                    $OrdersServicesHistory->setCommentTpl("{{144}}");
                    $OrdersServicesHistory->setCommentParams([]);
                    $OrdersServicesHistory->setActionResult(1);

                    $this->addOrderAudit($OrdersServicesHistory);

                    $this->addLog("Не удалось добавить ваучер к услуге № {$OrdersServices->getServiceID()}", 'error');
                    return null;
                }
            } else {
                $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
                $this->addLog("UploadFile не создал документ", 'error');
                return;
            }
        }
    }
}