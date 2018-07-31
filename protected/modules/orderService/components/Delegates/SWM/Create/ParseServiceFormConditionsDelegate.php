<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 06.04.17
 * Time: 18:32
 */
class ParseServiceFormConditionsDelegate extends AbstractDelegate
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

        // делаем запрос GetCacheOffer
        $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
        $getOfferResponse = $apiClient->makeRestRequest('searcherService', 'ParseServiceFormConditions', [
            'usertoken' => $params['usertoken'],
            'offerId' => $params['offerId'],
            'serviceType' => $params['serviceType'],
            'companyId' => $OrderModel->getCompany()->getId()
        ]);
    }
}