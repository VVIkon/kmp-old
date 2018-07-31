<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 24.05.17
 * Time: 13:46
 */
class RunSupplierGetServiceStatusDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);
        $this->params['serviceStatus'] = null;
        $serviceStatusParams['usertoken'] = $params['usertoken'];
        $serviceStatusParams['gateId'] = $params['gateId'];
        $serviceStatusParams['inServiceData'][0]['serviceId'] = $OrdersService->getServiceID();
        $serviceType = $OrdersService->getServiceType();
        $serviceStatusParams['inServiceData'][0]['serviceType'] = $serviceType;

        $engineData = $OrdersService->getOffer()->getEngineData();

        if (is_array($engineData) && count($engineData) > 0) {
            $ed = isset($engineData['data']['data']) ? $engineData['data']['data'] : StdLib::nvl($engineData['data']);

            if (empty($ed)) {
                $this->setError(OrdersErrors::SUPPLIER_ENGINE_NOT_SET, $serviceStatusParams);
                return null;
            }

            if ($serviceType == 1) {
                $serviceStatusParams['inServiceData'][0]['serviceData']['hotelReservation']['engineData'][0]['data'] = $ed;
            } elseif ($serviceType == 2) {
                $serviceStatusParams['inServiceData'][0]['serviceData']['segments']['pnrData'][0]['engine']['data'] = $ed;
            }
        }

        LogHelper::logExt(
            get_class($this), __METHOD__,
            "Для SupplierGetServiceStatus параметры", '',
            [
                '$engineData' => (isset($ed)) ? $ed : null,
                'serviceStatusParams' => $serviceStatusParams
            ],
            'info', 'system.orderservice.info'
        );

        $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
        $serviceStatusResponses = $apiClient->makeRestRequest('supplierService', 'SupplierGetServiceStatus', $serviceStatusParams);

        LogHelper::logExt(
            get_class($this), __METHOD__,
            "SupplierGetServiceStatus ответ команды", '',
            [
                'response' => $serviceStatusResponses
            ],
            'info', 'system.orderservice.info'
        );

        if (is_string($serviceStatusResponses)) {
            $serviceStatusResponse = json_decode($serviceStatusResponses, true);

            // здесь если получили ошибку проверки статуса, то считаем, что заявка в ГПТС ушла в архив
            // поэтому если услуга была в ожидании бронирования, то переводим ее в ручник
            if ($serviceStatusResponse['status'] != 0) {
                // перепроверим ушла ли заявка в архив
                $orderExists = $this->checkOrderExists($OrdersService);

                if (is_null($orderExists) || $orderExists === true) {
                    $this->setError(OrdersErrors::SERVICE_STATUS_NOT_RECEIVED, $serviceStatusResponse);
                    return null;
                }

                if (false === $orderExists) {
                    if ($OrdersService->inStatus(OrdersServices::STATUS_W_BOOKED)) {
                        $OrdersService->setStatus(OrdersServices::STATUS_MANUAL);
                        $OrdersService->save();
                    }

                    $this->setError(OrdersErrors::SERVICE_STATUS_ORDER_IN_ARCHIVE);
                    return null;
                }
            }

            //       LogHelper::logExt(get_class($this), __METHOD__, "По услуге получет ответ", '', ['serviceStatusResponse'=>$serviceStatusResponse], 'trace', 'system.orderservice.info');
            $this->params['serviceStatus'] = StdLib::nvl($serviceStatusResponse['body'][0]['supplierServiceData']['status']);
        } else {
            $this->setError(OrdersErrors::SERVICE_STATUS_NOT_RECEIVED);
        }
    }

    private function checkOrderExists(OrdersServices $service)
    {
        $requestData = [
            'services' => []
        ];

        $requestData['services'][] = [
            'serviceID' => $service->getServiceID(),
            'serviceType' => $service->getServiceType(),
            'addServices' => [],
            'engineData' => [
                'gateId' => 5,
                'data' => [
                    'GPTS_order_ref' => $service->getOrderModel()->getOrderIDGP(),
                    'GPTS_service_ref' => $service->getServiceIDGP()
                ]
            ]
        ];

        $apiClient = new ApiClient(Yii::app()->getModule('orderService'));

        try {
            $response = json_decode($apiClient->makeRestRequest('supplierService', 'SupplierGetOrder', $requestData), true);
        } catch (Exception $e) {
            $this->setError(OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR, [
                'msg' => $e->getMessage()
            ]);
            return null;
        }

        if ($response['status'] !== 0) {
            if ($response['errorCode'] === 49) {
                return false;
            }
        }

        return true;
    }
}