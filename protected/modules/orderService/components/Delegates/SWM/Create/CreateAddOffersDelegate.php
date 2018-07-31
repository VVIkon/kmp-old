<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 14.07.17
 * Time: 11:50
 */
class CreateAddOffersDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderService = new OrdersServices();
        $OrderService->unserialize($params['object']);

        $offer = $OrderService->getOffer();

        // поищем доп услуги в кеше
        $addOffersData = !empty($params['offerData']['offerInfo']['additionalServices']) ? $params['offerData']['offerInfo']['additionalServices'] : [];

        if (empty($addOffersData) && $offer->supportsAdditionalServices()) {
            $this->addLog('Оффер поддерживает доп услуги и они не пришли из GetCacheOffer, делаем запрос GetHotelAdditionalService');

            $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
            $getHotelAdditionalServiceResponse = $apiClient->makeRestRequest('searcherService', 'GetHotelAdditionalService', [
                'usertoken' => $params['usertoken'],
                'offerId' => $params['offerId'],
                'lang' => 'ru',
                'viewCurrency' => 'RUB'
            ]);
            if (!is_string($getHotelAdditionalServiceResponse)) {
                $this->setError(OrdersErrors::CANNOT_GET_OFFER);
                return null;
            }
            $addOfferData = json_decode($getHotelAdditionalServiceResponse, true);

            if ($addOfferData['status'] == 1) {
                $this->setError(OrdersErrors::CANNOT_GET_OFFER, $addOfferData);
                return null;
            }

            $addOffersNum = count($addOfferData['body']['offers']);

            $this->addLog("Результат GetHotelAdditionalService - добавили {$addOffersNum} доп услуги", 'info');

            $addOffersData = !empty($addOfferData['body']['offers']) ? $addOfferData['body']['offers'] : [];
        }

        // если есть доп услуги для записи в оффер - запишем их
        if (count($addOffersData)) {
            $this->addLog('Добавляем доп услуги в оффер');

            try {
                foreach ($addOffersData as $addOfferData) {
                    HotelOfferCreator::createAddOfferFromArray($addOfferData, $offer);
                }
            } catch (DomainException $e) {
                $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
                $this->addLog($e->getMessage(), 'error', $addOffersData);
            }
        }
    }
}