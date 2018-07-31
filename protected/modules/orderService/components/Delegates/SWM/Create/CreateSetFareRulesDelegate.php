<?php

/**
 * Для Avia.
 * Запрашивает правила тарифов в GPTS и сохраняет их в AviaFareRules и AviaTextRules
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 02.03.2017
 * Time: 12:54
 */
class CreateSetFareRulesDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // только для avia
        if ($OrdersService->getServiceType() == 2) {
            $par = [
                'offerKey' => $OrdersService->getOffer()->getOfferKey(),
                'usertoken' => $params['usertoken'],
//                'token' => $params['token'],
                'offerId' => $OrdersService->getOffer()->getOfferId(),
                'tripId' => 0
            ];

            $AsyncTask = new AsyncTask();
            $AsyncTask->setModule(Yii::app()->getModule('orderService'));
            $AsyncTask->setTaskParams('orderService', 'SetFareRules', $par);
            $this->setObjectToContext($AsyncTask);
        }
    }
}