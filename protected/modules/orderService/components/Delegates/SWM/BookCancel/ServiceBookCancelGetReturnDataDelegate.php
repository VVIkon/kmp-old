<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/6/16
 * Time: 1:46 PM
 */
class ServiceBookCancelGetReturnDataDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_POST_ACTION;

    public function run(array $params)
    {
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        $this->addResponse('serviceStatus', $OrdersService->getStatus());
        $this->addResponse('serviceId', $OrdersService->getServiceID());

        if (isset($params['newPenalties'])) {
            $cancelPenalties = $OrdersService->getOffer()->getCancelPenalties();

            $cancelPenaltiesInfo = new CancelPenaltiesInfo();
            foreach ($cancelPenalties as $cancelPenalty) {
                $cancelPenaltiesInfo->addCancelPenalties($cancelPenalty);
            }
            $cancelPenaltiesInfo->addCurrency('local', CurrencyStorage::getById(643));
            if (isset($params['viewCurrency'])) {
                $viewCurrency = CurrencyStorage::findByString($params['viewCurrency']);

                if ($viewCurrency) {
                    $cancelPenaltiesInfo->addCurrency('view', $viewCurrency);
                }
            }

            $this->addResponse('newPenalties', $cancelPenaltiesInfo->getArray());
        }
    }
}