<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12.05.17
 * Time: 18:05
 */
class SetSupplierServiceDataDelegate extends AbstractDelegate
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
        $OrdersService->refresh();

        $engineData = $OrdersService->getEngineData();

        // если нет данных брони, то отправлять по сути некуда
        // также не отпраляем офлайновую заявку
        if (empty($engineData) || $OrdersService->isOffline()) {
            $this->addLog('Не отправляем на синхронизацию оффлайн услугу или услугу без engineData');
            return;
        }

        $SetServiceDataParams = [
            'orderService' => $OrdersService->getSLOrderService(),
            'engineData' => $engineData,
            'supplierServiceData' => [
                'supplierReservationNum' => $OrdersService->getOffer()->getReservationNumber()
            ]
        ];

        $this->addLog('Запрос SetServiceData', 'info', $SetServiceDataParams);

//        $asyncTask = new AsyncTask();
//        $asyncTask->setModule(Yii::app()->getModule('orderService'));
//        $asyncTask->setTaskParams('supplierService', 'SetServiceData', $SetServiceDataParams);
//        $this->setObjectToContext($asyncTask);

        $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
        $response = json_decode($apiClient->makeRestRequest('supplierService', 'SetServiceData', $SetServiceDataParams), true);

        if(isset($response['modified']) && $response['modified']){
            $this->addLog('Результат SetServiceData', 'info', $response);
        } else {
            $this->addLog('Данные в шлюзе не обновлены (SetServiceData)', 'error', $response);
        }
    }
}