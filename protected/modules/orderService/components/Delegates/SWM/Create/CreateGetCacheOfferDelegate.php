<?php

/**
 * Получить в сервисе поиска через функцию GetCacheOffer (для авиа пока остаётся  GetOffer) данные предложения.
 * В DataContext будут записаны данные предложения от поставщика.
 * User: rock
 * Date: 8/5/16
 * Time: 4:40 PM
 */
class CreateGetCacheOfferDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrderService = new OrdersServices();
        $OrderService->unserialize($params['object']);

        // проверим тип сервиса
        $serviceName = $OrderService->getServiceNameByType($params['serviceType']);

        if (false === $serviceName) {
            $this->setError(OrdersErrors::UNKNOWN_SERVICE_TYPE);
            return null;
        }

        // делаем запрос GetCacheOffer
        $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
        $getOfferResponse = $apiClient->makeRestRequest('searcherService', 'GetCacheOffer', [
            'usertoken' => $params['usertoken'],
            'offerId' => $params['offerId'],
            'serviceType' => $params['serviceType'],
            'lang' => 'ru',
        ]);
        if (!is_string($getOfferResponse)) {
            $this->setError(OrdersErrors::CANNOT_GET_OFFER);
            return null;
        }
        $offerData = json_decode($getOfferResponse, true);
        if ($offerData === null || $offerData['status'] !== 0 || empty($offerData['body'])) {
            $this->setError(OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR);
            return null;
        }

        $this->params['offerData'] = $offerData['body'];
    }

}