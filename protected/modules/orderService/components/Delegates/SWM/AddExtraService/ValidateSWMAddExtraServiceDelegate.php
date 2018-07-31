<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 14.07.17
 * Time: 18:03
 */
class ValidateSWMAddExtraServiceDelegate extends AbstractDelegate
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

        // найдем все оффера на доп услуги
        $addOfferFound = false;
        $addOffers = $OrdersService->getOffer()->getAddOffers();
        foreach ($addOffers as $addOffer) { // выберем все доп оффера
            if ($addOffer->getId() == $params['addServiceOfferId']) { // найдем нужный по id
                if ($addOffer->getSubService()->isActive()) { // проверим, что он активен
                    $addOfferFound = true;
                    break;
                } else {
                    $this->setError(OrdersErrors::SUBSERVICE_NOT_ACTIVE);
                    return;
                }
            }
        }
        if (!$addOfferFound) {
            $this->setError(OrdersErrors::ADD_OFFER_NOT_FOUND);
            return;
        }

        // поищем оффер с таким ID в услуге
        $suchOfferAddedToService = false;
        $addServices = $OrdersService->getAddServices();
        foreach ($addServices as $addService) {
            if ($addService->getOfferId() == $params['addServiceOfferId']) {
                $suchOfferAddedToService = true;
                break;
            }
        }

        // если оффер уже был добавлен в услугу - проверим можно ли добавить еще 1
        if ($suchOfferAddedToService) {
            $subServicesSupplier = RefSubServicesSupplierRepository::getBySupplierAndSubService($OrdersService->getSupplier(), $addService->getSubService());

            if (is_null($subServicesSupplier)) {
                $this->setError(OrdersErrors::SUPPLIER_DOESNT_SUPPORT_ADD_SERVICE);
                return;
            }

            if (!$subServicesSupplier->canBeBookedRepeatedly()) {
                $this->setError(OrdersErrors::ADD_OFFER_WAS_ALREADY_ADDED_TO_SERVICE);
                return;
            }
        }
    }
}