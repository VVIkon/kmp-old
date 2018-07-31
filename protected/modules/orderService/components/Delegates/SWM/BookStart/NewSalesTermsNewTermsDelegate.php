<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/9/16
 * Time: 4:13 PM
 */
class NewSalesTermsNewTermsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $BookData = $this->getObjectFromContext('BookData');

        if ($BookData->getNewOfferData()) {
            $this->addLog('Новые ценовые предложения', 'info', $BookData->getNewOfferData());

            $OrderModel = new OrderModel();
            $OrderModel->unserialize($params['orderModel']);

            // запишем истрию в заявку и оффер
            $OrdersServices = new OrdersServices();
            $OrdersServices->unserialize($params['object']);
            $OrdersServices->setNewSalesTermsFromSSSalesTerm($BookData->getNewOfferData());
            $OrdersServices->save(false);

            $this->setDataToContext('object', $OrdersServices->serialize());
            $SaleCurrency = $OrdersServices->getSaleCurrency();

            $CurrencyRates = CurrencyRates::getInstance();

            // запишем новую цену в ответ команды
            $Offer = $OrdersServices->getOffer();
            $Offer->addCurrency('client', $OrderModel->getContract()->getCurrency());
            
            $this->addResponse('newOfferData', $OrdersServices->getSalesTermsInfo()->getArray());

            // запишем новую цену в Историю
            $newPrice = $BookData->getNewOfferDataClientPrice();
            $newCurrencyCode = $BookData->getNewOfferDataClientCurrency();
            $newCurrencyId = $CurrencyRates->getIdByCode($newCurrencyCode);

            $newPriceHistoryStr = round($CurrencyRates->calculateInCurrencyByIds($newPrice, $newCurrencyId, $SaleCurrency->getId()), 2);

            $OrdersServicesHistory = new OrdersServicesHistory();
            $OrdersServicesHistory->setObjectData($OrdersServices);
            $OrdersServicesHistory->setOrderData($OrderModel);
            $OrdersServicesHistory->setCommentTpl("{{137}} $newPriceHistoryStr {$SaleCurrency->getCode()}");
            $OrdersServicesHistory->setCommentParams([]);
            $OrdersServicesHistory->setActionResult(1);

            // log
            $this->addLog("Новые ценовые предложения по услуге № {$OrdersServices->getServiceID()} ($newPriceHistoryStr {$SaleCurrency->getCode()})", 'warning');

            $this->addOrderAudit($OrdersServicesHistory);
        }
    }
}