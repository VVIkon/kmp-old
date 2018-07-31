<?php

/**
 * Добавить информацию об услуге и предложении в сущность услуги заявки (см соотв. разделы описания Сущность Услуга, Сущность Услуги Авиаперелёт в разделе Проектирование Базы данных, см. также GetOrderOffers)
 * Примечание: в процессе создания услуги, должны быть в том числе вычислены и записаны следующие данные услуги, которых нет в предложении:
 * Extra = Дополнительная информация об услуге
 * CityID, CountryID =  ID города и страны, используется для визуализации заявки в UI
 * ServiceName = Название услуги, используется для визуализации заявки в UI
 * Для авиа дополнительно вычисляются (до перехода на GetCacheOffer ) поля: KmpPrice = Цена услуги от КМП, AgencyProfit =  Комиссия агента в валюте поставщика, SaleCurrency = Код валюты продажи услуги
 * В DataContext будет записан ID создаваемой услуги
 * В DataContext.SS_ORDERAUDIT в текущий элемент стека аудита, записываются данные созданной услуги.
 */
class CreateAddInfoDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        // восстановим объект OrderModel
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        // инициализация выходного результата
        $params['response']['serviceId'] = '';

        $OrderService = new OrdersServices();
        $OrderService->unserialize($params['object']);

        // создадим оффер из массива
        try {
            $Offer = OfferCreator::createFromArray($params['offerData']['offerInfo'], $OrderService->getServiceNameByType($params['serviceType']));
        } catch (DomainException $e) {
            $this->addLog($e->getMessage(), 'error', $params['offerData']['offerInfo']);
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return;
        }

        // создадим услугу из оффера
        $OrderService->fromOffer($Offer);
        $OrderService->setKtService();

        // ценовые данные
        $PriceOffers = $Offer->getPriceOffers();

        // подцепим заявку
        $OrderService->setOrderId($OrderModel->getOrderId());

        // сохраним цены в услугу
        if (count($PriceOffers)) {
            foreach ($PriceOffers as $PriceOffer) {
                $OrderService->fromPriceOffer($PriceOffer);
            }
        } else {
            $this->setError(OrdersErrors::CANNOT_CREATE_SERVICE);
            return null;
        }

        // посчитаем остаток к платежу
        $OrderService->calculateRestPaymentAmount();

        // сохраним услугу
        if (!$OrderService->save(false)) {
            $this->setError(OrdersErrors::CANNOT_CREATE_SERVICE);
            return null;
        }

        // сохраним ценовые предложения услуги
        if (count($PriceOffers)) {
            foreach ($PriceOffers as $PriceOffer) {
                $OrderService->addPrice($PriceOffer);
            }
        } else {
            $this->setError(OrdersErrors::CANNOT_CREATE_SERVICE);
            return null;
        }

        // создание пустых полей услуги
        $addFields = $OrderService->getOrderModel()->getCompany()->getAddFields();

        // выберем только доп поля услуг
        $addFields = array_filter($addFields, function (AdditionalFieldType $addField) {
            return $addField->isServiceField();
        });

        foreach ($addFields as $addField) {
            // не добавляем поля про нарушение ТП и мин цену - это исключения, с ними разбираемся отдельно
            if ($addField->isReasonFailTP() || $addField->getTypeTemplate() == 5) {
                continue;
            }

            $orderAddField = new OrderAdditionalField();
            $orderAddField->bindService($OrderService);
            $orderAddField->bindAdditionalFieldType($addField);
            $orderAddField->save(false);
        }

        // создадим доп поле с мин ценой, если такое пришло
        if (isset($params['offerData']['offerInfo']['travelPolicy']['orderAddData'])) {
            $additionalFieldTypes = $OrderModel->getCompany()->getAddFields();
            $additionalFieldTypes = array_filter($additionalFieldTypes, function (AdditionalFieldType $addField) {
                return $addField->isMinimalPrice();
            });

            if (count($additionalFieldTypes)) {
                $additionalFieldType = array_shift(array_values($additionalFieldTypes));
            }

            // если у компании есть такое доп поле, то запишем его
            if (isset($additionalFieldType)) {
                $orderAddField = new OrderAdditionalField();
                $orderAddField->bindService($OrderService);
                $orderAddField->bindAdditionalFieldType($additionalFieldType);
                $orderAddField->setValue(json_encode($params['offerData']['offerInfo']['travelPolicy']['orderAddData'], JSON_UNESCAPED_UNICODE));
                $orderAddField->save(false);

                $minimalPriceNotificationParams = [];
                $minimalPriceNotificationParams[strtolower($OrderService->getServiceNameByType($OrderService->getServiceType())) . 'MinimalPriceData'] = json_decode($orderAddField->getValue(), true);
                $this->addNotificationTemplate('minimalprice', $minimalPriceNotificationParams);
            }
        }

        // сохраним в контекст
        $this->params['object'] = $OrderService->serialize();

        // создадим Аудит для услуги
        $OrdersServicesHistory = new OrdersServicesHistory();
        $OrdersServicesHistory->setOrderData($OrderModel);
        $OrdersServicesHistory->setObjectData($OrderService);
        $OrdersServicesHistory->setActionResult(0);
        $OrdersServicesHistory->setCommentTpl('{{136}} {{serviceId}}');
        $OrdersServicesHistory->setCommentParams([
            'serviceId' => $OrderService->getServiceID()
        ]);

        // добавим данные об услуге в нотификацю
        $this->addNotificationData('comment', "{$OrderService->getServiceName()}, начало поездки {$OrderService->getDateStart()}");

        $this->addOrderAudit($OrdersServicesHistory);
        $this->addResponse('serviceId', $OrderService->getServiceID());
    }
}