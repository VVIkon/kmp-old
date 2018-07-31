<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/18/16
 * Time: 12:19 PM
 */
class RunSupplierModifyServiceDelegate extends AbstractDelegate
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

        // восстановим объект сервиса
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        $Offer = $OrdersService->getOffer();

        // если поставщик поддерживает отмену
        if ($Offer->hasModifyAbility()) {
            $OrderTourists = $OrdersService->getOrderTourists();
            $tourists = [];

            if (count($OrderTourists)) {
                foreach ($OrderTourists as $OrderTourist) {
                    $tourists[] = [
                        'maleFemale' => $OrderTourist->getTourist()->getSex(),
                        'firstName' => $OrderTourist->getDocument()->getName(),
                        'middleName' => $OrderTourist->getDocument()->getMiddleName(),
                        'lastName' => $OrderTourist->getDocument()->getSurname(),
                    ];;
                }
            } else {
                $this->setError(OrdersErrors::TOURIST_NOT_FOUND);
                return;
            }

            $serviceData = [
                'orderService' => $params['serviceData']['orderService'],
                'tourists' => $tourists,
                'engineData' => $OrdersService->getEngineData()
            ];

            $serviceModifyParams = [
                'serviceType' => $OrdersService->getServiceType(),
                'gateId' => $OrdersService->getSupplierID(),
                'supplierId' => $OrdersService->getSupplier()->getSupplierID(),
                'serviceData' => $serviceData,
                'usertoken' => $params['usertoken']
            ];

//        var_dump($serviceModifyParams);
//        exit;

            $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
            $serviceModifyResponse = json_decode($apiClient->makeRestRequest('supplierService', 'SupplierModifyService', $serviceModifyParams), true);

            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Модификация брони', '',
                $serviceModifyResponse,
                LogHelper::MESSAGE_TYPE_INFO, 'system.orderservice.info'
            );

            if (isset($serviceModifyResponse['errorCode'])) {
                $OrdersServiceHistory = new OrdersServicesHistory();
                $OrdersServiceHistory->setObjectData($OrdersService);
                $OrdersServiceHistory->setOrderData($OrderModel);
                $OrdersServiceHistory->setActionResult(1);
                $OrdersServiceHistory->setCommentParams([]);
                $OrdersServiceHistory->setCommentTpl('{{146}}');
                $this->addOrderAudit($OrdersServiceHistory);

                $this->setError(OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR);
                return null;
            }

            $this->params['modifyResult'] = $serviceModifyResponse['body']['modifyResult'];
            $this->params['bookData'] = $serviceModifyResponse['body']['bookData'];
        } else {
            $account = AccountRepository::getAccountById($params['userProfile']['userId']);

            $dateFrom = StdLib::toRussianDate($params['serviceData']['orderService']['dateStart']);
            $dateTo = StdLib::toRussianDate($params['serviceData']['orderService']['dateFinish']);

            $orderTourists = $OrdersService->getOrderTourists();
            $touristArr = [];

            foreach ($orderTourists as $orderTourist) {
                $touristArr[] = (string)$orderTourist->getTourist();
            }

            $touristStr = implode(', ', $touristArr);

            $this->addNotificationTemplate('manager', [
                'comment' => 'Пользователь ' . (string)$account . " запросил изменение брони. 
                              Оператор должен выполнить изменение брони вручную, т.к. поставщик не поддерживает онлайн изменение. 
                              Запрошенные изменения: дата с {$dateFrom} по {$dateTo}, турист(ы) {$touristStr}."
            ]);

            $this->addResponse('runOWMManual', true);
            $this->breakProcess();
        }
    }
}