<?php

/**
 * Репозиторий отельных офферов поиска
 */
class HotelOfferResponseRepository implements OfferResponseRepositoryInterface
{
    /**
     * Возвращает агрегат оффера поиска по отелям
     * @param $offerId
     * @return HotelOfferResponse
     */
    public static function getByOfferId($offerId)
    {
        $HotelOfferResponse = HotelOfferResponse::model()->with(
            'roomServices',
            'priceOffers.taxes'
        )
        ->findByAttributes(array('id' => $offerId));

        return $HotelOfferResponse;
    }

    /**
     * Получение офферов по токену
     * @param $token
     * @param $maxId
     * @param $limit
     * @return AbstractOffer []
     */
    public static function getOffersByToken($token, $maxId = 0, $limit = 1000)
    {
        // выберем отели
        $Criteria = new CDbCriteria();
        $Criteria->limit = $limit;

        if ($maxId > 0) {
            $Criteria->compare('t.id', '>' . $maxId);
        }

        $Criteria->with = array(
            'priceOffers.taxes',
//            'priceOfferByDays',
            'roomServices',
            'HotelInfo',
            'HotelInfo.HotelServices',
            'HotelInfo.HotelServices.HotelServiceGroupMain',
            'offerValue',
            'addOffers'
        );
        $Criteria->compare('token', $token);
        $Criteria->order = 't.id';

        return HotelOfferResponse::model()->findAll($Criteria);
    }


    public static function decorateForGetSearchResult($offers, Currency $currency, $lang)
    {
        $offerIds = [];
        $res = [];

        if (count($offers)) {
            $offerInfo = [];
            $module = YII::app()->getModule('searcherService');

            foreach ($offers as $offer) {
                $offer->addCurrency('view', $currency);
                $offer->addCurrency('local', CurrencyStorage::findByString(643));
                $offer->addCurrency('client', CurrencyStorage::findByString(643));
                $offer->setLang($lang);
                $offer->setConfig($module->getConfig());

                if (!isset($offerInfo[$offer->getHotelId()]['hotel'])) {
                    $HotelInfo = $offer->getHotelInfo();
                    $HotelInfo->setLang($lang);

                    $offerInfo[$offer->getHotelId()]['hotel'] = $HotelInfo->getShortHotelInfo();
                }

                $offerInfo[$offer->getHotelId()]['offers'][] = $offer->toArray();
                $offerIds[] = $offer->getId();
            }

            $res = array_values($offerInfo);
        }

        return [$res, $offerIds];
    }

    /**
     *
     * @param TokenCache $tokenCache
     * @return HotelSearchRequest
     */
    public static function getSearchRequestByToken(TokenCache $tokenCache)
    {
        return HotelSearchRequest::model()->findByPk($tokenCache->getToken());
    }

    /**
     * @param TokenCache $tokenCache
     * @return mixed
     */
    public static function getOfferIdsWithMinimalPriceByToken(TokenCache $tokenCache)
    {
        $resp = Yii::app()->db->createCommand()
            ->select('HOO.id, HPO.amountBrutto * (IFNULL(CUR.RateCBR, 1) / IF(CUR.Nominal = 0, 1, CUR.Nominal)) AS amount')
            ->from('ho_offers AS HOO')
            ->join('ho_priceOffer HPO', 'HOO.offerKey = HPO.offerkey')
            ->join('kt_currencies_rates CUR', 'CUR.CurrencyID = HPO.currency and CUR.RateDate = date(now())')
            ->where('HPO.type = 2')
            ->andWhere('HOO.token = :token', array(':token' => $tokenCache->getToken()))
            ->order('amount ASC')
            ->limit(1)
            ->queryAll();

        $answer = [];

        if (count($resp)) {
            foreach ($resp as $item) {
                $answer[] = $item['id'];
            }
        }

        return $answer;
    }
}