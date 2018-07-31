<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/29/16
 * Time: 3:45 PM
 */
class CreateGetCancelRulesDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        // достанем текущий сервис
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // пока только для отелей
        if($OrdersService->getServiceType() == 1){
            $Offer = $OrdersService->getOffer();

            // сделаем запрос штрафов
            $getCancelRulesParams = [
                'offerKey' => $Offer->getOfferKey(),
                'serviceType' => $OrdersService->getServiceType(),
                'gateId' => $OrdersService->getSupplierID(),
                'usertoken' => $params['usertoken'],
                'viewCurrency' => 'RUB'
            ];
            $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
            $getCancelRulesResponseJson = $apiClient->makeRestRequest('supplierService', 'GetCancelRules', $getCancelRulesParams);
            $getCancelRulesResponseArr = json_decode($getCancelRulesResponseJson, true);
            if (isset($getCancelRulesResponseArr['body']) && count($getCancelRulesResponseArr['body'])) {
                $Offer->addCancelPenalty($getCancelRulesResponseArr['body']);
            }
        }
    }


}