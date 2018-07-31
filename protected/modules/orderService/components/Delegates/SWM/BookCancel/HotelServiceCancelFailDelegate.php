<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 08.06.17
 * Time: 17:40
 */
class HotelServiceCancelFailDelegate extends AbstractDelegate
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

        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // только для проживания
        if ($OrdersService->getServiceType() == 1 && isset($params['newPenalties'])) {
            // нужно записать новые штрафы
            $offer = $OrdersService->getOffer();

            $offer->clearCancelPenalties();
            $offer->addCancelPenalty([
                'supplierCurrency' => $params['newPenalties']
            ]);
        }
    }
}