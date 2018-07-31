<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 18.07.17
 * Time: 18:19
 */
class ValidateSWMRemoveExtraServiceDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // поищем оффер с таким ID в услуге
        $suchOfferAddedToService = false;
        $addServices = $OrdersService->getAddServices();
        foreach ($addServices as $addService) {
            if ($addService->getId() == $params['addServiceId']) {
                $suchOfferAddedToService = true;
                $this->params['addServiceToDeleteName'] = $addService->getName();
                break;
            }
        }

        if (!$suchOfferAddedToService) {
            $this->setError(OrdersErrors::SUBSERVICE_NOT_FOUND);
            return;
        }

        if(!($addService->canBeRemoved())){
            $this->setError(OrdersErrors::SUBSERVICE_AND_SERVICE_STATUS_MUST_BE_NEW);
            return;
        }
    }
}