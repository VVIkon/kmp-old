<?php

/**
 * Объект данных сервис букинга
 */
class ServiceBooking
{
    protected $data;
    protected $supplierOfferData;

    /**
     * Добавим данные заявки
     * @param OrderModel $OrderModel
     */
    public function setOrder(OrderModel $OrderModel)
    {
        $this->data['orderId'] = $OrderModel->getOrderId();
        $this->data['gateOrderId'] = $OrderModel->getOrderIDGP();
    }

    /**
     * Добавим данные услуги
     * @param OrdersServices $OrdersServices
     * @return int
     */
    public function setService(OrdersServices $OrdersServices)
    {
        $this->data['serviceId'] = $OrdersServices->getServiceID();
        $this->data['serviceType'] = $OrdersServices->getServiceType();
        $this->data['supplierId'] = $OrdersServices->getSupplierID();

        if ($OrdersServices->getComment()) {
            $this->supplierOfferData['comment'] = $OrdersServices->getComment();
        } else {
            $this->supplierOfferData['comment'] = null;
        }

        $this->setSupplierOfferData($OrdersServices);
        return 0;
    }

    /**
     * Установка engineData
     * @param $engineData
     */
    public function setEngineData($engineData)
    {
        $this->supplierOfferData['engineData'] = $engineData;
    }

    public function setTourist($touristData)
    {
        if (count($touristData)) {
            $this->supplierOfferData['tourists'] = $touristData;
        } else {
            return OrdersErrors::NO_LINKED_TOURISTS_TO_SERVICES;
        }
    }

    protected function setSupplierOfferData(OrdersServices $OrdersServices)
    {
        $Offer = $OrdersServices->getOffer();
        $this->supplierOfferData['offerKey'] = $Offer->getOfferKey();

        switch ($OrdersServices->getServiceType()) {
            case 1:
//                $this->supplierOfferData['hotelOffer'] = $Offer->getOfferDataForBooking();
                $this->supplierOfferData['salesTerms'] = $OrdersServices->getSalesTerms();

                $this->supplierOfferData['addServices'] = [];

                $addOffers = $OrdersServices->getAddOffers();
                foreach ($addOffers as $addOffer) {
                    $this->supplierOfferData['addServices'][] = $addOffer->toSLAddServiceOffer();
                }

                break;
            case 2:
                $this->supplierOfferData['engineDataList'] = false;
                break;
        }
    }

    /**
     * Получение массива servicebooking
     * @return array
     */
    public function toArray()
    {
        $this->data['supplierOfferData'] = $this->supplierOfferData;
        return $this->data;
    }
}